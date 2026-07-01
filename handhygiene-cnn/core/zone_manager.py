"""
core/zone_manager.py — Manajemen polygon zone per kamera
Digunakan untuk mendeteksi apakah seseorang berada di zona tertentu.
"""
import json
from shapely.geometry import Point, Polygon
from utils.db import get_zones_by_camera


class ZoneManager:
    """
    Mengelola polygon zone per kamera.
    Zone diload dari database dan di-cache saat kamera start.

    Zone types:
        'sanitizer' — area hand sanitizer
        'wastafel'  — area wastafel/sink
        'pintu'     — area pintu masuk (deprecated, hanya masih ditampilkan jika tersimpan di DB)
    """

    def __init__(self, camera_id: int):
        self.camera_id = camera_id
        self.zones: list[dict] = []  # [{id, nama_zona, tipe_zona, polygon: Polygon}]
        self.load_zones()

    def load_zones(self):
        """Load zone dari database."""
        rows = get_zones_by_camera(self.camera_id)
        self.zones = []
        for row in rows:
            points_raw = row["polygon_points"]
            if isinstance(points_raw, str):
                points = json.loads(points_raw)
            else:
                points = points_raw  # sudah dict/list dari psycopg2

            try:
                polygon = Polygon([(p["x"], p["y"]) for p in points])
                self.zones.append({
                    "id": row["id"],
                    "nama": row["nama_zona"],
                    "tipe": row["tipe_zona"],
                    "polygon": polygon,
                })
            except Exception as e:
                print(f"[ZoneManager] Error parsing zone {row['id']}: {e}")

        print(f"[ZoneManager] Kamera {self.camera_id}: {len(self.zones)} zona dimuat")

    def reload(self):
        """Reload zone dari database (panggil setelah zone diupdate)."""
        self.load_zones()

    def check_point(self, x: float, y: float) -> list[str]:
        """
        Cek apakah titik (x, y) berada di dalam zone manapun.

        Returns:
            List tipe_zona yang mengandung titik ini.
        """
        pt = Point(x, y)
        matched = []
        for zone in self.zones:
            if zone["polygon"].contains(pt):
                matched.append(zone["tipe"])
        return matched

    def is_in_handwash_zone(self, x: float, y: float) -> bool:
        """True jika titik ada di zona sanitizer atau wastafel."""
        zones = self.check_point(x, y)
        return "sanitizer" in zones or "wastafel" in zones

    def bbox_intersects_handwash_zone(self, x1: float, y1: float, x2: float, y2: float) -> bool:
        """
        True jika bounding box (x1,y1,x2,y2) bersentuhan atau overlap dengan zona wastafel/sanitizer.
        Lebih fleksibel dibanding cek satu titik — cocok untuk gerakan tidak konsisten.
        """
        from shapely.geometry import box as shapely_box
        person_rect = shapely_box(x1, y1, x2, y2)
        for zone in self.zones:
            if zone["tipe"] in ("sanitizer", "wastafel"):
                if zone["polygon"].intersects(person_rect):
                    return True
        return False

    def is_in_door_zone(self, x: float, y: float) -> bool:
        """True jika titik ada di zona pintu."""
        zones = self.check_point(x, y)
        return "pintu" in zones

    def has_zones(self) -> bool:
        return len(self.zones) > 0


