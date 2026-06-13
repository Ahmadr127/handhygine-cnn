"""
training/prepare_dataset.py
============================
Script untuk menyiapkan dataset unified.
Sekarang HANYA melatih benda bergerak (Baki Medis & Troli Medis).
Benda statis (pintu, wastafel, sanitizer) ditangani oleh sistem Zona.

Kelas final (data.yaml):
  0: tenaga_kesehatan  ← COCO pre-trained (class person)
  1: baki_medis        ← tray_dataset_raw
  2: troli_medis       ← dataset tambahan
  3: wastafel          ← (Tidak dilatih lagi)
  4: hand_sanitizer    ← (Tidak dilatih lagi)
  5: pintu_masuk       ← (Tidak dilatih lagi)

Jalankan: python training/prepare_dataset.py
"""
import os
import shutil
import glob
from pathlib import Path

# ─── Path ──────────────────────────────────────────────────────────────────
BASE_DIR    = Path(__file__).parent.parent
DATASET_OUT = BASE_DIR / "dataset"
IMAGES_TRAIN = DATASET_OUT / "images" / "train"
IMAGES_VAL   = DATASET_OUT / "images" / "val"
LABELS_TRAIN = DATASET_OUT / "labels" / "train"
LABELS_VAL   = DATASET_OUT / "labels" / "val"

# Sumber dataset
TRAY_SRC      = BASE_DIR / "tray_dataset_raw"
ROBOFLOW_TRAY_SRC = BASE_DIR / "bukan sistem" / "roboflow" / "Medical Tray.v1i.yolov8"

VAL_SPLIT = 0.2  # 20% untuk validasi

# ─── Mapping kelas ─────────────────────────────────────────────────────────
TRAY_REMAP = {0: 1}  # 0 -> baki_medis (1)

def ensure_dirs():
    for d in [IMAGES_TRAIN, IMAGES_VAL, LABELS_TRAIN, LABELS_VAL]:
        d.mkdir(parents=True, exist_ok=True)

def remap_label_file(src_label: Path, dst_label: Path, remap: dict):
    lines_out = []
    with open(src_label, "r") as f:
        for line in f:
            line = line.strip()
            if not line: continue
            parts = line.split()
            class_id = int(parts[0])
            new_id = remap.get(class_id, class_id)
            parts[0] = str(new_id)
            lines_out.append(" ".join(parts))
    with open(dst_label, "w") as f:
        f.write("\n".join(lines_out))

def copy_dataset_split(src_dir: Path, split: str, remap: dict | None = None):
    img_src = src_dir / split / "images"
    lbl_src = src_dir / split / "labels"

    if not img_src.exists():
        return 0

    images = list(img_src.glob("*.jpg")) + list(img_src.glob("*.png"))
    count = 0

    for img_path in images:
        stem = img_path.stem
        label_path = lbl_src / f"{stem}.txt"

        is_val = (abs(hash(stem)) % 10) < int(VAL_SPLIT * 10)
        out_img_dir = IMAGES_VAL if is_val else IMAGES_TRAIN
        out_lbl_dir = LABELS_VAL if is_val else LABELS_TRAIN

        shutil.copy2(img_path, out_img_dir / img_path.name)

        if label_path.exists():
            dst_label = out_lbl_dir / f"{stem}.txt"
            if remap:
                remap_label_file(label_path, dst_label, remap)
            else:
                shutil.copy2(label_path, dst_label)

        count += 1

    return count

def process_tray_dataset():
    lbl_dir = TRAY_SRC / "labels"
    if not lbl_dir.exists():
        print(f"[WARNING] {TRAY_SRC.name} belum ada labelnya.")
        return 0

    images = list(TRAY_SRC.glob("*.jpg")) + list(TRAY_SRC.glob("*.png"))
    count = 0
    for img_path in images:
        stem = img_path.stem
        label_path = lbl_dir / f"{stem}.txt"
        if not label_path.exists(): continue

        is_val = (abs(hash(stem)) % 10) < int(VAL_SPLIT * 10)
        out_img_dir = IMAGES_VAL if is_val else IMAGES_TRAIN
        out_lbl_dir = LABELS_VAL if is_val else LABELS_TRAIN

        shutil.copy2(img_path, out_img_dir / img_path.name)
        shutil.copy2(label_path, out_lbl_dir / f"{stem}.txt")
        count += 1

    return count

def print_summary():
    n_train_img = len(list(IMAGES_TRAIN.glob("*.jpg")) + list(IMAGES_TRAIN.glob("*.png")))
    n_val_img   = len(list(IMAGES_VAL.glob("*.jpg")) + list(IMAGES_VAL.glob("*.png")))
    n_train_lbl = len(list(LABELS_TRAIN.glob("*.txt")))
    n_val_lbl   = len(list(LABELS_VAL.glob("*.txt")))

    print("\n" + "="*50)
    print("Dataset Summary (Khusus Baki Medis)")
    print("="*50)
    print(f"  Train images : {n_train_img}")
    print(f"  Train labels : {n_train_lbl}")
    print(f"  Val images   : {n_val_img}")
    print(f"  Val labels   : {n_val_lbl}")
    print("="*50 + "\n")

def write_data_yaml():
    yaml_content = f"""path: {DATASET_OUT.as_posix()}
train: images/train
val: images/val

nc: 6
names:
  0: tenaga_kesehatan
  1: baki_medis
  2: troli_medis
  3: wastafel
  4: hand_sanitizer
  5: pintu_masuk
"""
    yaml_path = DATASET_OUT / "data.yaml"
    with open(yaml_path, "w") as f:
        f.write(yaml_content)

def main():
    print("[INFO] Menyiapkan dataset khusus alat medis...")
    ensure_dirs()

    print("\n[1/2] Processing tray dataset lokal...")
    n = process_tray_dataset()
    if n > 0: print(f"  {n} gambar tray diproses")

    print("\n[2/2] Processing dataset Roboflow (Hanya Tray)...")
    for split in ["train", "valid", "test"]:
        n_tray = copy_dataset_split(ROBOFLOW_TRAY_SRC, split, remap=TRAY_REMAP)
        if n_tray > 0: print(f"  Medical Tray {split}: {n_tray} gambar")

    write_data_yaml()
    print_summary()
    print("Dataset siap! Jalankan training/train.py untuk mulai training.")

if __name__ == "__main__":
    main()
