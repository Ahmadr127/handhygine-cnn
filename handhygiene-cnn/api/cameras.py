"""
api/cameras.py — REST endpoints untuk manajemen kamera
"""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional
from utils.db import (
    get_all_cameras, insert_camera, delete_camera,
    get_zones_by_camera, upsert_zone
)
from core.camera_manager import camera_manager

router = APIRouter(prefix="/api/cameras", tags=["cameras"])


class CameraCreate(BaseModel):
    nama_kamera: str
    tipe: str       # 'usb' | 'rtsp' | 'file'
    source: str     # "0", "rtsp://...", "/path/to/file.mp4"


class ZoneCreate(BaseModel):
    group_id: int
    nama_zona: str
    tipe_zona: str   # 'sanitizer' | 'wastafel' | 'pintu'
    polygon_points: list[dict]  # [{"x": int, "y": int}, ...]


@router.get("")
def list_cameras():
    """Daftar semua kamera."""
    cameras = get_all_cameras()
    result = []
    for cam in cameras:
        result.append({
            **dict(cam),
            "is_running": camera_manager.is_running(cam["id"]),
        })
    return result


@router.post("")
def add_camera(data: CameraCreate):
    """Tambah kamera baru."""
    # Validasi tipe
    if data.tipe not in ("usb", "rtsp", "file"):
        raise HTTPException(400, "tipe harus 'usb', 'rtsp', atau 'file'")

    cam_id = insert_camera(data.nama_kamera, data.tipe, data.source)
    return {"id": cam_id, "message": "Kamera berhasil ditambahkan"}


@router.delete("/{camera_id}")
def remove_camera(camera_id: int):
    """Hapus kamera (stop dulu jika sedang berjalan)."""
    if camera_manager.is_running(camera_id):
        camera_manager.stop_camera(camera_id)
    delete_camera(camera_id)
    return {"message": "Kamera dihapus"}


@router.post("/{camera_id}/start")
def start_camera(camera_id: int):
    """Mulai monitoring kamera."""
    cameras = get_all_cameras()
    cam = next((c for c in cameras if c["id"] == camera_id), None)
    if not cam:
        raise HTTPException(404, "Kamera tidak ditemukan")

    if camera_manager.is_running(camera_id):
        return {"message": "Kamera sudah berjalan"}

    ok = camera_manager.start_camera(camera_id, cam["nama_kamera"], cam["source"], cam["group_id"])
    return {"message": "Kamera dimulai" if ok else "Gagal memulai kamera"}


@router.post("/{camera_id}/stop")
def stop_camera(camera_id: int):
    """Hentikan monitoring kamera."""
    camera_manager.stop_camera(camera_id)
    return {"message": "Kamera dihentikan"}


@router.get("/{camera_id}/zones")
def get_zones(camera_id: int):
    """Ambil zona untuk kamera tertentu."""
    return get_zones_by_camera(camera_id)


@router.post("/{camera_id}/zones")
def add_zone(camera_id: int, data: ZoneCreate):
    """Tambah zona baru untuk kamera."""
    if data.tipe_zona not in ("sanitizer", "wastafel", "pintu"):
        raise HTTPException(400, "tipe_zona harus 'sanitizer', 'wastafel', atau 'pintu'")

    upsert_zone(data.group_id, camera_id, data.nama_zona, data.tipe_zona, data.polygon_points)

    # Reload zone di processor yang sedang berjalan
    proc = camera_manager.get_processor(camera_id)
    if proc:
        proc.zone_mgr.reload()

    return {"message": "Zona berhasil ditambahkan"}


@router.get("/scan/usb")
def scan_usb_cameras():
    """Scan USB/webcam yang tersedia di device."""
    available = []
    for i in range(6):  # cek index 0-5
        cap = __import__("cv2").VideoCapture(i)
        if cap.isOpened():
            available.append({"index": i, "source": str(i), "label": f"USB Camera {i}"})
            cap.release()
    return available
