import os
from dotenv import load_dotenv

load_dotenv()

# ─────────────────────────────────────────────
# Database
# ─────────────────────────────────────────────
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = int(os.getenv("DB_PORT", "5432"))
DB_NAME = os.getenv("DB_NAME", "handwash_db")
DB_USER = os.getenv("DB_USER", "postgres")
DB_PASS = os.getenv("DB_PASS", "")

# ─────────────────────────────────────────────
# YOLO Model
# ─────────────────────────────────────────────
MODEL_PATH = os.getenv("MODEL_PATH", "../models/best.pt")
# Fallback ke YOLOv8n pre-trained jika best.pt belum ada
FALLBACK_MODEL = "yolov8n.pt"
DETECTION_CONFIDENCE = float(os.getenv("DETECTION_CONFIDENCE", "0.45"))
# Confidence khusus untuk deteksi instrumen medis (best.pt).
# Lebih tinggi dari orang untuk menekan false positive (baju putih, bayangan, dll.)
INSTRUMENT_CONFIDENCE = float(os.getenv("INSTRUMENT_CONFIDENCE", "0.65"))
# Luas minimum bounding box instrumen (piksel²).
# Deteksi instrumen yang terlalu kecil hampir pasti false positive.
INSTRUMENT_MIN_AREA = int(os.getenv("INSTRUMENT_MIN_AREA", "3000"))

# ─────────────────────────────────────────────
# Class mapping (sesuai data.yaml)
# ─────────────────────────────────────────────
CLASS_NAMES = {
    0: "tenaga_kesehatan",
    1: "baki_medis",
    2: "troli_medis",
}

# Class index
CLASS_PERSON       = 0
CLASS_BAKI         = 1
CLASS_TROLI        = 2

# Grup class "instrumen medis"
INSTRUMENT_CLASSES = {CLASS_BAKI, CLASS_TROLI}

# ─────────────────────────────────────────────
# Tracking & Compliance
# ─────────────────────────────────────────────
TRACK_RESET_SECONDS = 30       # reset state jika tidak terdeteksi
STREAM_FPS          = 15       # target FPS WebSocket stream
FRAME_QUEUE_SIZE    = 5        # max frame antrian per kamera
# Inference setiap N frame: 1=setiap frame (berat), 3=default (ringan), 5=sangat ringan
# Makin besar N → CPU lebih ringan, tapi bounding box sedikit lebih "delayed"
DETECT_EVERY_N_FRAMES = int(os.getenv("DETECT_EVERY_N_FRAMES", "3"))

# ─────────────────────────────────────────────
# Snapshot
# ─────────────────────────────────────────────
# Simpan langsung ke folder public storage Laravel agar bisa diakses lewat web
SNAPSHOT_DIR = os.getenv("SNAPSHOT_DIR", "../laravel-app/storage/app/public/snapshots")

# ─────────────────────────────────────────────
# FastAPI
# ─────────────────────────────────────────────
API_HOST = "0.0.0.0"
API_PORT = 8001
LARAVEL_ORIGIN = os.getenv("LARAVEL_ORIGIN", "http://localhost:8000")
