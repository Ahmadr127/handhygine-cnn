"""
training/prepare_dataset.py
============================
Script untuk menyiapkan dataset unified dari semua sumber yang ada.

Kelas final (data.yaml):
  0: tenaga_kesehatan  ← COCO pre-trained (class person)
  1: baki_medis        ← tray_dataset_raw (setelah dianotasi)
  2: troli_medis       ← dataset tambahan
  3: wastafel          ← dataset tambahan
  4: hand_sanitizer    ← hand sanitizer detection.v1i.yolov8 (remap)
  5: pintu_masuk       ← dataset tambahan

Jalankan: python training/prepare_dataset.py
"""
import os
import shutil
import random
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
SANITIZER_SRC = BASE_DIR / "bukan sistem" / "hand sanitizer detection.v1i.yolov8"
HANDWASH_SRC  = BASE_DIR / "bukan sistem" / "hand-washing.v1i.yolov8"
TRAY_SRC      = BASE_DIR / "tray_dataset_raw"  # belum dianotasi
DOOR_SRC      = BASE_DIR / "bukan sistem" / "OIDv4_ToolKit" / "OID" / "Dataset" / "train" / "Door"

VAL_SPLIT = 0.2  # 20% untuk validasi

# ─── Mapping kelas ─────────────────────────────────────────────────────────
# hand-sanitizer dataset: kelas 0 = Sanitizer → remap ke 4 (hand_sanitizer)
SANITIZER_REMAP = {0: 4}

# hand-washing dataset: step_1..6 → tidak digunakan sebagai object detection class
# Dataset ini bisa digunakan sebagai data augmentasi untuk aktivitas orang di depan sanitizer
# Untuk sekarang: kita skip label step_1..6, tapi copy gambarnya sebagai negatif
# (gambar orang tanpa label spesifik kita → berguna untuk background detection)
USE_HANDWASH_AS_BACKGROUND = True


def ensure_dirs():
    for d in [IMAGES_TRAIN, IMAGES_VAL, LABELS_TRAIN, LABELS_VAL]:
        d.mkdir(parents=True, exist_ok=True)


def remap_label_file(src_label: Path, dst_label: Path, remap: dict):
    """
    Baca file label YOLO, remap class index, tulis ke dst.
    Format: <class_id> <x_center> <y_center> <width> <height>
    """
    lines_out = []
    with open(src_label, "r") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            parts = line.split()
            class_id = int(parts[0])
            new_id = remap.get(class_id, class_id)
            parts[0] = str(new_id)
            lines_out.append(" ".join(parts))

    with open(dst_label, "w") as f:
        f.write("\n".join(lines_out))


def copy_dataset_split(src_dir: Path, split: str, remap: dict | None = None,
                       skip_labels: bool = False):
    """
    Copy gambar + label dari split tertentu ke dataset output.
    split: 'train' atau 'valid'
    """
    img_src = src_dir / split / "images"
    lbl_src = src_dir / split / "labels"

    if not img_src.exists():
        print(f"  [SKIP] {img_src} tidak ditemukan")
        return 0

    images = list(img_src.glob("*.jpg")) + list(img_src.glob("*.png"))
    count = 0

    for img_path in images:
        stem = img_path.stem
        label_path = lbl_src / f"{stem}.txt"

        # Cek split tujuan (80% train, 20% val berdasarkan filename hash)
        is_val = (abs(hash(stem)) % 10) < int(VAL_SPLIT * 10)
        out_img_dir = IMAGES_VAL if is_val else IMAGES_TRAIN
        out_lbl_dir = LABELS_VAL if is_val else LABELS_TRAIN

        # Copy image
        shutil.copy2(img_path, out_img_dir / img_path.name)

        # Copy/remap label
        if not skip_labels and label_path.exists():
            dst_label = out_lbl_dir / f"{stem}.txt"
            if remap:
                remap_label_file(label_path, dst_label, remap)
            else:
                shutil.copy2(label_path, dst_label)
        elif skip_labels:
            # Buat label kosong (background image)
            dst_label = out_lbl_dir / f"{stem}.txt"
            dst_label.write_text("")

        count += 1

    return count


def process_tray_dataset():
    """
    Copy gambar tray yang SUDAH dianotasi ke dataset.
    Cek apakah ada folder labels di tray_dataset_raw.
    Jika belum ada, print instruksi anotasi.
    """
    lbl_dir = TRAY_SRC / "labels"
    if not lbl_dir.exists():
        print("\n" + "="*60)
        print("[WARNING] tray_dataset_raw belum dianotasi!")
        print("="*60)
        print("Langkah anotasi:")
        print("1. Buka CVAT atau LabelImg")
        print("2. Load gambar dari: tray_dataset_raw/")
        print("3. Buat label: baki_medis (class index 1)")
        print("4. Export ke format YOLO TXT")
        print("5. Simpan ke: tray_dataset_raw/labels/")
        print("6. Jalankan ulang script ini")
        print("="*60 + "\n")
        return 0

    images = list(TRAY_SRC.glob("*.jpg")) + list(TRAY_SRC.glob("*.png"))
    count = 0
    for img_path in images:
        stem = img_path.stem
        label_path = lbl_dir / f"{stem}.txt"
        if not label_path.exists():
            continue

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
    print("Dataset Summary")
    print("="*50)
    print(f"  Train images : {n_train_img}")
    print(f"  Train labels : {n_train_lbl}")
    print(f"  Val images   : {n_val_img}")
    print(f"  Val labels   : {n_val_lbl}")
    print("="*50)
    print(f"  Output: {DATASET_OUT}")
    print("="*50 + "\n")


