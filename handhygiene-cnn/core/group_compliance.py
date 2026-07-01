"""
core/group_compliance.py — State machine kepatuhan di level Grup Monitoring

Perubahan v2:
- Tracking per-person menggunakan person_states dict (fix race condition multi-orang)
- report_hand_wash menerima person_id (fix Bug #1)
- Cuci tangan disimpan meskipun state IDLE / CARRYING (fix Bug #3 - pre-emptive wash window)
- Evaluasi kepatuhan sekarang berdasarkan cuci tangan dan instrumen, bukan lagi zona pintu
- Auto-cleanup entry yang sudah expire
"""
import time
from enum import Enum
import threading

class GroupState(str, Enum):
    IDLE       = "idle"
    CARRYING   = "carrying"
    HAND_WASHED = "hand_washed"


class PersonState:
    """Menyimpan state satu orang dalam satu siklus kepatuhan."""
    __slots__ = ("state", "carrying_time", "wash_time", "confidence", "last_frame", "last_camera_id", "instrumen_terdeteksi", "finalized")

    def __init__(self):
        self.state: GroupState        = GroupState.IDLE
        self.carrying_time: float     = 0.0
        self.wash_time: float         = 0.0   # dapat diset bahkan saat IDLE (pre-emptive)
        self.confidence: float        = 0.0
        self.last_frame               = None
        self.last_camera_id: int      = -1
        self.instrumen_terdeteksi: bool = False   # True jika report_instrument pernah dipanggil
        self.finalized: bool = False


