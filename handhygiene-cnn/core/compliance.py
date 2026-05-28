"""
core/compliance.py — State machine kepatuhan cuci tangan per person_id

State transitions:
  MONITORING          → default, belum ada instrumen terdeteksi
  CARRYING_INSTRUMENT → terdeteksi bawa baki/troli (instrumen medis)
  HAND_WASH_ZONE      → masuk zona sanitizer/wastafel sambil bawa instrumen
  COMPLIANT           → setelah cuci tangan → masuk zona pintu  → LOG + SNAPSHOT
  NON_COMPLIANT       → langsung ke pintu tanpa cuci tangan    → LOG + SNAPSHOT
"""
import time
from dataclasses import dataclass, field
from enum import Enum
from config import TRACK_RESET_SECONDS


class ComplianceState(str, Enum):
    MONITORING          = "monitoring"
    CARRYING_INSTRUMENT = "carrying_instrument"
    HAND_WASH_ZONE      = "hand_wash_zone"
    COMPLIANT           = "patuh"
    NON_COMPLIANT       = "tidak_patuh"


@dataclass
class PersonState:
    tracker_id: int
    camera_id: int
    state: ComplianceState = ComplianceState.MONITORING
    membawa_instrumen: bool = False
    aktivitas_cuci_tangan: bool = False
    last_seen: float = field(default_factory=time.time)
    last_bbox: list = field(default_factory=list)
    confidence: float = 0.0
    # Sudah di-log? (hindari duplikat log)
    logged: bool = False


class ComplianceEngine:
    """
    Mengelola state machine semua orang yang sedang di-track.
    Dipanggil setiap frame dari CameraProcessor.
    """

    def __init__(self, camera_id: int, on_event=None):
        """
        Args:
            camera_id: ID kamera dari database
            on_event: callback(person_state, frame) saat COMPLIANT/NON_COMPLIANT
        """
        self.camera_id = camera_id
        self.on_event = on_event  # callback untuk simpan DB + snapshot
        self._states: dict[int, PersonState] = {}  # tracker_id → PersonState

    def update(
        self,
        tracker_id: int,
        bbox: list,
        confidence: float,
        in_instrument_zone: bool,
        in_handwash_zone: bool,
        in_door_zone: bool,
        frame=None,
    ) -> ComplianceState:
        """
        Update state untuk satu person berdasarkan deteksi frame ini.

        Args:
            tracker_id      : ID unik dari ByteTrack
            bbox            : [x1, y1, x2, y2]
            confidence      : confidence score deteksi person
            in_instrument_zone: apakah berdekatan/membawa instrumen
            in_handwash_zone: apakah berada di zona cuci tangan
            in_door_zone    : apakah berada di zona pintu
            frame           : BGR frame untuk snapshot

        Returns:
            State saat ini setelah update
        """
        now = time.time()

        if tracker_id not in self._states:
            self._states[tracker_id] = PersonState(
                tracker_id=tracker_id,
                camera_id=self.camera_id,
            )

        ps = self._states[tracker_id]
        ps.last_seen = now
        ps.last_bbox = bbox
        ps.confidence = confidence

        # ── Transisi state ────────────────────────────────────────────────
        if ps.logged:
            # Sudah di-log, tunggu reset (orang keluar frame)
            return ps.state

        match ps.state:
            case ComplianceState.MONITORING:
                if in_instrument_zone:
                    ps.membawa_instrumen = True
                    ps.state = ComplianceState.CARRYING_INSTRUMENT

            case ComplianceState.CARRYING_INSTRUMENT:
                if in_handwash_zone:
                    ps.aktivitas_cuci_tangan = True
                    ps.state = ComplianceState.HAND_WASH_ZONE
                elif in_door_zone:
                    # Langsung masuk pintu → TIDAK PATUH
                    ps.state = ComplianceState.NON_COMPLIANT
                    self._fire_event(ps, frame)

            case ComplianceState.HAND_WASH_ZONE:
                if in_door_zone:
                    # Setelah cuci tangan → masuk pintu → PATUH
                    ps.state = ComplianceState.COMPLIANT
                    self._fire_event(ps, frame)
                elif not in_handwash_zone:
                    # Keluar zona cuci tangan tapi belum ke pintu → tetap carry
                    ps.state = ComplianceState.CARRYING_INSTRUMENT

        return ps.state

    def _fire_event(self, ps: PersonState, frame):
        """Panggil callback on_event untuk log + snapshot."""
        ps.logged = True
        if self.on_event and frame is not None:
            self.on_event(ps, frame)

    def cleanup_stale(self):
        """
        Hapus state person yang sudah lama tidak terdeteksi.
        Dipanggil setiap beberapa detik.
        """
        now = time.time()
        stale_ids = [
            tid for tid, ps in self._states.items()
            if now - ps.last_seen > TRACK_RESET_SECONDS
        ]
        for tid in stale_ids:
            del self._states[tid]
        if stale_ids:
            print(f"[Compliance] Kamera {self.camera_id}: reset {len(stale_ids)} track stale")

    def get_all_states(self) -> dict[int, PersonState]:
        return self._states

    def get_state(self, tracker_id: int) -> ComplianceState | None:
        ps = self._states.get(tracker_id)
        return ps.state if ps else None
