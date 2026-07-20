"""
training/prepare_dataset.py
============================
Menyiapkan dataset unified HANYA dari folder Roboflow.
Person (tenaga_kesehatan) TIDAK dilatih di sini — pakai yolov8n.pt COCO.

Kelas training (data.yaml):
  0: baki_medis  ← Medical Tray + update dataset baki (Roboflow)

Jalankan: python training/prepare_dataset.py
"""
import shutil
from pathlib import Path

# ─── Path ──────────────────────────────────────────────────────────────────
BASE_DIR     = Path(__file__).parent.parent
DATASET_OUT  = BASE_DIR / "dataset"
IMAGES_TRAIN = DATASET_OUT / "images" / "train"
IMAGES_VAL   = DATASET_OUT / "images" / "val"
LABELS_TRAIN = DATASET_OUT / "labels" / "train"
LABELS_VAL   = DATASET_OUT / "labels" / "val"

# Sumber dataset Roboflow (edit di sini jika nama folder berubah)
ROBOFLOW_DIR = BASE_DIR / "bukan sistem" / "roboflow"
ROBOFLOW_SOURCES = [
    ROBOFLOW_DIR / "Medical Tray.v1i.yolov8",
    ROBOFLOW_DIR / "update dataset baki.v6i.yolov8",
]

VAL_SPLIT = 0.2  # 20% untuk validasi (re-split dari train/valid/test Roboflow)


def ensure_dirs():
    for d in [IMAGES_TRAIN, IMAGES_VAL, LABELS_TRAIN, LABELS_VAL]:
        d.mkdir(parents=True, exist_ok=True)


def clear_output():
    """Hapus isi dataset/ agar tidak dobel saat dijalankan ulang."""
    for d in [IMAGES_TRAIN, IMAGES_VAL, LABELS_TRAIN, LABELS_VAL]:
        if d.exists():
            for f in d.iterdir():
                if f.is_file():
                    f.unlink()


def remap_label_file(src_label: Path, dst_label: Path, remap: dict):
    lines_out = []
    with open(src_label, "r") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            parts = line.split()
            class_id = int(parts[0])
            parts[0] = str(remap.get(class_id, class_id))
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


def print_summary():
    n_train_img = len(list(IMAGES_TRAIN.glob("*.jpg")) + list(IMAGES_TRAIN.glob("*.png")))
    n_val_img   = len(list(IMAGES_VAL.glob("*.jpg")) + list(IMAGES_VAL.glob("*.png")))
    n_train_lbl = len(list(LABELS_TRAIN.glob("*.txt")))
    n_val_lbl   = len(list(LABELS_VAL.glob("*.txt")))

    print("\n" + "=" * 50)
    print("Dataset Summary (Baki Medis dari Roboflow)")
    print("=" * 50)
    print(f"  Train images : {n_train_img}")
    print(f"  Train labels : {n_train_lbl}")
    print(f"  Val images   : {n_val_img}")
    print(f"  Val labels   : {n_val_lbl}")
    print("=" * 50 + "\n")


def write_data_yaml():
    # 1 kelas saja: person tetap dari yolov8n.pt (COCO), bukan dari training ini
    yaml_content = f"""path: {DATASET_OUT.as_posix()}
train: images/train
val: images/val

nc: 1
names:
  0: baki_medis
"""
    yaml_path = DATASET_OUT / "data.yaml"
    with open(yaml_path, "w") as f:
        f.write(yaml_content)
    print(f"[INFO] data.yaml ditulis: {yaml_path}")


def main():
    print("[INFO] Menyiapkan dataset dari Roboflow (tanpa OIDv4_ToolKit)...")
    ensure_dirs()
    clear_output()

    total = 0
    for i, src in enumerate(ROBOFLOW_SOURCES, start=1):
        print(f"\n[{i}/{len(ROBOFLOW_SOURCES)}] {src.name}")
        if not src.exists():
            print(f"  [SKIP] Folder tidak ditemukan: {src}")
            continue
        for split in ["train", "valid", "test"]:
            n = copy_dataset_split(src, split, remap=None)  # class 0 tetap baki_medis
            if n > 0:
                print(f"  {split}: {n} gambar")
                total += n

    if total == 0:
        print("\n[ERROR] Tidak ada gambar yang diproses. Cek path di ROBOFLOW_SOURCES.")
        return

    write_data_yaml()
    print_summary()
    print("Dataset siap! Jalankan: python training/train.py")


if __name__ == "__main__":
    main()
