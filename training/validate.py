"""
training/validate.py
=====================
Validasi dan benchmark model YOLO yang sudah ditraining.

Jalankan: python training/validate.py
"""
from pathlib import Path
from ultralytics import YOLO

BASE_DIR   = Path(__file__).parent.parent
DATA_YAML  = BASE_DIR / "dataset" / "data.yaml"
MODELS_DIR = BASE_DIR / "models"
MODEL_PATH = MODELS_DIR / "best.pt"


def validate():
    if not MODEL_PATH.exists():
        print(f"[ERROR] Model tidak ditemukan: {MODEL_PATH}")
        print("   Jalankan dulu: python training/train.py")
        return

    print(f"[Validate] Model: {MODEL_PATH}")
    model = YOLO(str(MODEL_PATH))

    # Validasi pada dataset val
    metrics = model.val(data=str(DATA_YAML), imgsz=640, verbose=True)

    print("\n" + "=" * 50)
    print("Validation Metrics")
    print("=" * 50)
    print(f"  mAP50   : {metrics.box.map50:.4f}")
    print(f"  mAP50-95: {metrics.box.map:.4f}")
    print(f"  Precision: {metrics.box.mp:.4f}")
    print(f"  Recall   : {metrics.box.mr:.4f}")
    print("=" * 50)

    # Per-class results
    print("\n[INFO] Per-class mAP50:")
    class_names = ["tenaga_kesehatan", "baki_medis", "troli_medis",
                   "wastafel", "hand_sanitizer", "pintu_masuk"]
    for i, name in enumerate(class_names):
        if i < len(metrics.box.maps):
            print(f"  {name:20s}: {metrics.box.maps[i]:.4f}")

    return metrics


def benchmark_speed():
    """Test kecepatan inference."""
    import cv2, time, numpy as np

    print("\n[INFO] Benchmark kecepatan inference...")
    model = YOLO(str(MODEL_PATH))
    dummy = np.zeros((640, 640, 3), dtype=np.uint8)

    # Warmup
    for _ in range(3):
        model(dummy, verbose=False)

    # Benchmark
    times = []
    for _ in range(50):
        t0 = time.perf_counter()
        model(dummy, verbose=False)
        times.append((time.perf_counter() - t0) * 1000)

    avg = sum(times) / len(times)
    fps = 1000 / avg
    print(f"  Avg inference: {avg:.1f} ms/frame")
    print(f"  Theoretical FPS: {fps:.1f}")


if __name__ == "__main__":
    validate()
    benchmark_speed()
