"""
utils/snapshot.py — Simpan screenshot frame saat status terdeteksi
"""
import os
import cv2
from datetime import date, datetime
from config import SNAPSHOT_DIR


def save_snapshot(frame, person_id: str, status: str, camera_name: str = "") -> str:
    """
    Simpan frame sebagai JPEG.

    Returns:
        Path relatif snapshot (untuk disimpan ke DB)
    """
    today = date.today().isoformat()          # "2026-05-28"
    folder = os.path.join(SNAPSHOT_DIR, today)
    os.makedirs(folder, exist_ok=True)

    timestamp = datetime.now().strftime("%H%M%S")
    cam_tag = camera_name.replace(" ", "_") if camera_name else "cam"
    filename = f"person_{person_id}_{status}_{cam_tag}_{timestamp}.jpg"
    filepath = os.path.join(folder, filename)

    cv2.imwrite(filepath, frame, [cv2.IMWRITE_JPEG_QUALITY, 85])

    # Return path relatif dari root proyek
    rel_path = os.path.relpath(filepath, start=os.path.join(os.path.dirname(__file__), "../../"))
    return rel_path.replace("\\", "/")
