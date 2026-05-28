from fastapi import APIRouter, HTTPException
from utils.db import get_all_cameras
from core.camera_manager import camera_manager

router = APIRouter(prefix="/api/groups", tags=["groups"])

@router.post("/{group_id}/start")
def start_group(group_id: int):
    """Mulai monitoring semua kamera dalam grup."""
    cameras = get_all_cameras()
    group_cams = [c for c in cameras if c["group_id"] == group_id]
    
    if not group_cams:
        raise HTTPException(404, "Tidak ada kamera dalam grup ini")

    camera_manager.start_group(group_id, group_cams)
    return {"message": f"Grup {group_id} dimulai dengan {len(group_cams)} kamera"}

@router.post("/{group_id}/stop")
def stop_group(group_id: int):
    """Hentikan monitoring semua kamera dalam grup."""
    camera_manager.stop_group(group_id)
    return {"message": f"Grup {group_id} dihentikan"}