class GroupComplianceEngine:
    """
    Satu engine per group_id. Menerima event dari semua kamera dalam grup.
    Menerapkan logika berbasis waktu per-orang (person_id):
      - Instrumen WAJIB terdeteksi (pembeda perawat vs bukan perawat)
      - Cuci tangan WAJIB dilakukan dalam window
      - Instrumen dan cuci tangan boleh terdeteksi dalam urutan apapun
      - Keduanya harus ada dalam window waktu yang sama
    """

    def __init__(self, group_id: int, on_event=None):
        self.group_id       = group_id
        self.on_event       = on_event
        self.lock           = threading.Lock()
        self.window_seconds = 180.0  # 3 menit: cukup untuk ambil instrumen → letakkan → cuci tangan → masuk pintu

        # State per person_id
        self._person_states: dict[str, PersonState] = {}

        # Status final per orang untuk label video (person_id → (status, timestamp))
        self.person_status: dict[str, tuple[str, float]] = {}

    # ─── Helpers ─────────────────────────────────────────────────────────────

    def _get_person(self, person_id: str) -> PersonState:
        """Ambil atau buat PersonState untuk person_id."""
        if person_id not in self._person_states:
            self._person_states[person_id] = PersonState()
        return self._person_states[person_id]

    def _cleanup_expired(self):
        """Hapus entry person yang sudah lama expire atau finalize non-compliance pada timeout."""
        now = time.time()
        expired = []
        for pid, ps in list(self._person_states.items()):
            if ps.finalized:
                first_event = min(
                    ps.carrying_time if ps.carrying_time > 0 else now,
                    ps.wash_time if ps.wash_time > 0 else now,
                )
                if now - first_event > self.window_seconds * 2:
                    expired.append(pid)
                continue

            first_event = min(
                ps.carrying_time if ps.carrying_time > 0 else float('inf'),
                ps.wash_time     if ps.wash_time > 0     else float('inf'),
            )
            if first_event == float('inf'):
                continue

            if ps.instrumen_terdeteksi and ps.wash_time == 0 and (now - first_event) > self.window_seconds:
                self._finalize_status("tidak_patuh", ps.last_frame, ps.last_camera_id, pid, ps)
                expired.append(pid)
                continue

            if ps.state == GroupState.IDLE and (now - first_event) > self.window_seconds * 2:
                expired.append(pid)

        for pid in expired:
            del self._person_states[pid]

    def cleanup_expired(self):
        """Public cleanup hook untuk flush person state yang sudah kadaluarsa."""
        with self.lock:
            self._cleanup_expired()

    # ─── Public API ──────────────────────────────────────────────────────────

    def report_instrument(self, camera_id: int, person_id: str, confidence: float, frame):
        """
        Dipanggil saat seseorang terdeteksi membawa instrumen medis.
        Set state CARRYING dan evaluasi ulang jika cuci tangan sudah terekam.
        """
        with self.lock:
            ps = self._get_person(person_id)
            ps.state                 = GroupState.CARRYING
            ps.carrying_time         = time.time()
            ps.confidence            = confidence
            ps.last_frame            = frame
            ps.last_camera_id        = camera_id
            ps.instrumen_terdeteksi  = True   # tandai bahwa instrumen benar-benar terdeteksi

            if ps.finalized:
                return

            if ps.wash_time > 0 and (time.time() - ps.wash_time) <= self.window_seconds:
                self._finalize_status("patuh", frame if frame is not None else ps.last_frame, camera_id, person_id, ps)

    def report_hand_wash(self, camera_id: int, person_id: str, frame=None):
        """
        Dipanggil saat seseorang terdeteksi di zona cuci tangan (setelah dwell time).
        Simpan wash_time dan finalisasi status PATUH jika belum selesai.
        """
        with self.lock:
            ps = self._get_person(person_id)
            now = time.time()
            ps.wash_time = now

            # Simpan frame sebagai fallback snapshot
            if frame is not None and ps.last_frame is None:
                ps.last_frame = frame

            if ps.finalized:
                return

            if ps.state in (GroupState.IDLE, GroupState.CARRYING):
                ps.state = GroupState.HAND_WASHED

            if ps.instrumen_terdeteksi:
                if (now - ps.carrying_time) > self.window_seconds:
                    # Instrumen sudah kadaluarsa sebelum cuci tangan → langsung TIDAK PATUH
                    self._finalize_status("tidak_patuh", frame if frame is not None else ps.last_frame, camera_id, person_id, ps)
                    return

            # Jika cuci tangan sudah terjadi, langsung laporkan PATUH
            self._finalize_status("patuh", frame if frame is not None else ps.last_frame, camera_id, person_id, ps)

    def report_door_entry(self, camera_id: int, person_id: str, frame):
        """
        Deprecated: evaluasi kepatuhan tidak lagi bergantung pada zona pintu.
        Jika metode ini terpaksa dipanggil, hanya flush state kadaluarsa.
        """
        with self.lock:
            self._cleanup_expired()

    # ─── Internal ────────────────────────────────────────────────────────────

    def _finalize_status(self, status: str, frame, trigger_camera_id: int, person_id: str, ps: PersonState):
        """Selesaikan siklus compliance dan panggil event callback."""
        if ps.finalized:
            return

        ps.finalized = True
        self.person_status[person_id] = (status, time.time())

        if self.on_event:
            self.on_event({
                "group_id":              self.group_id,
                "person_id":             person_id,
                "camera_id":             trigger_camera_id,
                "status":                status,
                "membawa_instrumen":     ps.instrumen_terdeteksi,
                "aktivitas_cuci_tangan": (status == "patuh"),
                "confidence":            ps.confidence,
            }, frame)

    def _fire_event(self, status: str, frame, trigger_camera_id: int, person_id: str, ps: PersonState):
        """Simpan status akhir dan panggil callback on_event."""
        self.person_status[person_id] = (status, time.time())

        if self.on_event:
            self.on_event({
                "group_id":              self.group_id,
                "person_id":             person_id,
                "camera_id":             trigger_camera_id,
                "status":                status,
                "membawa_instrumen":     ps.instrumen_terdeteksi,  # True hanya jika instrumen benar-benar terdeteksi
                "aktivitas_cuci_tangan": (status == "patuh"),
                "confidence":            ps.confidence,
            }, frame)

    def get_person_status(self, person_id: str) -> str | None:
        """
        Kembalikan status final orang ini (patuh/tidak_patuh) untuk label di video.
        Status kadaluarsa setelah 30 detik (cukup lama untuk melewati zona pintu).
        """
        with self.lock:
            if person_id in self.person_status:
                status, ts = self.person_status[person_id]
                if time.time() - ts < 30.0:
                    return status
                else:
                    del self.person_status[person_id]
            return None

    def get_engine_state(self, person_id: str) -> str | None:
        """
        Kembalikan state internal engine untuk person_id (idle/carrying/hand_washed).
        Digunakan untuk menampilkan state sementara di label video.
        """
        with self.lock:
            if person_id not in self._person_states:
                return None
            ps = self._person_states[person_id]
            now = time.time()
            # Jika window sudah expire, anggap idle
            if ps.state != GroupState.IDLE and (now - ps.carrying_time) > self.window_seconds:
                return None
            return ps.state.value  # "idle" | "carrying" | "hand_washed"
