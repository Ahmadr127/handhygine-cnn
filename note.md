State yang Ditampilkan di Video
#	Label	Warna Kotak	Kapan Muncul
1	Monitoring	⬜ Abu-abu	Default — orang terdeteksi tapi tidak ada kondisi khusus
2	Membawa Instrumen	🟧 Oranye	Bbox orang overlap/dekat dengan bbox instrumen (jarum, dll)
3	Cuci Tangan...	🟡 Kuning redup	Bbox orang menyentuh zona wastafel, tapi < 2 detik
4	Cuci Tangan	🟡 Kuning	Orang di zona wastafel sudah ≥ 2 detik → terkonfirmasi
5	Sudah Cuci Tangan ✓	🟢 Hijau-tosca	Cuci tangan sudah tercatat, orang berjalan menuju pintu
6	PATUH	🟢 Hijau terang	Masuk zona pintu + instrumen + cuci tangan ✓ → tersimpan DB
7	TIDAK PATUH	🔴 Merah	Masuk zona pintu + instrumen ✓ + tidak cuci tangan → tersimpan DB

Sebelum (IoU):

Person bbox: 200×400 = 80.000 px
Tray bbox:   150×60  =  9.000 px  (di dalam person)
IoU = 9.000 / (80.000 + 9.000 - 9.000) = 0.11 ← di bawah 0.3 → Monitoring ❌
Sesudah (Overlap Ratio):

inter_area = 9.000 px  (nampan 100% di dalam bbox orang)
i_area     = 9.000 px  (luas nampan)
overlap_ratio = 9.000 / 9.000 = 1.0 ← di atas 0.5 → Membawa Instrumen ✅