"""
core/detector.py — Wrapper YOLOv8 untuk deteksi objek
"""
import os
import numpy as np
import supervision as sv
from ultralytics import YOLO
from config import MODEL_PATH, FALLBACK_MODEL, DETECTION_CONFIDENCE, CLASS_NAMES


class Detector:
    """
    Wrapper YOLOv8.
    Otomatis fallback ke yolov8n.pt (COCO) jika best.pt belum ada.
    """

    def __init__(self):
        model_path = os.path.abspath(
            os.path.join(os.path.dirname(__file__), MODEL_PATH)
        )
        if os.path.exists(model_path):
            print(f"[Detector] Load model: {model_path}")
            self.model = YOLO(model_path)
        else:
            print(f"[Detector] best.pt tidak ditemukan, fallback ke {FALLBACK_MODEL}")
            self.model = YOLO(FALLBACK_MODEL)

        self.conf = DETECTION_CONFIDENCE
        self._class_names = CLASS_NAMES

    def detect(self, frame: np.ndarray) -> sv.Detections:
        """
        Jalankan deteksi pada frame.

        Args:
            frame: BGR numpy array dari OpenCV

        Returns:
            supervision.Detections (xyxy, confidence, class_id)
        """
        results = self.model(frame, conf=self.conf, verbose=False)[0]
        detections = sv.Detections.from_ultralytics(results)
        return detections

    def get_class_name(self, class_id: int) -> str:
        """Kembalikan nama kelas berdasarkan index."""
        # Kalau pakai model pre-trained COCO, map class 0 (person) → tenaga_kesehatan
        if class_id == 0 and 0 not in self._class_names:
            return "tenaga_kesehatan"
        return self._class_names.get(class_id, f"class_{class_id}")

    def filter_by_classes(self, detections: sv.Detections, class_ids: set) -> sv.Detections:
        """Filter deteksi hanya untuk class_id tertentu."""
        if len(detections) == 0:
            return detections
        mask = np.isin(detections.class_id, list(class_ids))
        return detections[mask]
