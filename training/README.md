# 📂 Panduan Training Model & Manajemen Dataset YOLOv8

Panduan ini menjelaskan struktur kelas dataset kustom, cara mempersiapkan data (preprocessing), cara menjalankan training/validasi, serta panduan menambahkan dataset baru yang sudah dilabeli ke dalam sistem.

---

## 🏷️ 1. Skema Kelas & Index YOLO

Model YOLOv8 pada sistem ini dikonfigurasikan dengan **6 kelas** utama berikut:

| Index Kelas | Nama Kelas | Kegunaan Objek | Asal Dataset Awal |
| :---: | :--- | :--- | :--- |
| **`0`** | `tenaga_kesehatan` | Deteksi petugas medis | COCO Pre-trained (`person`) |
| **`1`** | `baki_medis` | Deteksi baki/tray medis | `tray_dataset_raw` (Perlu anotasi manual) |
| **`2`** | `troli_medis` | Deteksi troli medis | Dataset kustom tambahan |
| **`3`** | `wastafel` | Deteksi wastafel/sink | Dataset kustom tambahan |
| **`4`** | `hand_sanitizer` | Deteksi dispenser sanitizer | `hand sanitizer detection.v1i.yolov8` |
| **`5`** | `pintu_masuk` | Deteksi pintu/akses masuk | `OIDv4_ToolKit/OID/Dataset/train/Door` |

---

## 🛠️ 2. Persiapan Dataset (`prepare_dataset.py`)

Sebelum melakukan training, jalankan script untuk menyatukan dan menyelaraskan dataset:
```bash
python training/prepare_dataset.py
```

### Apa yang dilakukan script ini secara otomatis?
1. Membuat folder tujuan: `dataset/images/train`, `dataset/images/val`, `dataset/labels/train`, dan `dataset/labels/val`.
2. Menyalin dataset Hand Sanitizer & melakukan *remapping* indeks kelas (dari `0` di dataset asli menjadi kelas `4`).
3. Menyalin dataset Hand Washing sebagai gambar latar belakang (*background negative samples* tanpa label) untuk mengurangi *false positives* saat training.
4. Membaca dataset Door (Pintu) format OIDv4 (koordinat piksel absolut) dan **mengonversinya ke koordinat ternormalisasi YOLO** dengan indeks kelas `5`.
5. Membagi data secara acak menggunakan pembagian **80% untuk Training** dan **20% untuk Validasi**.
6. Membuat file konfigurasi dataset [dataset/data.yaml](file:///e:/skripsi/sistem/dataset/data.yaml) secara dinamis.

---

## 🚀 3. Cara Menambahkan Dataset Baru Berlabel

Jika Anda memiliki tambahan dataset baru yang sudah dilabeli, pilih salah satu metode di bawah ini:

### Metode A: Mendaftarkan Dataset Format YOLO via Script (Rekomendasi)
*Gunakan metode ini jika label Anda sudah berupa file `.txt` YOLO (koordinat 0-1).*

1. Letakkan folder dataset Anda di dalam folder proyek (misalnya: `bukan sistem/dataset_baru/`). Pastikan strukturnya memiliki subfolder `train/images`, `train/labels`, `valid/images`, dan `valid/labels`.
2. Buka script [training/prepare_dataset.py](file:///e:/skripsi/sistem/training/prepare_dataset.py).
3. Tambahkan path folder sumber di bagian atas script:
   ```python
   NEW_SRC = BASE_DIR / "bukan sistem" / "dataset_baru"
   ```
4. Definisikan pemetaan kelas (*remapping*) jika indeks kelas dataset baru tidak sesuai dengan skema kita. Contoh jika objek pada dataset baru memiliki index `0` tetapi harus diubah menjadi kelas `2` (`troli_medis`):
   ```python
   NEW_REMAP = {0: 2} 
   ```
5. Di dalam fungsi `main()`, tambahkan pemanggilan salin data:
   ```python
   print("\n[5/5] Processing dataset baru...")
   for split in ["train", "valid"]:
       n = copy_dataset_split(NEW_SRC, split, remap=NEW_REMAP)
       print(f"  {split}: {n} gambar diproses")
   ```
6. Jalankan kembali script: `python training/prepare_dataset.py`

### Metode B: Mengonversi Dataset Format OIDv4/Lainnya
*Gunakan metode ini jika dataset Anda memiliki label koordinat piksel absolut seperti dataset Door.*

1. Tulis fungsi baru di dalam `prepare_dataset.py` (contohnya bisa meniru fungsi `process_door_dataset()`).
2. Gunakan Pillow untuk membaca dimensi gambar (`img.size`) guna menormalisasi koordinat box:
   ```python
   x_center = ((xmin + xmax) / 2.0) / img_w
   y_center = ((ymin + ymax) / 2.0) / img_h
   w = (xmax - xmin) / img_w
   h = (ymax - ymin) / img_h
   ```
3. Simpan baris label ke file `.txt` dengan prefix indeks kelas target (contoh: `3` untuk wastafel).
4. Jalankan script untuk melakukan konversi massal.

### Metode C: Menyalin Manual (Tanpa Mengubah Script)
*Cocok untuk penambahan gambar dalam jumlah sedikit.*

1. Salin file gambar (`.jpg` / `.png`) langsung ke:
   - `dataset/images/train/` (untuk training) ATAU `dataset/images/val/` (untuk validasi)
2. Salin file label `.txt` pasangannya langsung ke:
   - `dataset/labels/train/` ATAU `dataset/labels/val/`
3. **PENTING**: Pastikan angka pertama di setiap baris file `.txt` Anda telah disesuaikan dengan indeks kelas sistem (0 s/d 5).

---

## 🏋️ 4. Menjalankan Training Model (`train.py`)

Untuk melatih model YOLOv8n menggunakan dataset yang telah disatukan:
```bash
python training/train.py --epochs 100 --batch 16
```

### Parameter Tambahan CLI:
* `--epochs`: Jumlah siklus training (Default: `100`).
* `--batch`: Ukuran batch data (Default: `16`, turunkan ke `8` atau `4` jika memory OOM / CPU lambat).

**Hasil Training**:
* Model terbaik akan disimpan di [models/best.pt](file:///e:/skripsi/sistem/models/best.pt).
* Log training dan visualisasi grafik metrik tersimpan di folder `models/handwash_v1/`.

---

## 📊 5. Validasi & Benchmark Kecepatan (`validate.py`)

Setelah training selesai, Anda dapat memverifikasi kinerja model kustom Anda dengan perintah:
```bash
python training/validate.py
```

Script ini akan:
1. Memuat file `models/best.pt`.
2. Melakukan evaluasi metrik akurasi (Precision, Recall, mAP50, mAP50-95) per kelas pada dataset validasi.
3. Melakukan benchmark kecepatan inference rata-rata per frame (dalam satuan milidetik) dan menghitung FPS teoretis pada hardware lokal Anda.
