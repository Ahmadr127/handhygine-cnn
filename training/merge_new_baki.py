import os
import shutil

src_dir = r"e:\skripsi\sistem\bukan sistem\update dataset baki.v1i.yolov8"
dest_dir = r"e:\skripsi\sistem\dataset"

print("Memulai penggabungan dataset baru...")

for split in ['train', 'valid', 'test']:
    src_images = os.path.join(src_dir, split, "images")
    src_labels = os.path.join(src_dir, split, "labels")
    
    if not os.path.exists(src_images): 
        continue
        
    dest_split = 'val' if split in ['valid', 'test'] else 'train'
    dest_images = os.path.join(dest_dir, "images", dest_split)
    dest_labels = os.path.join(dest_dir, "labels", dest_split)
    
    os.makedirs(dest_images, exist_ok=True)
    os.makedirs(dest_labels, exist_ok=True)
    
    # Copy images
    img_count = 0
    for f in os.listdir(src_images):
        if f.endswith('.jpg') or f.endswith('.png'):
            shutil.copy2(os.path.join(src_images, f), os.path.join(dest_images, f))
            img_count += 1
            
    # Modify and copy labels
    lbl_count = 0
    if os.path.exists(src_labels):
        for f in os.listdir(src_labels):
            if f.endswith('.txt'):
                with open(os.path.join(src_labels, f), 'r') as file:
                    lines = file.readlines()
                
                new_lines = []
                for line in lines:
                    parts = line.strip().split()
                    if len(parts) > 0:
                        # Di dataset baru, baki adalah class 0.
                        # Di dataset utama, baki adalah class 1.
                        if parts[0] == '0':
                            parts[0] = '1'
                        new_lines.append(" ".join(parts) + "\n")
                        
                with open(os.path.join(dest_labels, f), 'w') as file:
                    file.writelines(new_lines)
                lbl_count += 1
                
    print(f"[{split} -> {dest_split}] Berhasil menyalin {img_count} gambar dan mengubah {lbl_count} label.")

print("Penggabungan selesai!")
