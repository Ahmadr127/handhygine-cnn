"""
core/camera_manager.py — Multi-thread camera processor

Setiap kamera berjalan di thread terpisah.
Pipeline per frame:
  capture → detect → track → zone check → compliance update → broadcast WS
"""
import cv2
import time
import threading
import queue
import base64
import json
import numpy as np
import supervision as sv
from datetime import datetime

from config import (
    FRAME_QUEUE_SIZE, STREAM_FPS, DETECT_EVERY_N_FRAMES,
    CLASS_PERSON, INSTRUMENT_CLASSES,
    CLASS_NAMES, TRACK_RESET_SECONDS,
)
from core.detector import Detector
from core.tracker import Tracker
from core.zone_manager import ZoneManager
from core.group_compliance import GroupComplianceEngine, GroupState
from utils.db import insert_monitoring_log, update_camera_status
from utils.snapshot import save_snapshot


# ─── Warna bounding box per state (disesuaikan dengan group state jika perlu) ──
STATE_COLORS = {
    "monitoring":          (200, 200, 200),   # abu
    "carrying_instrument": (0, 165, 255),     # oranye
    "hand_wash_zone":      (255, 255, 0),     # kuning
    "patuh":               (0, 230, 0),       # hijau
    "tidak_patuh":         (0, 0, 230),       # merah
}

STATE_LABELS_ID = {
    "monitoring":          "Monitoring",
    "carrying_instrument": "Membawa Instrumen",
    "hand_wash_zone":      "Cuci Tangan",
    "hand_wash_pending":   "Cuci Tangan...",
    "hand_washed_done":    "Sudah Cuci Tangan ✓",
    "patuh":               "PATUH",
    "tidak_patuh":         "TIDAK PATUH",
}

STATE_COLORS["hand_wash_pending"] = (200, 200, 0)    # kuning redup (sedang dwell)
STATE_COLORS["hand_washed_done"] = (0, 200, 150)    # hijau-tosca (sudah cuci)


def make_person_key(camera_id: int, tracker_id: int) -> str:
    """Kunci sesi compliance unik per kamera + ByteTrack ID."""
    return f"{camera_id}:{tracker_id}"


def display_tracker_id(person_key: str) -> str:
    """Ambil nomor tracker untuk label UI / log DB (contoh: '4:13' → '13')."""
    if ":" in person_key:
        return person_key.rsplit(":", 1)[-1]
    return person_key


