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

# ─────────────────────────────────────────────
# Class mapping (sesuai data.yaml)
# ─────────────────────────────────────────────
CLASS_NAMES = {
    0: "tenaga_kesehatan",
    1: "baki_medis",
    2: "troli_medis",
    3: "wastafel",
    4: "hand_sanitizer",
    5: "pintu_masuk",
}

# Class index
CLASS_PERSON       = 0
CLASS_BAKI         = 1
CLASS_TROLI        = 2
CLASS_WASTAFEL     = 3
CLASS_SANITIZER    = 4
CLASS_PINTU        = 5

# Grup class "instrumen medis"
INSTRUMENT_CLASSES = {CLASS_BAKI, CLASS_TROLI}

# Grup class "area cuci tangan"
HANDWASH_CLASSES   = {CLASS_WASTAFEL, CLASS_SANITIZER}

# ─────────────────────────────────────────────
# Tracking & Compliance
# ─────────────────────────────────────────────
TRACK_RESET_SECONDS = 30       # reset state jika tidak terdeteksi
STREAM_FPS          = 15       # target FPS WebSocket stream
FRAME_QUEUE_SIZE    = 5        # max frame antrian per kamera

# ─────────────────────────────────────────────
# Snapshot
# ─────────────────────────────────────────────
SNAPSHOT_DIR = os.getenv("SNAPSHOT_DIR", "../snapshots")

# ─────────────────────────────────────────────
# FastAPI
# ─────────────────────────────────────────────
API_HOST = "0.0.0.0"
API_PORT = 8001
LARAVEL_ORIGIN = os.getenv("LARAVEL_ORIGIN", "http://localhost:8000")
