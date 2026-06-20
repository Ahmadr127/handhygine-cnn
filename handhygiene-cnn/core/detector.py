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
    Menggunakan dua model sekaligus:
    1. yolov8n.pt untuk deteksi orang (tenaga kesehatan)
    2. best.pt untuk deteksi instrumen medis (baki, troli, dsb)
    """

    def __init__(self):
        # 1. Load model khusus (best.pt) untuk instrumen
        model_path = os.path.abspath(
            os.path.join(os.path.dirname(__file__), "..", MODEL_PATH)
        )
        if os.path.exists(model_path):
            print(f"[Detector] Load model custom: {model_path}")
            self.model_custom = YOLO(model_path)
        else:
            print(f"[Detector] best.pt tidak ditemukan, fungsi custom mati.")
            self.model_custom = None

        # 2. Load model bawaan (yolov8n.pt) khusus untuk mendeteksi 'person' (class 0)
        print(f"[Detector] Load model person: {FALLBACK_MODEL}")
        self.model_person = YOLO(FALLBACK_MODEL)

        self.conf = DETECTION_CONFIDENCE
        self._class_names = CLASS_NAMES

    def detect(self, frame: np.ndarray) -> sv.Detections:
        # Deteksi orang (class 0) dari model standar
        res_person = self.model_person(frame, conf=self.conf, classes=[0], verbose=False)[0]
        det_person = sv.Detections.from_ultralytics(res_person)

        # Deteksi alat medis dari model training
        if self.model_custom:
            res_custom = self.model_custom(frame, conf=self.conf, verbose=False)[0]
            det_custom = sv.Detections.from_ultralytics(res_custom)
            
            # Mapping class ID: Karena model best.pt di-training dengan 1 class (baki=0),
            # kita ubah ID-nya menjadi 1 agar sesuai dengan config dan tidak bentrok dengan person (0).
            if len(det_custom) > 0:
                det_custom.class_id = np.full_like(det_custom.class_id, 1)
            
            # Gabungkan hasil deteksi
            if len(det_person) > 0 and len(det_custom) > 0:
                detections = sv.Detections.merge([det_person, det_custom])
            elif len(det_custom) > 0:
                detections = det_custom
            else:
                detections = det_person
        else:
            detections = det_person

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
