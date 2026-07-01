"""
utils/db.py — Koneksi dan operasi PostgreSQL menggunakan psycopg2
"""
import psycopg2
import psycopg2.extras
from contextlib import contextmanager
from config import DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

_DSN = f"host={DB_HOST} port={DB_PORT} dbname={DB_NAME} user={DB_USER} password={DB_PASS}"


@contextmanager
def get_conn():
    """Context manager koneksi PostgreSQL."""
    conn = psycopg2.connect(_DSN)
    try:
        yield conn
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()


def init_tables():
    """Buat tabel jika belum ada (fallback jika Laravel migration belum jalan)."""
    sql = """
    CREATE TABLE IF NOT EXISTS monitoring_groups (
        id SERIAL PRIMARY KEY,
        nama_grup VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        lokasi VARCHAR(100),
        aktif BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT NOW(),
        updated_at TIMESTAMP DEFAULT NOW()
    );

    CREATE TABLE IF NOT EXISTS cameras (
        id SERIAL PRIMARY KEY,
        group_id INTEGER REFERENCES monitoring_groups(id) ON DELETE SET NULL,
        nama_kamera VARCHAR(100) NOT NULL,
        tipe VARCHAR(20) NOT NULL CHECK (tipe IN ('usb','rtsp','file')),
        source TEXT NOT NULL,
        aktif BOOLEAN DEFAULT FALSE,
        zona_config JSONB,
        created_at TIMESTAMP DEFAULT NOW(),
        updated_at TIMESTAMP DEFAULT NOW()
    );

    CREATE TABLE IF NOT EXISTS zones (
        id SERIAL PRIMARY KEY,
        group_id INTEGER REFERENCES monitoring_groups(id) ON DELETE CASCADE,
        camera_id INTEGER REFERENCES cameras(id) ON DELETE CASCADE,
        nama_zona VARCHAR(50) NOT NULL,
            tipe_zona VARCHAR(20) NOT NULL CHECK (tipe_zona IN ('sanitizer','wastafel')),
        id SERIAL PRIMARY KEY,
        person_id VARCHAR(50) NOT NULL,
        group_id INTEGER REFERENCES monitoring_groups(id) ON DELETE CASCADE,
        camera_id INTEGER REFERENCES cameras(id),
        waktu TIMESTAMP DEFAULT NOW(),
        status VARCHAR(20) NOT NULL CHECK (status IN ('patuh','tidak_patuh')),
        membawa_instrumen BOOLEAN DEFAULT FALSE,
        aktivitas_cuci_tangan BOOLEAN DEFAULT FALSE,
        snapshot_path TEXT,
        confidence DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT NOW()
    );
    """
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(sql)
    print("[DB] Tabel siap.")


def get_active_cameras():
    """Ambil semua kamera aktif beserta zona-nya."""
    with get_conn() as conn:
        with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
            cur.execute("""
                SELECT c.*, 
                       json_agg(z.*) FILTER (WHERE z.id IS NOT NULL) AS zones
                FROM cameras c
                LEFT JOIN zones z ON z.camera_id = c.id
                WHERE c.aktif = TRUE
                GROUP BY c.id
            """)
            return cur.fetchall()


def get_all_cameras():
    """Ambil semua kamera (aktif maupun tidak)."""
    with get_conn() as conn:
        with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
            cur.execute("SELECT * FROM cameras ORDER BY id")
            return cur.fetchall()


def insert_camera(nama, tipe, source):
    """Tambah kamera baru."""
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO cameras (nama_kamera, tipe, source) VALUES (%s, %s, %s) RETURNING id",
                (nama, tipe, source)
            )
            return cur.fetchone()[0]


def update_camera_status(camera_id: int, aktif: bool):
    """Toggle aktif/nonaktif kamera."""
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "UPDATE cameras SET aktif=%s, updated_at=NOW() WHERE id=%s",
                (aktif, camera_id)
            )


def delete_camera(camera_id: int):
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM cameras WHERE id=%s", (camera_id,))


def get_zones_by_camera(camera_id: int):
    with get_conn() as conn:
        with conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor) as cur:
            cur.execute("SELECT * FROM zones WHERE camera_id=%s", (camera_id,))
            return cur.fetchall()


def upsert_zone(group_id: int, camera_id: int, nama: str, tipe: str, points: list):
    """Insert atau update zona untuk kamera."""
    import json
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO zones (group_id, camera_id, nama_zona, tipe_zona, polygon_points)
                VALUES (%s, %s, %s, %s, %s)
                ON CONFLICT DO NOTHING
                RETURNING id
                """,
                (group_id, camera_id, nama, tipe, json.dumps(points))
            )


def insert_monitoring_log(person_id, group_id, camera_id, status, membawa_instrumen,
                           aktivitas_cuci_tangan, snapshot_path=None, confidence=None):
    """Simpan hasil monitoring ke database."""
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute(
                """
                INSERT INTO monitoring_logs
                (person_id, group_id, camera_id, status, membawa_instrumen,
                 aktivitas_cuci_tangan, snapshot_path, confidence)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                RETURNING id
                """,
                (str(person_id), group_id, camera_id, status, membawa_instrumen,
                 aktivitas_cuci_tangan, snapshot_path, confidence)
            )
            return cur.fetchone()[0]
