import os
import shutil
from pathlib import Path

BASE_DIR = Path(r"e:\skripsi\sistem")
DATASET_DIR = BASE_DIR / "dataset"
BACKUP_DIR = BASE_DIR / "dataset_backup"

def backup_dataset():
    if not BACKUP_DIR.exists():
        print(f"Creating backup at {BACKUP_DIR}...")
        shutil.copytree(DATASET_DIR, BACKUP_DIR)
        print("Backup complete.")
    else:
        print("Backup already exists. Skipping backup step.")

def filter_labels_and_images():
    # Target class yang dipertahankan: 1 (baki_medis), 2 (troli_medis) jika ada. 
    # Karena kita hanya fokus baki, kita pertahankan '1' dan '2' just in case.
    TARGET_CLASSES = {'1', '2'}
    
    total_removed_images = 0
    total_kept_images = 0
    
    for split in ['train', 'val']:
        img_dir = DATASET_DIR / "images" / split
        lbl_dir = DATASET_DIR / "labels" / split
        
        if not img_dir.exists() or not lbl_dir.exists():
            continue
            
        print(f"\nProcessing {split} split...")
        for lbl_file in lbl_dir.glob("*.txt"):
            with open(lbl_file, 'r') as f:
                lines = f.readlines()
            
            new_lines = []
            has_target = False
            for line in lines:
                parts = line.strip().split()
                if len(parts) > 0:
                    cls_id = parts[0]
                    if cls_id in TARGET_CLASSES:
                        new_lines.append(line)
                        has_target = True
            
            img_file_jpg = img_dir / (lbl_file.stem + ".jpg")
            img_file_png = img_dir / (lbl_file.stem + ".png")
            img_file = img_file_jpg if img_file_jpg.exists() else (img_file_png if img_file_png.exists() else None)
            
            if has_target:
                # Timpa file label dengan line yang sudah difilter (hanya class baki)
                with open(lbl_file, 'w') as f:
                    f.writelines(new_lines)
                total_kept_images += 1
            else:
                # Hapus label file jika tidak punya baki
                lbl_file.unlink()
                # Hapus image file
                if img_file and img_file.exists():
                    img_file.unlink()
                total_removed_images += 1
                
        # Hapus file cache
        cache_file = lbl_dir.parent / f"{split}.cache"
        if cache_file.exists():
            cache_file.unlink()
            print(f"Deleted {cache_file}")

    print("\n" + "="*40)
    print("Pemfilteran Selesai!")
    print(f"Total gambar baki dipertahankan: {total_kept_images}")
    print(f"Total gambar benda mati dihapus: {total_removed_images}")
    print("="*40)

if __name__ == "__main__":
    backup_dataset()
    filter_labels_and_images()
