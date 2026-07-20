# Catatan Sistem Hand Hygiene CNN

## State yang Ditampilkan di Video

| # | Label | Warna Kotak | Kapan Muncul |
|---|---|---|---|
| 1 | Monitoring | Abu-abu | Default — orang terdeteksi tapi tidak ada kondisi khusus |
| 2 | Membawa Instrumen | Oranye | Bbox orang overlap/dekat dengan bbox instrumen (baki) |
| 3 | Cuci Tangan... | Kuning redup | Bbox orang menyentuh zona wastafel, tapi < 2 detik |
| 4 | Cuci Tangan | Kuning | Orang di zona wastafel sudah ≥ 2 detik → terkonfirmasi |
| 5 | Sudah Cuci Tangan ✓ | Hijau-tosca | Cuci tangan sudah tercatat, orang berjalan menuju pintu |
| 6 | PATUH | Hijau terang | Masuk zona pintu + instrumen + cuci tangan ✓ → tersimpan DB |
| 7 | TIDAK PATUH | Merah | Masuk zona pintu + instrumen ✓ + tidak cuci tangan → tersimpan DB |

---

## Deteksi "Membawa Instrumen" (Overlap Ratio, bukan IoU)

Sebelum (IoU):

```
Person bbox: 200×400 = 80.000 px
Tray bbox:   150×60  =  9.000 px  (di dalam person)
IoU = 9.000 / (80.000 + 9.000 - 9.000) = 0.11 ← di bawah 0.3 → Monitoring ❌
```

Sesudah (Overlap Ratio):

```
inter_area = 9.000 px  (nampan 100% di dalam bbox orang)
i_area     = 9.000 px  (luas nampan)
overlap_ratio = 9.000 / 9.000 = 1.0 ← di atas 0.5 → Membawa Instrumen ✅
```

---

## Dual-Model (Person vs Baki)

| Model | File | Fungsi | Class ID di runtime |
|---|---|---|---|
| Pretrained COCO | `yolov8n.pt` | Deteksi orang (`person`) → tenaga kesehatan | `0` |
| Custom fine-tune | `models/best.pt` | Deteksi baki medis | `1` (di-remap dari class `0` hasil training) |

- Person **tidak dilatih** dari dataset Roboflow.
- Training Roboflow hanya menghasilkan `best.pt` untuk baki (`nc: 1`, class `0: baki_medis`).
- Logika merge ada di `handhygiene-cnn/core/detector.py`.

### Lokasi file yang diharapkan

```
sistem/
├── yolov8n.pt                 # sering muncul otomatis saat train dari root
├── models/
│   └── best.pt                # hasil training (disalin dari handwash_v1/weights/)
├── handhygiene-cnn/
│   └── yolov8n.pt             # WAJIB ada di sini untuk detector runtime
└── training/
    └── yolov8n.pt             # muncul jika train dijalankan dari folder training/
```

---

## Mengembalikan / Mengunduh Ulang `yolov8n.pt` (Pretrained Person)

File ini **bukan hasil training Anda**. Itu bobot resmi Ultralytics YOLOv8n (COCO). Kalau terhapus, unduh ulang — tidak perlu backup lama.

### Cara 1 — Otomatis (paling mudah)

Jalankan salah satu perintah ini (butuh internet). Ultralytics akan mengunduh `yolov8n.pt` ke **current working directory**:

```bash
# dari root proyek
cd E:\skripsi\sistem
python -c "from ultralytics import YOLO; YOLO('yolov8n.pt')"
```

atau biarkan `python training/train.py` mengunduh sendiri saat mulai training.

### Cara 2 — Salin ke lokasi runtime detector

Detector mencari file di `handhygiene-cnn/yolov8n.pt` (lihat `FALLBACK_MODEL` di `config.py`).

Kalau file sudah ada di root / folder training, salin:

```powershell
Copy-Item E:\skripsi\sistem\yolov8n.pt E:\skripsi\sistem\handhygiene-cnn\yolov8n.pt -Force
```

Atau unduh langsung ke folder CNN:

```bash
cd E:\skripsi\sistem\handhygiene-cnn
python -c "from ultralytics import YOLO; YOLO('yolov8n.pt')"
```

### Cara 3 — Manual dari GitHub Ultralytics

Unduh release weights YOLOv8n, lalu simpan sebagai:

- `handhygiene-cnn/yolov8n.pt` (untuk sistem jalan)
- opsional juga di root `sistem/yolov8n.pt` (untuk training)

URL resmi biasanya mengarah ke assets Ultralytics (nama file: `yolov8n.pt`).

### Cek cepat apakah file valid

Ukuran normal `yolov8n.pt` sekitar **~6.5 MB**. Kalau jauh lebih kecil / sedang bertambah, unduhan belum selesai.

```powershell
Get-Item E:\skripsi\sistem\handhygiene-cnn\yolov8n.pt |
  Select-Object FullName, Length
```

### Yang TIDAK perlu dikembalikan manual

| File | Keterangan |
|---|---|
| `models/best.pt` | Hasil **training Anda**. Muncul lagi setelah `train.py` selesai (disalin dari `models/handwash_v1/weights/best.pt`). |
| Dataset toolkit / OIDv4 | Sudah tidak dipakai. Sumber data = `bukan sistem/roboflow/`. |

---

## Pipeline Training (ringkas)

```bash
# 1. Satukan dataset Roboflow → dataset/
python training/prepare_dataset.py

# 2. Fine-tune baki (base weights: yolov8n.pt)
python training/train.py --epochs 100 --batch 16

# 3. Evaluasi
python training/validate.py
```

Path sumber Roboflow diedit di `training/prepare_dataset.py` → `ROBOFLOW_SOURCES`.

Setelah training selesai:

1. Pastikan `models/best.pt` ada.
2. Pastikan `handhygiene-cnn/yolov8n.pt` ada (person).
3. Jalankan sistem CNN seperti biasa.
