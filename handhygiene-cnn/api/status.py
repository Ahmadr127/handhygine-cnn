"""
api/status.py — Health check & statistik sistem
"""
from fastapi import APIRouter
from datetime import date
from core.camera_manager import camera_manager
from utils.db import get_conn
import psycopg2.extras

router = APIRouter(prefix="/api", tags=["status"])


@router.get("/status")
def health_check():
    """Health check AI Service."""
    return {
        "status": "ok",
        "running_cameras": camera_manager.running_cameras(),
        "total_running": len(camera_manager.running_cameras()),
    }


@router.get("/stats/today")
def stats_today():
    """Statistik monitoring hari ini."""
    today = date.today().isoformat()
    with get_conn() as conn:
        with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
            cur.execute("""
                SELECT
                    COUNT(*) FILTER (WHERE status = 'patuh')       AS total_patuh,
                    COUNT(*) FILTER (WHERE status = 'tidak_patuh') AS total_tidak_patuh,
                    COUNT(*)                                        AS total
                FROM monitoring_logs
                WHERE DATE(waktu) = %s
            """, (today,))
            row = cur.fetchone()
            return dict(row)


@router.get("/logs/recent")
def recent_logs(limit: int = 20):
    """Log monitoring terbaru."""
    with get_conn() as conn:
        with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
            cur.execute("""
                SELECT ml.*, c.nama_kamera
                FROM monitoring_logs ml
                LEFT JOIN cameras c ON c.id = ml.camera_id
                ORDER BY ml.waktu DESC
                LIMIT %s
            """, (limit,))
            rows = cur.fetchall()
            return [dict(r) for r in rows]