class CameraProcessor:
    """
    Memproses satu kamera: capture + detect + track + compliance.
    Hasilkan frame ter-annotate ke frame_queue untuk WebSocket.
    """

    def __init__(self, camera_id: int, nama: str, source, group_id: int, group_engine: GroupComplianceEngine):
        self.camera_id = camera_id
        self.nama = nama
        self.source = source  # int (USB), str (RTSP/file)
        self.group_id = group_id
        self.group_engine = group_engine
        self._stop_event = threading.Event()
        self.frame_queue: queue.Queue = queue.Queue(maxsize=FRAME_QUEUE_SIZE)

        self.detector = Detector()
        self.tracker = Tracker()
        self.zone_mgr = ZoneManager(self.camera_id)

        # Annotator supervision
        self.box_annotator = sv.BoxAnnotator(thickness=2)
        self.label_annotator = sv.LabelAnnotator(text_scale=0.5)
        
        self.handwash_dwell_timers: dict[int, dict] = {}
        # Format: { tid: {"start": float, "leave": float|None, "reported": bool} }
        self._tid_last_seen: dict[int, float] = {}   # deteksi reuse ID ByteTrack
        self._thread: threading.Thread | None = None
        self._frame_count: int = 0                    # counter untuk frame skipping
        self._last_detections = None                   # deteksi terakhir (reuse saat skip)

    # ─── Start / Stop ────────────────────────────────────────────────────────

    def start(self):
        self._stop_event.clear()
        self._thread = threading.Thread(target=self._run, daemon=True, name=f"cam-{self.camera_id}")
        self._thread.start()
        print(f"[Camera {self.camera_id}] Dimulai: {self.nama} ({self.source})")

    def stop(self):
        self._stop_event.set()
        if self._thread:
            self._thread.join(timeout=5)
        update_camera_status(self.camera_id, False)
        print(f"[Camera {self.camera_id}] Dihentikan.")

    def is_running(self) -> bool:
        return self._thread is not None and self._thread.is_alive()

    # ─── Processing Loop ─────────────────────────────────────────────────────

    def _run(self):
        # Parse source
        src = int(self.source) if str(self.source).isdigit() else self.source
        cap = cv2.VideoCapture(src)

        if not cap.isOpened():
            print(f"[Camera {self.camera_id}] GAGAL membuka sumber: {self.source}")
            return

        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
        frame_delay = 1.0 / STREAM_FPS

        while not self._stop_event.is_set():
            t0 = time.time()
            ret, frame = cap.read()

            if not ret:
                # RTSP reconnect
                print(f"[Camera {self.camera_id}] Frame gagal, reconnect...")
                cap.release()
                time.sleep(2)
                cap = cv2.VideoCapture(src)
                continue

            self._frame_count += 1
            # Frame skipping: inference berat hanya setiap N frame
            # Frame yang diskip langsung pakai deteksi terakhir → CPU lebih ringan
            run_detect = (self._frame_count % DETECT_EVERY_N_FRAMES == 0)
            annotated = self._process_frame(frame, run_detect=run_detect)

            # Encode ke JPEG → base64
            _, buf = cv2.imencode(".jpg", annotated, [cv2.IMWRITE_JPEG_QUALITY, 70])
            b64 = base64.b64encode(buf.tobytes()).decode("utf-8")

            payload = {
                "camera_id": self.camera_id,
                "camera_name": self.nama,
                "frame": b64,
                "timestamp": datetime.now().isoformat(),
            }

            # Non-blocking push ke queue
            try:
                self.frame_queue.put_nowait(payload)
            except queue.Full:
                try:
                    self.frame_queue.get_nowait()
                    self.frame_queue.put_nowait(payload)
                except queue.Empty:
                    pass

            # Rate limiting
            elapsed = time.time() - t0
            sleep_t = frame_delay - elapsed
            if sleep_t > 0:
                time.sleep(sleep_t)

        cap.release()

    def _process_frame(self, frame: np.ndarray, run_detect: bool = True) -> np.ndarray:
        """
        Jalankan pipeline deteksi → tracking → zone → compliance pada satu frame.
        Jika run_detect=False, pakai deteksi terakhir (frame skipping).
        Return frame ter-annotate.
        """
        if run_detect:
            self._last_detections = self.detector.detect(frame)
        detections = self._last_detections if self._last_detections is not None else self.detector.detect(frame)

        if len(detections) == 0:
            # Tetap gambar zona walaupun tidak ada deteksi
            annotated = frame.copy()
            self._draw_zones(annotated)
            return annotated

        # Ambil instrumen dari RAW detections agar tidak dibuang oleh tracker
        instrument_mask = np.isin(detections.class_id, list(INSTRUMENT_CLASSES))
        instruments = detections[instrument_mask]

        # Ambil person untuk di-track
        person_mask = detections.class_id == CLASS_PERSON
        person_detections = detections[person_mask]

        if len(person_detections) > 0:
            persons = self.tracker.update(person_detections)
        else:
            persons = sv.Detections.empty()

        # Buat set bounding box instrumen (untuk cek proximity)
        instr_boxes = instruments.xyxy if len(instruments) > 0 else np.array([])

        labels = []
        colors = []
        now = time.time()
        current_tids: set[int] = set()

        for i in range(len(persons)):
            tid = int(persons.tracker_id[i]) if persons.tracker_id is not None else -1
            if tid < 0:
                continue
            current_tids.add(tid)

            person_key = make_person_key(self.camera_id, tid)
            last_seen = self._tid_last_seen.get(tid)
            gap = (now - last_seen) if last_seen is not None else None

            # ByteTrack reuse ID setelah track lama hilang → reset sesi compliance
            if self._should_reset_track_session(person_key, gap):
                self.group_engine.reset_session(person_key)
                self.handwash_dwell_timers.pop(tid, None)

            self._tid_last_seen[tid] = now

            bbox = persons.xyxy[i]
            conf = float(persons.confidence[i])

            # Posisi bawah tengah (kaki) dan tengah bounding box
            bx, by = Tracker.get_bottom_center(bbox)
            cx, cy = Tracker.get_center(bbox)

            # Scale ke 800x450 reference (zona digambar di canvas 800x450 UI)
            h_frame, w_frame = frame.shape[:2]
            scale_x_ref = 800.0 / w_frame
            scale_y_ref = 450.0 / h_frame
            bx_scaled = bx * scale_x_ref
            by_scaled = by * scale_y_ref
            cx_scaled = cx * scale_x_ref
            cy_scaled = cy * scale_y_ref

            # Cek apakah membawa instrumen (overlap/proximity dengan frame ini)
            near_instrument = self._is_near_instrument(bbox, instr_boxes)

            # Zona wastafel: bbox orang harus overlap dengan polygon zona
            # (titik tengah terlalu ketat — orang berdiri di depan sanitizer dinding
            #  sering tidak masuk zona kecil di dinding meskipun sedang cuci tangan)
            x1s, y1s, x2s, y2s = (
                bbox[0] * scale_x_ref, bbox[1] * scale_y_ref,
                bbox[2] * scale_x_ref, bbox[3] * scale_y_ref,
            )
            in_handwash = self.zone_mgr.bbox_intersects_handwash_zone(x1s, y1s, x2s, y2s)
            state = "monitoring"

            # Update last seen timestamp & frame in group engine
            self.group_engine.update_last_seen(self.camera_id, person_key, frame, bbox)

            if near_instrument:
                # Laporkan ke engine (untuk compliance tracking)
                self.group_engine.report_instrument(self.camera_id, person_key, conf, frame)
                # Tampilkan label HANYA jika instrumen benar ada di frame ini
                state = "carrying_instrument"

            # Zona wastafel: bbox menyentuh zona → mulai dwell timer
            # Terkonfirmasi cuci tangan setelah menetap ≥ 2 detik di zona
            if in_handwash:
                if tid not in self.handwash_dwell_timers:
                    # Pertama kali masuk zona → mulai timer baru
                    self.handwash_dwell_timers[tid] = {
                        "start":    time.time(),
                        "leave":    None,   # masih di dalam zona
                        "reported": False,  # belum lapor ke engine
                    }
                else:
                    # Masuk lagi setelah sempat keluar → clear leave time
                    self.handwash_dwell_timers[tid]["leave"] = None

                dwell = time.time() - self.handwash_dwell_timers[tid]["start"]
                if dwell >= 2.0:
                    # Hanya lapor ke engine SEKALI per sesi (bukan setiap frame)
                    if not self.handwash_dwell_timers[tid]["reported"]:
                        self.group_engine.report_hand_wash(self.camera_id, person_key, frame)
                        self.handwash_dwell_timers[tid]["reported"] = True
                    state = "hand_wash_zone"     # Terkonfirmasi (≥ 2 detik)
                else:
                    state = "hand_wash_pending"  # Menunggu konfirmasi 2 detik
            else:
                # Di luar zona: catat waktu keluar & reset jika sudah > 3 detik di luar
                if tid in self.handwash_dwell_timers:
                    info = self.handwash_dwell_timers[tid]
                    if info["leave"] is None:
                        info["leave"] = time.time()  # catat kapan keluar zona

                    # Hitung gap dari waktu KELUAR, bukan dari waktu masuk
                    gap_outside = time.time() - info["leave"]
                    if gap_outside > 3.0:  # reset setelah 3 detik di luar zona
                        del self.handwash_dwell_timers[tid]

            # Cek status akhir dari compliance engine (Patuh/Tidak Patuh)
            # Status final selalu menimpa state sementara
            final_status = self.group_engine.get_person_status(person_key)
            if final_status:
                state = final_status
            elif state == "monitoring":
                # Tampilkan state engine internal HANYA untuk hand_washed
                # JANGAN tampilkan "carrying" dari engine ke label video —
                # itu bisa menyebabkan false positive saat instrumen sudah pergi dari frame
                engine_state = self.group_engine.get_engine_state(person_key)
                if engine_state == "hand_washed":
                    state = "hand_washed_done"    # Sudah cuci tangan ✓

            label = f"#{tid} {STATE_LABELS_ID.get(state, state)}"
            labels.append(label)
            colors.append(STATE_COLORS.get(state, (200, 200, 200)))

        # Bersihkan tracker yang sudah lama tidak terlihat di frame ini
        for stale_tid in set(self._tid_last_seen) - current_tids:
            if now - self._tid_last_seen[stale_tid] > TRACK_RESET_SECONDS:
                del self._tid_last_seen[stale_tid]
                self.handwash_dwell_timers.pop(stale_tid, None)

        # Annotate persons
        annotated = frame.copy()
        if len(persons) > 0:
            annotated = self.box_annotator.annotate(annotated, persons)
            annotated = self.label_annotator.annotate(annotated, persons, labels)

        # Annotate instruments (bounding box saja)
        if len(instruments) > 0:
            for box in instruments.xyxy:
                x1, y1, x2, y2 = map(int, box)
                cv2.rectangle(annotated, (x1, y1), (x2, y2), (255, 165, 0), 2)

        # Draw zones
        self._draw_zones(annotated)

        # Bersihkan state yang sudah kadaluarsa setiap frame.
        self.group_engine.cleanup_expired()

        return annotated

    def _should_reset_track_session(self, person_key: str, gap: float | None) -> bool:
        """
        Reset sesi jika ByteTrack reuse ID atau track lama sudah final.
        - gap None  → pertama kali ID muncul di kamera ini
        - gap besar → track lama sudah hilang cukup lama (reuse ID)
        - gap kecil + sesi lama sudah PATUH/TIDAK PATUH → orang baru dapat ID yang sama
        """
        if gap is None:
            return self.group_engine.has_session(person_key)

        if gap > TRACK_RESET_SECONDS:
            return True

        if gap > 0 and self.group_engine.is_finalized(person_key):
            return True

        return False

    def _is_near_instrument(self, person_bbox, instr_boxes, overlap_threshold=0.5) -> bool:
        """
        Cek apakah instrumen berada di dalam atau sangat dekat dengan bounding box orang.

        Menggunakan rasio overlap terhadap luas instrumen (bukan IoU), karena instrumen
        yang dibawa selalu berada DI DALAM bbox orang sehingga IoU-nya kecil meskipun
        sepenuhnya overlap.

        overlap_ratio = intersection_area / instrument_area
        Jika > overlap_threshold (default 50%) → dianggap membawa instrumen.
        """
        if len(instr_boxes) == 0:
            return False

        px1, py1, px2, py2 = person_bbox

        for ib in instr_boxes:
            ix1, iy1, ix2, iy2 = ib
            i_area = (ix2 - ix1) * (iy2 - iy1)
            if i_area <= 0:
                continue

            # Hitung intersection
            inter_x1 = max(px1, ix1)
            inter_y1 = max(py1, iy1)
            inter_x2 = min(px2, ix2)
            inter_y2 = min(py2, iy2)

            if inter_x2 > inter_x1 and inter_y2 > inter_y1:
                inter_area = (inter_x2 - inter_x1) * (inter_y2 - inter_y1)
                # Rasio: seberapa banyak instrumen yang ada di dalam bbox orang
                overlap_ratio = inter_area / i_area
                if overlap_ratio > overlap_threshold:
                    return True

        return False

    def _draw_zones(self, frame: np.ndarray):
        """Gambar polygon zone di frame (semi-transparan)."""
        overlay = frame.copy()
        h_frame, w_frame = frame.shape[:2]
        scale_x = w_frame / 800.0
        scale_y = h_frame / 450.0

        zone_colors = {
            "sanitizer": (0, 255, 0),    # hijau
            "wastafel":  (255, 255, 0),  # kuning
        }
        for zone in self.zone_mgr.zones:
            color = zone_colors.get(zone["tipe"], (128, 128, 128))
            pts_shapely = list(zone["polygon"].exterior.coords)
            pts = np.array([[int(x * scale_x), int(y * scale_y)] for x, y in pts_shapely[:-1]], np.int32)
            cv2.fillPoly(overlay, [pts], color)
            cv2.polylines(frame, [pts], True, color, 2)
            # Label zona
            if len(pts) > 0:
                cx = int(sum(p[0] for p in pts) / len(pts))
                cy = int(sum(p[1] for p in pts) / len(pts))
                cv2.putText(frame, zone["nama"], (cx - 30, cy),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)
        cv2.addWeighted(overlay, 0.2, frame, 0.8, 0, frame)

    # (Callback on_event tidak lagi di dalam CameraProcessor, tapi di CameraManager)