def main():
    print("[INFO] Menyiapkan dataset unified...")
    ensure_dirs()

    # 1. Hand sanitizer dataset → remap class 0 (Sanitizer) → 4 (hand_sanitizer)
    print("\n[1/3] Processing hand sanitizer dataset...")
    for split in ["train", "valid"]:
        n = copy_dataset_split(SANITIZER_SRC, split, remap=SANITIZER_REMAP)
        print(f"  {split}: {n} gambar")

    # 2. Hand washing dataset → gunakan sebagai background (orang cuci tangan)
    if USE_HANDWASH_AS_BACKGROUND:
        print("\n[2/3] Processing hand-washing dataset (sebagai background)...")
        for split in ["train", "valid"]:
            n = copy_dataset_split(HANDWASH_SRC, split, skip_labels=True)
            print(f"  {split}: {n} gambar (background, tanpa label)")
    else:
        print("\n[2/3] Skipping hand-washing dataset")

    # 3. Tray dataset (jika sudah dianotasi)
    print("\n[3/4] Processing tray dataset...")
    n = process_tray_dataset()
    if n > 0:
        print(f"  {n} gambar tray diproses")

    # 4. Door dataset dari OIDv4_ToolKit (pintu_masuk -> class index 5)
    print("\n[4/4] Processing door dataset...")
    n_door = process_door_dataset()
    if n_door > 0:
        print(f"  {n_door} gambar pintu diproses")

    # 5. Buat data.yaml secara dinamis
    write_data_yaml()

    print_summary()
    print("Dataset siap! Jalankan training/train.py untuk mulai training.")


def write_data_yaml():
    """Menulis file data.yaml untuk konfigurasi training YOLOv8."""
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
    print(f"data.yaml berhasil dibuat di: {yaml_path}")


def process_door_dataset():
    """
    Membaca dataset Door dari OIDv4_ToolKit (format OID text),
    mengonversinya ke YOLO format (class index 5), dan menyalin ke dataset train/val.
    """
    if not DOOR_SRC.exists():
        print(f"  [SKIP] Door dataset source tidak ditemukan di: {DOOR_SRC}")
        return 0

    lbl_dir = DOOR_SRC / "Label"
    if not lbl_dir.exists():
        print(f"  [SKIP] Folder Label pintu tidak ditemukan")
        return 0

    from PIL import Image

    images = list(DOOR_SRC.glob("*.jpg")) + list(DOOR_SRC.glob("*.png"))
    count = 0

    for img_path in images:
        stem = img_path.stem
        label_path = lbl_dir / f"{stem}.txt"
        if not label_path.exists():
            continue

        try:
            with Image.open(img_path) as img:
                img_w, img_h = img.size
        except Exception as e:
            print(f"  Error loading image {img_path.name}: {e}")
            continue

        yolo_lines = []
        with open(label_path, "r") as f:
            for line in f:
                line = line.strip()
                if not line:
                    continue
                parts = line.split()
                if len(parts) < 5 or parts[0].lower() != "door":
                    continue
                
                try:
                    xmin = float(parts[1])
                    ymin = float(parts[2])
                    xmax = float(parts[3])
                    ymax = float(parts[4])
                    
                    # Convert to normalized coordinates
                    x_center = ((xmin + xmax) / 2.0) / img_w
                    y_center = ((ymin + ymax) / 2.0) / img_h
                    w = (xmax - xmin) / img_w
                    h = (ymax - ymin) / img_h
                    
                    # Limit coordinates between 0 and 1
                    x_center = max(0.0, min(1.0, x_center))
                    y_center = max(0.0, min(1.0, y_center))
                    w = max(0.0, min(1.0, w))
                    h = max(0.0, min(1.0, h))
                    
                    yolo_lines.append(f"5 {x_center:.6f} {y_center:.6f} {w:.6f} {h:.6f}")
                except Exception as e:
                    print(f"  Error parsing line '{line}' in {label_path.name}: {e}")
                    continue

        if not yolo_lines:
            continue

        # Tentukan split train/val
        is_val = (abs(hash(stem)) % 10) < int(VAL_SPLIT * 10)
        out_img_dir = IMAGES_VAL if is_val else IMAGES_TRAIN
        out_lbl_dir = LABELS_VAL if is_val else LABELS_TRAIN

        # Salin gambar
        shutil.copy2(img_path, out_img_dir / img_path.name)

        # Tulis label YOLO
        dst_label = out_lbl_dir / f"{stem}.txt"
        with open(dst_label, "w") as f:
            f.write("\n".join(yolo_lines))

        count += 1

    return count


if __name__ == "__main__":
    main()
