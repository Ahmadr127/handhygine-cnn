"""
core/group_compliance.py — State machine kepatuhan di level Grup Monitoring

Perubahan v2:
- Tracking per-person menggunakan person_states dict (fix race condition multi-orang)
- report_hand_wash menerima person_id (fix Bug #1)
- Cuci tangan disimpan meskipun state IDLE / CARRYING (fix Bug #3 - pre-emptive wash window)
- report_door_entry menerima person_id dan evaluasi per-orang (fix Bug #2 & #4)
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
    __slots__ = ("state", "carrying_time", "wash_time", "confidence", "last_frame", "last_camera_id", "instrumen_terdeteksi")

    def __init__(self):
        self.state: GroupState        = GroupState.IDLE
        self.carrying_time: float     = 0.0
        self.wash_time: float         = 0.0   # dapat diset bahkan saat IDLE (pre-emptive)
        self.confidence: float        = 0.0
        self.last_frame               = None
        self.last_camera_id: int      = -1
        self.instrumen_terdeteksi: bool = False   # True jika report_instrument pernah dipanggil


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
        """Hapus entry person yang sudah lama expire (> 2x window)."""
        now = time.time()
        expired = [
            pid for pid, ps in self._person_states.items()
            if ps.state == GroupState.IDLE and (now - ps.carrying_time) > self.window_seconds * 2
            and (now - ps.wash_time) > self.window_seconds * 2
        ]
        for pid in expired:
            del self._person_states[pid]

    # ─── Public API ──────────────────────────────────────────────────────────

    def report_instrument(self, camera_id: int, person_id: str, confidence: float, frame):
        """
        Dipanggil saat seseorang terdeteksi membawa instrumen medis.
        Selalu update carrying_time dan set state CARRYING.
        """
        with self.lock:
            ps = self._get_person(person_id)
            ps.state                 = GroupState.CARRYING
            ps.carrying_time         = time.time()
            ps.confidence            = confidence
            ps.last_frame            = frame
            ps.last_camera_id        = camera_id
            ps.instrumen_terdeteksi  = True   # tandai bahwa instrumen benar-benar terdeteksi

    def report_hand_wash(self, camera_id: int, person_id: str, frame=None):
        """
        Dipanggil saat seseorang terdeteksi di zona cuci tangan (setelah dwell time).
        Simpan wash_time dan naikkan state. Bisa dipanggil sebelum atau sesudah instrumen.
        """
        with self.lock:
            ps = self._get_person(person_id)
            ps.wash_time = time.time()

            # Simpan frame sebagai fallback snapshot
            if frame is not None and ps.last_frame is None:
                ps.last_frame = frame

            # Update state: HAND_WASHED dari state apapun
            if ps.state in (GroupState.IDLE, GroupState.CARRYING):
                ps.state = GroupState.HAND_WASHED

    def report_door_entry(self, camera_id: int, person_id: str, frame):
        """
        Dipanggil saat seseorang terdeteksi di zona pintu.

        Logika kepatuhan:
          - Instrumen WAJIB terdeteksi (syarat perawat) dalam window
          - Cuci tangan WAJIB ada dalam window (urutan bebas vs instrumen)
          - Jika keduanya ada → PATUH
          - Jika instrumen ada tapi tidak cuci tangan → TIDAK PATUH
          - Jika instrumen tidak ada → abaikan (bukan perawat)
        """
        with self.lock:
            now = time.time()

            if person_id not in self._person_states:
                return

            ps = self._person_states[person_id]

            # SYARAT UTAMA: instrumen harus terdeteksi (pembeda perawat vs bukan perawat)
            if not ps.instrumen_terdeteksi:
                return

            # Hitung window dari event pertama (instrumen atau cuci tangan, mana lebih awal)
            first_event = min(
                ps.carrying_time if ps.carrying_time > 0 else float('inf'),
                ps.wash_time     if ps.wash_time > 0     else float('inf'),
            )
            if first_event == float('inf') or (now - first_event) > self.window_seconds:
                # Session expire → reset
                ps.state              = GroupState.IDLE
                ps.instrumen_terdeteksi = False
                ps.carrying_time      = 0.0
                ps.wash_time          = 0.0
                return

            # Cek apakah cuci tangan dilakukan dalam window
            wash_is_recent = (
                ps.wash_time > 0
                and (now - ps.wash_time) <= self.window_seconds
            )

            status = "patuh" if wash_is_recent else "tidak_patuh"

            entry_frame = frame if frame is not None else ps.last_frame
            self._fire_event(status, entry_frame, camera_id, person_id, ps)

            # Setelah evaluasi: reset HANYA instrumen agar siklus baru bisa dimulai nanti.
            # wash_time dan carrying_time TIDAK direset agar jika zona pintu ter-trigger lagi
            # dalam window yang sama (misal orang masih berdiri di pintu), hasilnya tetap konsisten.
            # Kedua field akan expire sendiri setelah window_seconds (3 menit).
            ps.state                = GroupState.IDLE
            ps.instrumen_terdeteksi = False
            # carrying_time dan wash_time dibiarkan → evaluasi ulang dalam window tetap akurat

            self._cleanup_expired()

    # ─── Internal ────────────────────────────────────────────────────────────

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
