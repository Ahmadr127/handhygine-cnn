"""
api/stream.py — WebSocket endpoint untuk live video stream per kamera
"""
import asyncio
import json
from fastapi import APIRouter, WebSocket, WebSocketDisconnect
from core.camera_manager import camera_manager

router = APIRouter(tags=["stream"])


@router.websocket("/ws/stream/{camera_id}")
async def stream_camera(websocket: WebSocket, camera_id: int):
    """
    WebSocket live stream untuk satu kamera.
    Kirim payload JSON setiap frame:
    {
        "camera_id": int,
        "camera_name": str,
        "frame": "<base64 JPEG>",
        "timestamp": "ISO-8601"
    }
    """
    await websocket.accept()
    print(f"[WS] Client terhubung ke kamera {camera_id}")

    try:
        proc = camera_manager.get_processor(camera_id)
        if proc is None:
            await websocket.send_text(json.dumps({
                "error": f"Kamera {camera_id} tidak sedang berjalan"
            }))
            await websocket.close()
            return

        while True:
            # Non-blocking get dari frame queue
            try:
                payload = proc.frame_queue.get_nowait()
                await websocket.send_text(json.dumps(payload))
            except Exception:
                # Queue kosong, tunggu sebentar
                await asyncio.sleep(0.05)

    except WebSocketDisconnect:
        print(f"[WS] Client disconnect dari kamera {camera_id}")
    except Exception as e:
        print(f"[WS] Error kamera {camera_id}: {e}")
