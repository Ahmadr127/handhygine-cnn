"""
export_onnx.py — Export model .pt ke ONNX untuk DirectML GPU inference.
Jalankan SEKALI:
  python export_onnx.py
"""
import os
import sys

print("=" * 55)
print("  Hand Hygiene AI — Export ke ONNX (untuk GPU)")
print("=" * 55)

try:
    from ultralytics import YOLO
except ImportError:
    print("  ❌ ultralytics tidak ditemukan.")
    sys.exit(1)

# Pre-install onnx & onnxslim agar Ultralytics tidak auto-install onnxruntime (CPU)
# yang akan menimpa onnxruntime-directml (GPU AMD)
import subprocess
print("  📦 Memastikan onnx & onnxslim terinstall ...")
subprocess.run([sys.executable, "-m", "pip", "install", "-q",
                "onnx>=1.12.0,<2.0.0", "onnxslim>=0.1.82"], check=False)
print("  ✅ Dependencies siap.")
print()

BASE_DIR = os.path.dirname(__file__)

models_to_export = [
    (os.path.abspath(os.path.join(BASE_DIR, "../models/best.pt")), "best.pt (custom)"),
    (os.path.abspath(os.path.join(BASE_DIR, "yolov8n.pt")),        "yolov8n.pt (person)"),
]

for pt_path, label in models_to_export:
    if not os.path.exists(pt_path):
        print(f"  ⚠  Skip {label}: file tidak ditemukan ({pt_path})")
        continue
    onnx_path = os.path.splitext(pt_path)[0] + ".onnx"
    if os.path.exists(onnx_path):
        print(f"  ✅ {label}: sudah ada ({onnx_path}), skip.")
        continue
    print(f"  ⏳ Export {label} → ONNX ...")
    model = YOLO(pt_path)
    model.export(format="onnx", imgsz=640, simplify=True, opset=12)
    print(f"  ✅ {label} selesai.")

print()
print("  ✅ Semua model siap. Jalankan service seperti biasa:")
print("  uvicorn main:app --host 0.0.0.0 --port 8001 --reload")
