import os
import sys
import argparse
from pathlib import Path

# Pastikan path dataset benar
BASE_DIR = Path(__file__).parent.parent
DATA_YAML = BASE_DIR / "dataset" / "data.yaml"
MODELS_DIR = BASE_DIR / "models"
MODELS_DIR.mkdir(exist_ok=True)

IMAGES_TRAIN = BASE_DIR / "dataset" / "images" / "train"
IMAGES_VAL   = BASE_DIR / "dataset" / "images" / "val"
RUN_DIR      = MODELS_DIR / "handwash_v1"
LAST_CKPT    = RUN_DIR / "weights" / "last.pt"

# Checkpoint penuh (~24MB) punya state optimizer; best/last weights (~6MB) tidak bisa resume
FULL_CKPT_MIN_BYTES = 15_000_000


def count_dataset_images():
    """Hitung jumlah gambar train/val (.jpg + .png)."""
    exts = ("*.jpg", "*.png")
    train = sum(len(list(IMAGES_TRAIN.glob(ext))) for ext in exts)
    val   = sum(len(list(IMAGES_VAL.glob(ext))) for ext in exts)
    return train, val


def is_full_checkpoint(path: Path) -> bool:
    return path.exists() and path.stat().st_size >= FULL_CKPT_MIN_BYTES


def find_resume_checkpoint() -> Path | None:
    """Cari checkpoint penuh untuk resume (last.pt atau epoch*.pt terbaru)."""
    if is_full_checkpoint(LAST_CKPT):
        return LAST_CKPT
    epoch_ckpts = sorted(
        (RUN_DIR / "weights").glob("epoch*.pt"),
        key=lambda p: int(p.stem.replace("epoch", "")),
        reverse=True,
    )
    for ckpt in epoch_ckpts:
        if is_full_checkpoint(ckpt):
            return ckpt
    return None


def train(epochs=100, batch=16, resume=False):
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

    # DataLoader workers sering crash di Windows
    if sys.platform == "win32":
        CONFIG["workers"] = 0

    print("=" * 60)
    print("  YOLOv8n Fine-tuning")
    print("  Dataset:", DATA_YAML)
    print("  Epochs :", epochs)
    print("  Batch  :", batch)
    print("=" * 60)

    resume_ckpt = find_resume_checkpoint() if resume else None
    if resume and resume_ckpt:
        print(f"[INFO] Resume training dari checkpoint penuh: {resume_ckpt}")
        model = YOLO(str(resume_ckpt))
        results = model.train(resume=True)
    else:
        if resume:
            print("[WARN] Tidak ada checkpoint penuh untuk resume.")
            print("       Training baru dengan dataset/data.yaml.")
        elif LAST_CKPT.exists() or list((RUN_DIR / "weights").glob("epoch*.pt")):
            print("[INFO] Checkpoint lama ditemukan, mulai training baru (pakai --resume untuk lanjut).")
        else:
            print("[INFO] Mulai dari YOLOv8n pre-trained COCO")
        model = YOLO("yolov8n.pt")
        results = model.train(**CONFIG)

    # Salin best.pt ke models/
    best_src = MODELS_DIR / "handwash_v1" / "weights" / "best.pt"
    best_dst = MODELS_DIR / "best.pt"
    if best_src.exists():
        import shutil
        shutil.copy2(best_src, best_dst)
        print(f"\n[SUCCESS] Model terbaik disimpan: {best_dst}")

    n_train, n_val = count_dataset_images()
    n_total = n_train + n_val
    map50 = results.results_dict.get("metrics/mAP50(B)", 0.0)
    map50_95 = results.results_dict.get("metrics/mAP50-95(B)", 0.0)

    print("\n" + "=" * 60)
    print("  TRAINING SELESAI")
    print("=" * 60)
    print(f"  Train images : {n_train}")
    print(f"  Val images   : {n_val}")
    print(f"  Total        : {n_total} gambar")
    print(f"  Epochs       : {epochs}")
    print(f"  mAP50        : {map50:.4f}")
    print(f"  mAP50-95     : {map50_95:.4f}")
    print("=" * 60)
    print(f"\n[SUCCESS] {n_total} gambar berhasil ditraining!")

    return results


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Train YOLOv8 model")
    parser.add_argument("--epochs", type=int, default=100, help="Number of training epochs")
    parser.add_argument("--batch", type=int, default=16, help="Batch size")
    parser.add_argument(
        "--resume",
        action="store_true",
        help="Lanjutkan training dari checkpoint penuh (last.pt ~24MB, bukan best weights)",
    )
    args = parser.parse_args()

    # Cek dataset tersedia
    if not DATA_YAML.exists():
        print("[ERROR] data.yaml tidak ditemukan!")
        print("   Jalankan dulu: python training/prepare_dataset.py")
        sys.exit(1)

    n_train, n_val = count_dataset_images()
    if n_train == 0:
        print("[ERROR] Tidak ada gambar training!")
        print("   Jalankan dulu: python training/prepare_dataset.py")
        sys.exit(1)

    print(f"[INFO] Dataset: {n_train} train + {n_val} val = {n_train + n_val} gambar")
    train(epochs=args.epochs, batch=args.batch, resume=args.resume)
