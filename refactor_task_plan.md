# Refactor Plan & Task List

## Tujuan Utama
- Hapus ketergantungan pada zona pintu untuk penentuan compliance.
- PATUH ditentukan saat cuci tangan selesai, bukan saat masuk zona pintu.
- Tetap pertahankan logika wajib cuci tangan bagi yang membawa instrumen.
- Tetap beri label PATUH untuk handwash tanpa instrumen (sunnah).
- Refactor frontend Laravel agar tidak memaksa zona pintu.

## Task

1. Backend Python - refactor compliance core
   - Ubah `handhygiene-cnn/core/group_compliance.py` agar handwash meng-finalize event.
   - Hapus dependency `report_door_entry` sebagai trigger log utama.
   - Tambahkan cleanup timeout yang menangani `tidak_patuh` jika instrumen terdeteksi tapi tidak cuci tangan.
   - Pastikan event hanya ditembak sekali per person per siklus.

2. Backend Python - refactor camera processing
   - Ubah `handhygiene-cnn/core/camera_manager.py` untuk menghapus logika zona pintu dan cooldown pintu.
   - Pastikan handwash confirmed (dwell ≥2 detik) langsung melaporkan `report_hand_wash()`.
   - Perbaiki label/status yang tampil dalam UI video.

3. Backend Python - update API zone validation
   - Ubah `handhygiene-cnn/api/cameras.py` agar tipe zona baru hanya `sanitizer` dan `wastafel`.

4. Laravel - update zone management UI & validation
   - Ubah `laravel-app/app/Http/Controllers/CameraController.php` validasi `tipe_zona`.
   - Ubah `laravel-app/resources/views/cameras/zones.blade.php` untuk menghapus tombol/legend pintu dan teks syarat.
   - Ubah `laravel-app/resources/views/groups/show.blade.php` untuk menghapus persyaratan zona pintu.

5. Verifikasi
   - Cek syntax Python pada file yang diubah.
   - Cek error file Laravel terkait jika memungkinkan.

## Status
- [x] Task 1: Backend Python - refactor compliance core
- [x] Task 2: Backend Python - refactor camera processing
- [x] Task 3: Backend Python - update API zone validation
- [x] Task 4: Laravel - update zone management UI & validation
- [x] Task 5: Verifikasi
