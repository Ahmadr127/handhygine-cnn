"""
api/stream.py — WebSocket endpoint untuk live video stream per kamera
"""
import asyncio
import json
import cv2
import base64
from fastapi import APIRouter, WebSocket, WebSocketDisconnect
from core.camera_manager import camera_manager
from utils.db import get_all_cameras

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

@router.websocket("/ws/preview/{camera_id}")
async def preview_camera(websocket: WebSocket, camera_id: int):
    """
    WebSocket stream untuk preview ringan saat mengatur zona.
    Jika kamera sudah monitoring, ambil frame dari queue.
    Jika belum, buka cv2.VideoCapture langsung tanpa deteksi YOLO.
    """
    await websocket.accept()
    print(f"[WS Preview] Client terhubung ke kamera {camera_id}")

    # Cek apakah sedang dimonitoring
    proc = camera_manager.get_processor(camera_id)
    if proc:
        try:
            while True:
                try:
                    payload = proc.frame_queue.get_nowait()
                    await websocket.send_text(json.dumps(payload))
                except Exception:
                    await asyncio.sleep(0.05)
        except WebSocketDisconnect:
            print(f"[WS Preview] Client disconnect dari kamera {camera_id}")
        return

    # Jika tidak jalan, buat stream mandiri (tanpa YOLO/monitoring)
    cameras = get_all_cameras()
    cam = next((c for c in cameras if c["id"] == camera_id), None)
    if not cam:
        await websocket.send_text(json.dumps({"error": "Kamera tidak ditemukan"}))
        await websocket.close()
        return

    source = cam["source"]
    if str(source).isdigit():
        source = int(source)

    cap = cv2.VideoCapture(source)
    if not cap.isOpened():
        await websocket.send_text(json.dumps({"error": "Gagal membuka kamera preview"}))
        await websocket.close()
        return

    try:
        while True:
            # Baca frame dalam thread terpisah agar event loop tidak terblokir
            ret, frame = await asyncio.to_thread(cap.read)
            if not ret:
                break
                
            frame = cv2.resize(frame, (640, 360))
            _, buffer = cv2.imencode('.jpg', frame, [cv2.IMWRITE_JPEG_QUALITY, 60])
            b64 = base64.b64encode(buffer).decode('utf-8')
            
            payload = {
                "camera_id": camera_id,
                "camera_name": cam["nama_kamera"],
                "frame": b64
            }
            
            await websocket.send_text(json.dumps(payload))
            await asyncio.sleep(0.05)
            
    except WebSocketDisconnect:
        print(f"[WS Preview] Client disconnect dari kamera {camera_id}")
    except Exception as e:
        print(f"[WS Preview] Error kamera {camera_id}: {e}")
    finally:
        cap.release()
