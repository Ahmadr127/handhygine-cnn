# Panduan Training Model & Dataset YOLOv8

Setup dataset **hanya dari Roboflow**. Folder `OIDv4_ToolKit` dan `tray_dataset_raw` **tidak dipakai lagi**.

---

## 1. Dual-Model (penting)

| Model | File | Kelas | Sumber |
| :--- | :--- | :--- | :--- |
| Person (tenaga kesehatan) | `yolov8n.pt` | COCO class `person` → runtime ID `0` | Pre-trained Ultralytics (auto-download) |
| Instrumen (baki) | `models/best.pt` | Training class `0` = `baki_medis` → runtime ID `1` | Fine-tune dari dataset Roboflow |

**Person tidak dilatih ulang.** Kalau `yolov8n.pt` terhapus, cukup jalankan training/sistem sekali — Ultralytics akan mengunduh ulang otomatis.

---

## 2. Folder Roboflow yang dipakai

```
bukan sistem/roboflow/
├── Medical Tray.v1i.yolov8/          ← export YOLOv8 (train/valid/test)
│   ├── train/images + labels
│   ├── valid/images + labels
│   └── test/images + labels
└── update dataset baki.v6i.yolov8/   ← export YOLOv8 tambahan
    ├── train/images + labels
    ├── valid/images + labels
    └── test/images + labels
```

Kedua dataset Roboflow sudah `nc: 1` (class index `0` = baki). Script menyalin keduanya ke `dataset/` dengan label tetap `0: baki_medis`.

---

## 3. Cara edit path / route Roboflow

Buka `training/prepare_dataset.py`, bagian atas:

```python
ROBOFLOW_DIR = BASE_DIR / "bukan sistem" / "roboflow"
ROBOFLOW_SOURCES = [
    ROBOFLOW_DIR / "Medical Tray.v1i.yolov8",
    ROBOFLOW_DIR / "update dataset baki.v6i.yolov8",
]
```

### Ganti / tambah dataset Roboflow baru

1. Letakkan folder export YOLOv8 di `bukan sistem/roboflow/` (harus ada `train/images`, `train/labels`, dst.).
2. Tambahkan baris di `ROBOFLOW_SOURCES`:
   ```python
   ROBOFLOW_SOURCES = [
       ROBOFLOW_DIR / "Medical Tray.v1i.yolov8",
       ROBOFLOW_DIR / "update dataset baki.v6i.yolov8",
       ROBOFLOW_DIR / "nama-folder-baru.vXi.yolov8",  # ← tambah ini
   ]
   ```
3. Jalankan ulang: `python training/prepare_dataset.py`

### Pindah lokasi folder Roboflow

Ubah `ROBOFLOW_DIR`, contoh jika dipindah ke root proyek:

```python
ROBOFLOW_DIR = BASE_DIR / "roboflow"
```

### Remap kelas (jarang perlu)

Kalau export Roboflow punya class index yang beda, isi `remap` saat copy:

```python
# contoh: class 0 di Roboflow tetap 0 (baki) — default sekarang remap=None
n = copy_dataset_split(src, split, remap={0: 0})
```

---

## 4. Persiapan dataset

```bash
python training/prepare_dataset.py
```

Script akan:
1. Mengosongkan lalu mengisi `dataset/images/{train,val}` dan `dataset/labels/{train,val}`
2. Menyalin semua split Roboflow (`train` / `valid` / `test`) lalu membagi ulang ~80/20
3. Menulis `dataset/data.yaml` dengan `nc: 1` (`0: baki_medis`)

---

## 5. Training

```bash
python training/train.py --epochs 100 --batch 16
```

- Base weights: `yolov8n.pt` (auto-download jika belum ada)
- Output: `models/best.pt` dan log di `models/handwash_v1/`

Turunkan `--batch` ke `8` atau `4` jika OOM.

---

## 6. Validasi

```bash
python training/validate.py
```

Memakai `models/best.pt` + `dataset/data.yaml`.

---

## 7. Menambah sedikit gambar manual

Salin pasangan image + label ke:
- `dataset/images/train/` + `dataset/labels/train/`
- atau `dataset/images/val/` + `dataset/labels/val/`

Pastikan baris label diawali `0` (baki_medis).
