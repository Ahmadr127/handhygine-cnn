"""
core/group_compliance.py — State machine kepatuhan di level Grup Monitoring
"""
import time
from enum import Enum
import threading

class GroupState(str, Enum):
    IDLE = "idle"
    CARRYING = "carrying"
    HAND_WASHED = "hand_washed"

class GroupComplianceEngine:
    """
    Satu engine per group_id. Menerima event dari semua kamera dalam grup.
    Menerapkan logika berbasis waktu:
    Dalam N detik sejak terdeteksi instrumen, jika ada event cuci tangan dan event masuk pintu,
    maka ditentukan status kepatuhan grup.
    """
    def __init__(self, group_id: int, on_event=None):
        self.group_id = group_id
        self.on_event = on_event
        self.lock = threading.Lock()
        
        self.state = GroupState.IDLE
        self.last_carrying_time = 0.0
        self.last_wash_time = 0.0
        self.window_seconds = 60.0
        
        # Data snapshot sementara untuk dikirim saat event
        self.last_person_id = ""
        self.last_camera_id = None
        self.last_frame = None
        self.last_confidence = 0.0

    def report_instrument(self, camera_id: int, person_id: str, confidence: float, frame):
        with self.lock:
            now = time.time()
            self.state = GroupState.CARRYING
            self.last_carrying_time = now
            self.last_person_id = person_id
            self.last_camera_id = camera_id
            self.last_frame = frame
            self.last_confidence = confidence

    def report_hand_wash(self, camera_id: int):
        with self.lock:
            now = time.time()
            if self.state in (GroupState.CARRYING, GroupState.HAND_WASHED) and (now - self.last_carrying_time <= self.window_seconds):
                self.state = GroupState.HAND_WASHED
                self.last_wash_time = now

    def report_door_entry(self, camera_id: int, frame):
        with self.lock:
            now = time.time()
            if now - self.last_carrying_time > self.window_seconds:
                # Event basi / tidak valid
                self.state = GroupState.IDLE
                return

            if self.state == GroupState.HAND_WASHED:
                # Patuh
                self._fire_event("patuh", frame if frame is not None else self.last_frame, camera_id)
            elif self.state == GroupState.CARRYING:
                # Tidak Patuh
                self._fire_event("tidak_patuh", frame if frame is not None else self.last_frame, camera_id)
            
            # Reset
            self.state = GroupState.IDLE

    def _fire_event(self, status: str, frame, trigger_camera_id: int):
        if self.on_event:
            self.on_event({
                "group_id": self.group_id,
                "person_id": self.last_person_id,
                "camera_id": trigger_camera_id, # Kamera yang trigger event akhir
                "status": status,
                "membawa_instrumen": True,
                "aktivitas_cuci_tangan": (status == "patuh"),
                "confidence": self.last_confidence
            }, frame)
