# Plan: Reset DB + Restore handhygine.sql + Analisis & Validasi Person ID

Status: **IN PROGRESS**
Update berkala: tiap task selesai, centang & catat hasil + timestamp.

## Ringkasan
- Restore dump `handwash_db` dari `E:\skripsi\handhygine.sql` (pg_dump 17.5 -> server lokal 14.5,
  pakai pg_restore 17.4 bundled, tanpa `-C` agar tidak kena `LOCALE_PROVIDER`).
- Person ID saat ini = nomor tracker ByteTrack polos (duplikat lintas kamera/waktu).
- Keputusan user:
  - Restore ke `handwash_db` + update `.env` (laravel-app & handhygiene-cnn).
  - Kolom `ID baru` (38 baris CSV) dipakai sebagai `person_id` baru di `monitoring_logs`.
  - Validasi CSV vs `monitoring_logs` = laporan + tulis hasil ke DB.

## Checklist Task
- [x] Langkah 0 - Buat file PLAN.md ini + todo list. (DONE 2026-08-19)
- [ ] Langkah 1 - Reset DB handwash_db: terminate koneksi -> DROP DATABASE -> CREATE DATABASE.
- [ ] Langkah 2 - Restore: pg_restore(17.4) --no-owner --no-privileges -d handwash_db;
      verifikasi jumlah baris monitoring_logs / cameras / zones / monitoring_groups.
- [ ] Langkah 2b - Update .env: laravel-app `DB_DATABASE=handwash_db`,
      handhygiene-cnn `DB_NAME=handwash_db`, lalu `php artisan config:clear`.
- [ ] Langkah 3 - Analisis id person 4 digit (opsi pad-nol / kunci kamera:tracker / ID global;
      dampak ke monitoring_logs.person_id, snapshot.py, label video, CSV).
- [ ] Langkah 4 - Analisis titik kode pemakai person_id berbasis kolom ID baru:
      camera_manager.make_person_key/display_tracker_id, group_compliance state machine,
      utils/db.insert_monitoring_log, snapshot.py, ConfusionMatrixController.
- [ ] Langkah 5 - Validasi CSV vs monitoring_logs + tulis: script Python (psycopg2),
      cocokkan (person_id, kamera, menit waktu, confidence, status) + cek kolom lanjutan,
      UPDATE person_id=<ID baru> untuk 38 baris, simpan laporan.
- [ ] Verifikasi akhir - Ringkasan baris terupdate, hasil validasi, contoh sebelum/sesudah.

## Catatan Temuan
- Dump: dbname=handwash_db, Dump Version 1.16-0, dibuat 2026-08-19 22:03.
- handwash_db (lokal) sekarang kosong; handhygine (app aktif) punya 68 log, 1 kamera.
- CSV `confusion_matrix_20260720_033054.csv`: 320 baris, 38 ber-ID baru (5 digit), 282 kosong.

## Log Eksekusi
- 2026-08-19 — Mulai eksekusi.