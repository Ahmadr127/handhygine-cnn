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
    FRAME_QUEUE_SIZE, STREAM_FPS,
    CLASS_PERSON, INSTRUMENT_CLASSES, HANDWASH_CLASSES, CLASS_PINTU,
    CLASS_NAMES,
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
STATE_COLORS["hand_washed_done"] = (0, 200, 150)    # hijau-tosca (sudah cuci, belum pintu)


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
        
        self.handwash_dwell_timers: dict[int, float] = {}
        self._thread: threading.Thread | None = None

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

            annotated = self._process_frame(frame)

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

    def _process_frame(self, frame: np.ndarray) -> np.ndarray:
        """
        Jalankan pipeline deteksi → tracking → zone → compliance pada satu frame.
        Return frame ter-annotate.
        """
        detections = self.detector.detect(frame)

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

        for i in range(len(persons)):
            tid = int(persons.tracker_id[i]) if persons.tracker_id is not None else -1
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

            # Cek apakah membawa instrumen (overlap/proximity)
            near_instrument = self._is_near_instrument(bbox, instr_boxes)

            # Zona wastafel: cek intersection bounding box orang vs polygon zona
            # Lebih fleksibel dari cek satu titik — cocok untuk gerakan yang tidak konsisten
            x1s, y1s, x2s, y2s = (
                bbox[0] * scale_x_ref, bbox[1] * scale_y_ref,
                bbox[2] * scale_x_ref, bbox[3] * scale_y_ref,
            )
            in_handwash = self.zone_mgr.bbox_intersects_handwash_zone(x1s, y1s, x2s, y2s)
            # Zona pintu: tetap pakai titik kaki (lebih akurat untuk deteksi masuk pintu)
            in_door = self.zone_mgr.is_in_door_zone(bx_scaled, by_scaled)

            state = "monitoring"

            if near_instrument:
                self.group_engine.report_instrument(self.camera_id, str(tid), conf, frame)
                state = "carrying_instrument"

            # Zona wastafel: bbox menyentuh zona → mulai dwell timer
            # Konfirmasi cuci tangan setelah 2 detik menetap (toleran terhadap gerakan tidak konsisten)
            if in_handwash:
                if tid not in self.handwash_dwell_timers:
                    self.handwash_dwell_timers[tid] = time.time()

                dwell = time.time() - self.handwash_dwell_timers[tid]
                if dwell >= 2.0:
                    # Kirim frame untuk fallback snapshot
                    self.group_engine.report_hand_wash(self.camera_id, str(tid), frame)
                    state = "hand_wash_zone"     # Terkonfirmasi (≥2 detik)
                else:
                    state = "hand_wash_pending"  # Bbox menyentuh zona, menunggu 2 detik
            else:
                # Keluar zona: timer TIDAK direset agar gerakan tidak konsisten tidak batalkan
                # Timer hanya direset jika keluar zona lebih dari 3 detik
                if tid in self.handwash_dwell_timers:
                    gap = time.time() - self.handwash_dwell_timers[tid]
                    if gap > 5.0:  # grace period: boleh keluar zona max 5 detik
                        del self.handwash_dwell_timers[tid]

            if in_door:
                self.group_engine.report_door_entry(self.camera_id, str(tid), frame)

            # Cek status akhir dari compliance engine (Patuh/Tidak Patuh)
            final_status = self.group_engine.get_person_status(str(tid))
            if final_status:
                state = final_status
            elif state not in ("hand_wash_zone", "hand_wash_pending", "carrying_instrument"):
                # Tampilkan state engine internal untuk monitoring
                engine_state = self.group_engine.get_engine_state(str(tid))
                if engine_state == "hand_washed":
                    state = "hand_washed_done"    # Sudah cuci tangan, menuju pintu
                elif engine_state == "carrying":
                    state = "carrying_instrument"  # Engine confirm membawa instrumen

            label = f"#{tid} {STATE_LABELS_ID.get(state, state)}"
            labels.append(label)
            colors.append(STATE_COLORS.get(state, (200, 200, 200)))

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

        return annotated

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
            "pintu":     (0, 0, 255),    # merah
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
