"""
training/train.py
==================
Fine-tune YOLOv8n untuk sistem monitoring kepatuhan cuci tangan.

Jalankan: python training/train.py [--epochs EPOCHS] [--batch BATCH]
"""
import os
import sys
import argparse
from pathlib import Path

# Pastikan path dataset benar
BASE_DIR = Path(__file__).parent.parent
DATA_YAML = BASE_DIR / "dataset" / "data.yaml"
MODELS_DIR = BASE_DIR / "models"
MODELS_DIR.mkdir(exist_ok=True)


def train(epochs=100, batch=16):
    from ultralytics import YOLO

    # ─── Konfigurasi training ───────────────────────────────────────────
    CONFIG = {
        "data":      str(DATA_YAML),
        "epochs":    epochs,
        "imgsz":     640,
        "batch":     batch,         # Kurangi jika OOM (CPU/GPU lemah)
        "patience":  20,         # Early stopping
        "project":   str(MODELS_DIR),
        "name":      "handwash_v1",
        "exist_ok":  True,
        "pretrained": True,      # Mulai dari COCO pre-trained
        "optimizer": "AdamW",
        "lr0":       0.001,
        "lrf":       0.01,
        "momentum":  0.937,
        "weight_decay": 0.0005,
        "warmup_epochs": 3,
        "augment":   True,
        "degrees":   10.0,       # Rotasi ringan (sudut kamera CCTV)
        "flipud":    0.0,        # Jangan flip vertikal
        "fliplr":    0.5,
        "hsv_h":     0.015,
        "hsv_s":     0.7,
        "hsv_v":     0.4,
        "mosaic":    1.0,
        "mixup":     0.1,
        "verbose":   True,
        "save":      True,
        "save_period": 10,       # Simpan checkpoint setiap 10 epoch
    }

    print("=" * 60)
    print("  YOLOv8n Fine-tuning")
    print("  Dataset:", DATA_YAML)
    print("  Epochs :", epochs)
    print("  Batch  :", batch)
    print("=" * 60)

    # Cek apakah ada model sebelumnya untuk resume
    last_model = MODELS_DIR / "handwash_v1" / "weights" / "last.pt"
    is_resume = False
    if last_model.exists():
        print(f"[INFO] Resume dari checkpoint: {last_model}")
        model = YOLO(str(last_model))
        is_resume = True
    else:
        print("[INFO] Mulai dari YOLOv8n pre-trained COCO")
        model = YOLO("yolov8n.pt")

    # Training
    if is_resume:
        results = model.train(resume=True)
    else:
        results = model.train(**CONFIG)

    # Salin best.pt ke models/
    best_src = MODELS_DIR / "handwash_v1" / "weights" / "best.pt"
    best_dst = MODELS_DIR / "best.pt"
    if best_src.exists():
        import shutil
        shutil.copy2(best_src, best_dst)
        print(f"\n[SUCCESS] Model terbaik disimpan: {best_dst}")

    print("\n[INFO] Hasil training:")
    print(f"  mAP50   : {results.results_dict.get('metrics/mAP50(B)', 0.0):.4f}")
    print(f"  mAP50-95: {results.results_dict.get('metrics/mAP50-95(B)', 0.0):.4f}")

    return results


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Train YOLOv8 model")
    parser.add_argument("--epochs", type=int, default=100, help="Number of training epochs")
    parser.add_argument("--batch", type=int, default=16, help="Batch size")
    args = parser.parse_args()

    # Cek dataset tersedia
    if not DATA_YAML.exists():
        print("[ERROR] data.yaml tidak ditemukan!")
        print("   Jalankan dulu: python training/prepare_dataset.py")
        sys.exit(1)

    # Cek apakah ada gambar
    train_imgs = list((BASE_DIR / "dataset" / "images" / "train").glob("*.jpg"))
    if len(train_imgs) == 0:
        print("[ERROR] Tidak ada gambar training!")
        print("   Jalankan dulu: python training/prepare_dataset.py")
        sys.exit(1)

    print(f"[INFO] Dataset: {len(train_imgs)} gambar training")
    train(epochs=args.epochs, batch=args.batch)
