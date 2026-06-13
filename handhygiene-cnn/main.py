"""
main.py — FastAPI entry point untuk AI Service
Jalankan: uvicorn main:app --host 0.0.0.0 --port 8001 --reload
"""
import sys
import os

# Pastikan import relatif bekerja
sys.path.insert(0, os.path.dirname(__file__))

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager

from config import API_HOST, API_PORT, LARAVEL_ORIGIN
from utils.db import init_tables, get_active_cameras
from core.camera_manager import camera_manager
from api.cameras import router as cameras_router
from api.groups import router as groups_router
from api.stream import router as stream_router
from api.status import router as status_router


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup & shutdown logic."""
    print("=" * 50)
    print("  Hand Hygiene AI Monitoring Service")
    print("=" * 50)

    # Init database tables
    init_tables()

    # Auto-start kamera yang sebelumnya aktif
    active_cameras = get_active_cameras()
    for cam in active_cameras:
        print(f"[Startup] Auto-start kamera: {cam['nama_kamera']}")
        camera_manager.start_camera(cam["id"], cam["nama_kamera"], cam["source"], cam["group_id"])

    yield  # ← aplikasi berjalan di sini

    # Shutdown
    print("[Shutdown] Menghentikan semua kamera...")
    camera_manager.stop_all()
    print("[Shutdown] Selesai.")


app = FastAPI(
    title="Hand Hygiene Monitoring AI Service",
    description="YOLO + ByteTrack compliance monitoring API",
    version="1.0.0",
    lifespan=lifespan,
)

# CORS — izinkan Laravel origin
app.add_middleware(
    CORSMiddleware,
    allow_origins=[LARAVEL_ORIGIN, "http://localhost:8000", "http://127.0.0.1:8000"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Register routers
app.include_router(cameras_router)
app.include_router(groups_router)
app.include_router(stream_router)
app.include_router(status_router)


@app.get("/")
def root():
    return {
        "service": "Hand Hygiene AI Service",
        "version": "1.0.0",
        "docs": "/docs",
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host=API_HOST, port=API_PORT, reload=True)