# ─── Global Camera Registry ───────────────────────────────────────────────────

class CameraManager:
    """Registry global semua CameraProcessor yang berjalan."""

    def __init__(self):
        self._cameras: dict[int, CameraProcessor] = {}
        self._group_engines: dict[int, GroupComplianceEngine] = {}
        self._lock = threading.Lock()

    def _get_or_create_group_engine(self, group_id: int) -> GroupComplianceEngine:
        if group_id not in self._group_engines:
            self._group_engines[group_id] = GroupComplianceEngine(group_id, on_event=self._on_group_event)
        return self._group_engines[group_id]

    def _on_group_event(self, event_data: dict, frame):
        """
        Dipanggil oleh GroupComplianceEngine saat status grup (PATUH/TIDAK PATUH) terdeteksi.
        Simpan snapshot + log ke database.
        """
        status = event_data["status"]
        cam_id = event_data["camera_id"]
        
        # Cari nama kamera
        camera_name = f"Camera {cam_id}"
        proc = self._cameras.get(cam_id)
        if proc:
            camera_name = proc.nama

        if frame is None:
            print(f"[GroupCompliance] Peringatan: frame None untuk event {status}, snapshot dilewati.")
            snap_path = None
        else:
            snap_path = save_snapshot(
                frame,
                person_id=event_data["person_id"],
                status=status,
                camera_name=camera_name,
            )

        try:
            log_id = insert_monitoring_log(
                person_id=event_data["person_id"],
                group_id=event_data["group_id"],
                camera_id=cam_id,
                status=status,
                membawa_instrumen=event_data["membawa_instrumen"],
                aktivitas_cuci_tangan=event_data["aktivitas_cuci_tangan"],
                snapshot_path=snap_path,
                confidence=round(event_data["confidence"] * 100, 2),
            )
            print(f"[GroupCompliance] Grup {event_data['group_id']} | Log #{log_id}: {status.upper()}")
        except Exception as e:
            print(f"[GroupCompliance] Error simpan log: {e}")

    def start_camera(self, camera_id: int, nama: str, source, group_id: int) -> bool:
        with self._lock:
            if camera_id in self._cameras and self._cameras[camera_id].is_running():
                return False  # sudah jalan
                
            engine = self._get_or_create_group_engine(group_id)
            proc = CameraProcessor(camera_id, nama, source, group_id, engine)
            proc.start()
            self._cameras[camera_id] = proc
            update_camera_status(camera_id, True)
            return True

    def stop_camera(self, camera_id: int):
        with self._lock:
            proc = self._cameras.pop(camera_id, None)
            if proc:
                proc.stop()
                
    def start_group(self, group_id: int, cameras_data: list):
        """Memulai semua kamera dalam satu grup."""
        for cam in cameras_data:
            self.start_camera(cam["id"], cam["nama_kamera"], cam["source"], group_id)
            
    def stop_group(self, group_id: int):
        """Menghentikan semua kamera dalam satu grup."""
        with self._lock:
            to_stop = [cid for cid, proc in self._cameras.items() if proc.group_id == group_id]
            for cid in to_stop:
                proc = self._cameras.pop(cid)
                proc.stop()
            if group_id in self._group_engines:
                del self._group_engines[group_id]

    def stop_all(self):
        with self._lock:
            for proc in self._cameras.values():
                proc.stop()
            self._cameras.clear()
            self._group_engines.clear()

    def get_processor(self, camera_id: int) -> CameraProcessor | None:
        return self._cameras.get(camera_id)

    def is_running(self, camera_id: int) -> bool:
        proc = self._cameras.get(camera_id)
        return proc is not None and proc.is_running()

    def running_cameras(self) -> list[int]:
        return [cid for cid, p in self._cameras.items() if p.is_running()]


# Singleton
camera_manager = CameraManager()
