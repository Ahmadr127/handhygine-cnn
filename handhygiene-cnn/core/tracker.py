"""
core/tracker.py — ByteTrack wrapper via supervision
"""
import numpy as np
import supervision as sv
from config import CLASS_PERSON


class Tracker:
    """
    ByteTrack object tracker menggunakan supervision.ByteTrack.
    Memberikan tracker_id unik yang persisten antar frame.
    """

    def __init__(self):
        self.byte_track = sv.ByteTrack(
            track_activation_threshold=0.35,
            lost_track_buffer=30,
            minimum_matching_threshold=0.8,
            frame_rate=15,
        )

    def update(self, detections: sv.Detections) -> sv.Detections:
        """
        Update tracker dengan deteksi frame saat ini.

        Returns:
            Detections dengan tracker_id yang sudah diassign.
        """
        if len(detections) == 0:
            return detections
        return self.byte_track.update_with_detections(detections)

    def reset(self):
        """Reset semua track (misal saat kamera restart)."""
        self.byte_track.reset()

    @staticmethod
    def get_center(bbox_xyxy: np.ndarray) -> tuple[float, float]:
        """Hitung titik tengah bounding box."""
        x1, y1, x2, y2 = bbox_xyxy
        return ((x1 + x2) / 2, (y1 + y2) / 2)

    @staticmethod
    def get_bottom_center(bbox_xyxy: np.ndarray) -> tuple[float, float]:
        """
        Titik bawah tengah bounding box (lebih akurat untuk posisi berdiri).
        """
        x1, y1, x2, y2 = bbox_xyxy
        return ((x1 + x2) / 2, y2)
