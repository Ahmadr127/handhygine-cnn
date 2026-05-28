# 🏥 Sistem Monitoring Kepatuhan Cuci Tangan
**Computer Vision · YOLOv8 · ByteTrack · Laravel · PostgreSQL**

---

## 🚀 Cara Menjalankan

### 1. Jalankan AI Service (Python)
```bash
cd ai-service
python -m uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```
API docs tersedia di: http://localhost:8001/docs

### 2. Jalankan Laravel Dashboard
```bash
cd laravel-app
php artisan serve --port=8000
```
Dashboard tersedia di: http://localhost:8000

---

## 🗂 Struktur Proyek

```
sistem/
├── ai-service/          # Python FastAPI + YOLO
│   ├── main.py          # Entry point (port 8001)
│   ├── config.py        # Konfigurasi global
│   ├── core/
│   │   ├── detector.py      # YOLOv8 wrapper
│   │   ├── tracker.py       # ByteTrack
│   │   ├── zone_manager.py  # Polygon zones (Shapely)
│   │   ├── compliance.py    # State machine kepatuhan
│   │   └── camera_manager.py
│   ├── api/
│   │   ├── cameras.py    # REST CRUD kamera
│   │   ├── stream.py     # WebSocket live stream
│   │   └── status.py     # Health check & stats
│   └── utils/
│       ├── db.py         # PostgreSQL (psycopg2)
│       └── snapshot.py   # Simpan screenshot
│
├── laravel-app/         # Web Dashboard (port 8000)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   └── Models/
│   ├── resources/views/
│   │   ├── layouts/app.blade.php   # Layout utama (dark theme)
│   │   ├── dashboard.blade.php     # 4-grid monitoring
│   │   ├── cameras/index.blade.php # Manajemen kamera
│   │   ├── cameras/zones.blade.php # Konfigurasi zona
│   │   └── monitoring/index.blade.php # Log & laporan
│   └── database/migrations/
│
├── dataset/             # Dataset YOLO unified
│   ├── images/train/ & val/
│   ├── labels/train/ & val/
│   └── data.yaml
│
├── training/
│   ├── prepare_dataset.py  # Merge & prepare dataset
│   ├── train.py            # Fine-tune YOLOv8n
│   └── validate.py         # Validasi model
│
├── models/
│   └── best.pt          # Model hasil training
│
└── snapshots/           # Screenshot kejadian
    └── YYYY-MM-DD/
```

---

## 📋 Alur Penggunaan

### A. Setup Pertama Kali

1. **Siapkan dataset** (jika belum):
   ```bash
   cd e:\skripsi\sistem
   python training/prepare_dataset.py
   ```

2. **Training model** (butuh waktu, GPU jika ada):
   ```bash
   python training/train.py
   ```
   Model akan tersimpan di `models/best.pt`

3. **Jalankan kedua service** (lihat atas)

### B. Tambah Kamera

1. Buka http://localhost:8000/cameras
2. Klik form **Tambah Kamera**
3. Pilih tipe (USB/RTSP/File) dan masukkan source
4. Klik **Tambah**

### C. Konfigurasi Zona

1. Di halaman kamera, klik **🗺 Zona** pada kamera
2. Pilih tipe > ⚠️ **Catatan Sistem Grup Monitoring:**  
> Sistem dirancang menggunakan arsitektur **Grup Monitoring** per lorong/area. Karena kondisi CCTV di lapangan tidak menentu (sudut dan jangkauannya), sistem memungkinkan lebih dari satu kamera untuk memantau satu area (lorong) yang sama. Logika kepatuhan bersifat lintas-kamera (*cross-camera*) berdasarkan waktu (window 60 detik). **Syarat utama**: Dalam satu grup minimal harus terdapat 1 zona cuci tangan (sanitizer/wastafel) dan 1 zona pintu masuk (meskipun berada di tangkapan kamera yang berbeda).

### D. Monitoring Real-time

1. Buka http://localhost:8000 (Dashboard)
2. Pilih kamera pada setiap slot grid
3. Klik **▶ Connect**
4. Sistem akan menampilkan live feed + deteksi YOLO

---

## 📊 Logika Kepatuhan

```
Person terdeteksi membawa instrumen (baki/troli)
           ↓
   Masuk zona Sanitizer/Wastafel?
        ↙              ↘
      YA               TIDAK
       ↓                 ↓
  Aktivitas          Langsung ke
  cuci tangan         zona pintu
       ↓                 ↓
  Masuk pintu        TIDAK PATUH ❌
       ↓              → Log + Snapshot
   PATUH ✅
  → Log + Snapshot
```

---

## 🧪 Dataset

### Label (data.yaml)
| Index | Label | Status |
|---|---|---|
| 0 | tenaga_kesehatan | COCO pre-trained (person) |
| 1 | baki_medis | tray_dataset_raw (perlu anotasi) |
| 2 | troli_medis | Perlu dataset tambahan |
| 3 | wastafel | Perlu dataset tambahan |
| 4 | hand_sanitizer | ✅ 426+ gambar |
| 5 | pintu_masuk | Perlu dataset tambahan |

### Cara Tambah Dataset Baru
1. Tambah gambar ke `dataset/images/train/`
2. Buat label YOLO di `dataset/labels/train/` (sesuai index)
3. Jalankan ulang `training/train.py`

### Cara Anotasi Tray Dataset
1. Buka **CVAT** (https://cvat.ai) atau **LabelImg**
2. Load gambar dari `tray_dataset_raw/`
3. Buat label: `baki_medis` (class index **1**)
4. Export format YOLO TXT
5. Simpan ke `tray_dataset_raw/labels/`
6. Jalankan `training/prepare_dataset.py`

---

## ⚙️ Konfigurasi

### AI Service (`ai-service/.env`)
```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=handwash_db
DB_USER=postgres
DB_PASS=
MODEL_PATH=../models/best.pt
DETECTION_CONFIDENCE=0.45
```

### Laravel (`laravel-app/.env`)
```env
DB_CONNECTION=pgsql
DB_DATABASE=handwash_db
DB_USERNAME=postgres
AI_SERVICE_URL=http://localhost:8001
AI_SERVICE_WS=ws://localhost:8001
```

---

## 🔧 Troubleshooting

| Masalah | Solusi |
|---|---|
| AI Service offline (merah di sidebar) | Pastikan `uvicorn` berjalan di port 8001 |
| Kamera tidak muncul | Cek source (index/RTSP URL), klik Scan USB |
| Model tidak ditemukan | Jalankan `training/train.py` atau download pre-trained |
| Database error | Cek PostgreSQL berjalan, `handwash_db` ada |
| WebSocket tidak connect | Cek CORS di AI service, port 8001 terbuka |

---

## 📚 Referensi
- [YOLOv8 Documentation](https://docs.ultralytics.com)
- [Supervision Library](https://supervision.roboflow.com)
- [FastAPI WebSocket](https://fastapi.tiangolo.com/advanced/websockets/)
- [Laravel 11 Docs](https://laravel.com/docs/11.x)
