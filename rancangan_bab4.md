# Bab 4: Hasil dan Pembahasan

Bagian ini menyajikan hasil penelitian dan pembahasan yang disusun secara sistematis berdasarkan tahapan prosedur pengembangan sistem yang telah ditetapkan. Pembahasan mencakup hasil analisis kebutuhan, perancangan dan implementasi proses bisnis baru, analisis metode deteksi objek berbasis YOLOv8 dan pelacakan ByteTrack, serta evaluasi performa model dan logika kepatuhan.

---

## 4.1 Hasil Analisis Kebutuhan

Pada tahap awal penelitian, dilakukan pengumpulan dan analisis kebutuhan sebagai dasar dalam pengembangan sistem yang akan dibangun. Kebutuhan tersebut dianalisis melalui beberapa metode berikut:

### 1. Wawancara (Interview)
Berdasarkan hasil wawancara dengan pihak manajemen rumah sakit dan petugas PPI (detail transkrip terlampir pada Lampiran I), diperoleh informasi bahwa pelaksanaan kebersihan tangan oleh tenaga medis saat ini mengacu pada Standar Operasional Prosedur (SOP) utama yang memfokuskan pada penerapan **6 langkah** dan **5 momen** kebersihan tangan. Proses pemantauan kepatuhan saat ini berjalan melalui mekanisme observasi langsung oleh petugas Pencegahan dan Pengendalian Infeksi (PPI) di setiap unit keperawatan, di mana hasilnya didokumentasikan melalui sistem pelaporan daring.

Meskipun sistem pelaporan telah berjalan, pemantauan manual yang mengandalkan pengamatan manusia (*human observation*) memiliki keterbatasan alami. Pengawasan visual secara terus-menerus sulit dilakukan, terutama di area dengan mobilitas tinggi atau pada jam operasional sibuk. Hal ini memvalidasi urgensi pengembangan sistem monitoring otomatis berbasis *Computer Vision* sebagai instrumen pendukung yang konsisten. Sebagai acuan teknis awal, disepakati bahwa batas toleransi kesalahan deteksi (*error rate*) maksimal yang dapat diterima untuk uji coba awal adalah sebesar 30%.

### 2. Observasi (Observation)
Kegiatan observasi dilakukan dengan meninjau dan menganalisis data rekaman video *Closed-Circuit Television* (CCTV) di Rumah Sakit Azra, khususnya pada area lorong dan transisi tenaga medis. Observasi dilakukan melalui pemutaran ulang (*playback*) rekaman kamera pengawas yang telah ada untuk mengevaluasi kelayakan visual dan kualitas rekaman yang akan digunakan sebagai dataset masukan (*input*) sistem. Pengamatan difokuskan pada area tangkapan kamera yang mengarah ke fasilitas kebersihan tangan (waslap/wastafel/dispenser *hand sanitizer*) serta pintu masuk ruangan.

Melalui peninjauan rekaman CCTV tersebut, dilakukan evaluasi terhadap beberapa parameter teknis krusial:
- **Tingkat kejelasan resolusi video**: Mengidentifikasi apakah gestur tubuh dan pergerakan tangan tenaga medis terlihat jelas.
- **Kondisi pencahayaan**: Meninjau variasi pencahayaan pada pagi, siang, dan malam hari.
- **Sudut pandang (*angle*) dan luas cakupan (*field of view*)**: Mengidentifikasi potensi terhalangnya objek (*occlusion* atau *blind spot*) saat tenaga medis membawa peralatan medis atau mendekati area cuci tangan.

### 3. Analisis Proses Bisnis

Perbedaan utama antara proses bisnis lama dan proses bisnis baru terletak pada otomatisasi pemantauan tindakan, mekanisme pelacakan identitas objek, serta validasi kepatuhan secara *real-time* tanpa interaksi fisik langsung dari petugas PPI di lapangan.

#### A. Proses Bisnis Lama (BPMN Lama)
Skema proses bisnis lama dimulai dari tenaga medis yang melakukan tindakan kebersihan tangan saat terdapat indikasi 5 momen *hand hygiene* (misalnya sebelum masuk ke ruangan pasien dengan membawa instrumen medis). Petugas PPI harus hadir secara fisik untuk mengobservasi tindakan tersebut secara langsung. 
- Jika tindakan cuci tangan tidak terpantau oleh petugas PPI (karena keterbatasan waktu atau kehadiran), maka proses berhenti dan data kepatuhan tidak tercatat.
- Jika kepatuhan terpantau, petugas PPI akan menilai kesesuaian tindakan tersebut dengan SOP 6 langkah kebersihan tangan, lalu mencatat data tersebut secara manual untuk kemudian diinput ke dalam sistem pelaporan daring. Setelah data divalidasi oleh sistem, data disimpan di dalam database hingga laporan bulanan tersimpan.

#### B. Proses Bisnis Baru (BPMN Baru)
Skema proses bisnis baru mengeliminasi kebutuhan kehadiran fisik petugas PPI secara terus-menerus dengan memanfaatkan otomatisasi *Computer Vision* (Python FastAPI + YOLOv8 + ByteTrack) yang terintegrasi dengan Dashboard Laravel. 

Proses bisnis baru berjalan dengan alur sebagai berikut:
1. **Deteksi dan Tracking Objek**: CCTV menangkap aktivitas di area koridor. Sistem secara otomatis mendeteksi objek tenaga medis serta instrumen medis (seperti baki atau troli medis) menggunakan model YOLOv8. Setiap objek manusia yang terdeteksi diberikan identitas unik (*unique tracker ID* atau `person_id`) menggunakan algoritma ByteTrack untuk mempertahankan identitasnya lintas frame.
2. **Evaluasi Status Membawa Instrumen**: Sistem mengevaluasi apakah tenaga medis membawa instrumen medis (baki/troli). Jika membawa instrumen, status state machine untuk ID tersebut diubah menjadi `CARRYING_INSTRUMENT` (Membawa Instrumen). Jika tidak membawa instrumen, sistem menganggap subjek sebagai non-target (bukan tenaga medis yang akan melakukan tindakan klinis ke pasien) dan sistem kembali melakukan pemantauan normal.
3. **Pemantauan Zona Cuci Tangan**: Saat tenaga medis dengan status `CARRYING_INSTRUMENT` terpantau masuk ke koordinat spasial zona cuci tangan (wastafel atau *hand sanitizer*), sistem akan menghitung *dwell time* (waktu menetap). Jika subjek menetap di zona tersebut minimal selama 2 detik, statusnya diperbarui menjadi `HAND_WASHED`.
4. **Validasi Akhir di Zona Pintu**: Sistem memonitor pergerakan subjek hingga mendekati area pintu masuk ruangan. Pemicu validasi akhir dan perekaman log dilakukan **hanya ketika subjek terdeteksi masuk ke zona pintu**.
   - Jika subjek masuk ke zona pintu dengan status terakhir `HAND_WASHED`, sistem mengklasifikasikan kejadian sebagai **"Patuh"**.
   - Jika subjek masuk ke zona pintu namun masih berstatus `CARRYING_INSTRUMENT` (belum mencuci tangan), sistem mengklasifikasikan kejadian sebagai **"Tidak Patuh"**.
   - **Aturan Penyimpanan Log**: Jika tenaga medis terdeteksi membawa instrumen dan sempat mencuci tangan tetapi tidak melewati pintu masuk (hanya lewat di koridor), sistem **tidak akan menyimpan log** ke database. Hal ini memastikan bahwa statistik kepatuhan hanya merepresentasikan kejadian klinis yang sesungguhnya (momen sebelum kontak dengan lingkungan pasien di dalam ruangan).
5. **Penyimpanan Log dan Integrasi Laravel**: Setelah status divalidasi di zona pintu, layanan AI (Python) akan menyimpan log kepatuhan (berisi `person_id`, status, timestamp, tingkat keyakinan, dan bukti foto *snapshot*) langsung ke database PostgreSQL yang juga diakses oleh aplikasi berbasis Laravel. Data yang tersimpan secara otomatis memperbarui statistik kepatuhan pada Dashboard Laravel secara *real-time*.

---

## 4.2 Kebutuhan Sistem dan Aplikasi

Kebutuhan fungsional dan teknis sistem dijabarkan pada tabel-tabel di bawah ini untuk mendukung kelancaran implementasi sistem.

### 1. Kebutuhan Fitur Sistem (Fungsional)

| Aktor / Sub-sistem | Fitur | Keterangan |
|---|---|---|
| **Petugas PPI** | Manajemen Kamera | Menambahkan, mengedit, dan menghapus data kamera (USB, RTSP, atau file video). |
| | Manajemen Grup Monitoring | Mengelompokkan beberapa kamera ke dalam satu grup wilayah (lorong/bangsal) untuk pemantauan lintas-kamera (*cross-camera compliance*). |
| | Manajemen Zona Spasial | Mengonfigurasi koordinat poligon zona cuci tangan (wastafel/sanitizer) dan zona pintu masuk pada gambar tangkapan kamera secara interaktif. |
| | Kontrol Monitoring | Mengaktifkan (*Start*) atau menghentikan (*Stop*) proses monitoring AI pada kamera atau grup tertentu. |
| | Dashboard Real-time | Menampilkan *live stream* video dengan overlay *bounding box* ter-anotasi beserta grafik statistik kepatuhan hari ini. |
| | Laporan & Detail Log | Menampilkan riwayat kepatuhan dengan filter tanggal/status, dilengkapi dengan foto *snapshot* sebagai bukti visual deteksi. |
| **Tenaga Medis** | Pemantauan Pasif | Aktivitas membawa instrumen medis dan kebersihan tangan dipantau secara otomatis tanpa memerlukan interaksi fisik langsung dengan perangkat sistem. |
| **Sistem AI (Python)** | Deteksi Objek Real-time | Mendeteksi objek dinamis (tenaga kesehatan, baki medis, troli medis) menggunakan arsitektur YOLOv8. |
| | Multi-Object Tracking | Melacak identitas unik perawat lintas frame menggunakan ByteTrack. |
| | State Machine Kepatuhan | Memproses transisi status perawat secara spasial-temporal untuk menghasilkan status PATUH atau TIDAK PATUH. |
| | Auto-save & Webhook | Menyimpan data log dan gambar *snapshot* ke PostgreSQL, serta mengirimkan visualisasi frame melalui WebSocket. |

### 2. Kebutuhan Perangkat Keras (Hardware)

| No | Komponen | Spesifikasi Minimum |
|---|---|---|
| 1 | Processor | Intel® Core™ i5 Gen 10 @ 2.40GHz (8 CPUs) atau AMD Ryzen 5 setara |
| 2 | RAM | 8 GB DDR4 (Direkomendasikan 16 GB untuk pemrosesan multi-kamera) |
| 3 | Storage | SSD 256 GB |
| 4 | GPU (Opsional) | NVIDIA GPU dengan dukungan CUDA (misalnya GTX 1650 / RTX 3050) untuk akselerasi inferensi |
| 5 | Kamera | IP Camera RTSP, USB Webcam, atau File Video (.mp4) beresolusi minimal 720p |
| 6 | Perangkat I/O | Monitor, Keyboard, Mouse |

### 3. Kebutuhan Perangkat Lunak (Software)

| No | Software | Versi / Deskripsi |
|---|---|---|
| 1 | Sistem Operasi | Windows 10/11 atau Ubuntu 22.04 LTS |
| 2 | Bahasa Pemrograman | Python 3.10+ (Layanan AI) dan PHP 8.2+ (Web Dashboard) |
| 3 | Framework Web | Laravel 11 |
| 4 | Database | PostgreSQL |
| 5 | Web Server Helper | Node.js + Vite (untuk aset frontend Laravel) |
| 6 | Dependency Manager | Composer (PHP) dan pip (Python) |
| 7 | Browser Utama | Google Chrome / Mozilla Firefox |
| 8 | Code Editor | Visual Studio Code |

---

## 4.3 Hasil Analisis Metode

Metode utama yang diimplementasikan dalam penelitian ini adalah kombinasi *Convolutional Neural Network* (CNN) dengan arsitektur **YOLOv8** untuk deteksi objek dinamis, algoritma **ByteTrack** untuk pelacakan identitas, dan analisis geometris poligon (**Shapely**) untuk penentuan status zona.

```
       [ Input Frame ]
              │
              ▼
   ┌──────────────────────┐
   │    YOLOv8 Detector   │ ◄─── Menggunakan dual-model:
   │ (detect person &    │      - yolov8n.pt (person COCO)
   │  custom best.pt)     │      - best.pt (baki/troli medis)
   └──────────┬───────────┘
              │
              ▼
   ┌──────────────────────┐
   │  ByteTrack Tracker   │ ◄─── Memberikan Tracker ID unik per orang
   └──────────┬───────────┘
              │
              ▼
   ┌──────────────────────┐
   │ Shapely Zone Manager │ ◄─── Evaluasi geometris overlap koordinat
   └──────────┬───────────┘      kaki perawat vs poligon zona interaktif
              │
              ▼
   ┌──────────────────────┐
   │  Compliance Engine   │ ◄─── Mengelola State Machine per Tracker ID
   │   (State Machine)    │      (IDLE -> CARRYING -> WASHED -> PATUH/TIDAK)
   └──────────┬───────────┘
              │ (Kondisi: Masuk Zona Pintu)
              ▼
    [ Log DB & Snapshot ] ◄─── Dikirim ke PostgreSQL & Dashboard Laravel
```

### 1. Arsitektur YOLOv8 (CNN)
YOLOv8 (dikembangkan oleh Ultralytics) merupakan arsitektur CNN *single-stage detector* yang melakukan prediksi koordinat kotak pembatas (*bounding box*) dan probabilitas kelas secara simultan. Pada sistem ini, terdapat **pemisahan deteksi yang sangat penting untuk efisiensi komputasi**:
- **Objek Dinamis (Dilatih/Dideteksi oleh YOLOv8)**: Hanya mendeteksi objek bergerak yang membutuhkan pelacakan intensif, yaitu `tenaga_kesehatan` (menggunakan model pre-trained `yolov8n.pt` kelas person), serta `baki_medis` dan `troli_medis` (menggunakan model kustom `best.pt` hasil *fine-tuning*).
- **Objek Statis (Ditangani oleh Sistem Zona Poligon)**: Objek seperti `wastafel`, `hand_sanitizer`, dan `pintu_masuk` **tidak dilatih kembali pada model YOLOv8**. Pendeteksian objek statis menggunakan deteksi objek konvensional seringkali tidak stabil karena variasi sudut kamera (*view angle*) dan bayangan. Sebagai gantinya, objek statis ini didefinisikan secara manual sebagai koordinat poligon (*virtual zones*) melalui antarmuka web Laravel, dan dievaluasi secara geometris menggunakan library Python Shapely.

Secara struktural, YOLOv8 terdiri dari tiga bagian utama:
1. **Backbone**: Menggunakan arsitektur CSP (Cross Stage Partial) untuk mengekstrak fitur gambar dari pola sederhana hingga pola kompleks (misalnya bentuk baki medis).
2. **Neck**: Menggunakan PANet (Path Aggregation Network) untuk menggabungkan fitur dari berbagai skala resolusi agar dapat mendeteksi objek besar maupun kecil secara presisi.
3. **Head**: Menggunakan pendekatan *Anchor-Free* yang langsung memprediksi pusat objek untuk mempercepat kecepatan inferensi.

### 2. Penentuan Status Membawa Instrumen: Overlap Ratio vs Intersection over Union (IoU)
Dalam menentukan apakah seorang tenaga medis sedang membawa instrumen medis (baki/troli), analisis awal menggunakan perhitungan matematis *Intersection over Union* (IoU) standar dengan rumus:

$$\text{IoU} = \frac{\text{Luas } (A \cap B)}{\text{Luas } A + \text{Luas } B - \text{Luas } (A \cap B)}$$

Keterangan:
- $A$ = Bounding box objek manusia (`tenaga_kesehatan`);
- $B$ = Bounding box objek instrumen (`baki_medis` / `troli_medis`);
- $A \cap B$ = Area irisan (tumpang tindih) antara kotak $A$ dan kotak $B$.

**Masalah pada Penggunaan IoU**:
Karena ukuran fisik baki medis jauh lebih kecil dibandingkan ukuran tubuh manusia, bounding box baki medis hampir selalu berada sepenuhnya di dalam (*completely enclosed*) bounding box manusia. Hal ini menyebabkan nilai penyebut (Union atau gabungan luas) menjadi sangat besar, sehingga nilai IoU yang dihasilkan menjadi sangat kecil dan tidak pernah mencapai ambang batas yang ditetapkan (misalnya 0,30).

*Contoh Kasus Riil (IoU vs Overlap Ratio)*:
- Luas Bounding Box Orang ($A$): $200 \times 400 = 80.000\text{ piksel}^2$
- Luas Bounding Box Baki ($B$): $150 \times 60 = 9.000\text{ piksel}^2$ (baki berada 100% di dalam tubuh perawat)
- Luas Irisan ($A \cap B$): $9.000\text{ piksel}^2$

*Perhitungan IoU standar*:
$$\text{IoU} = \frac{9.000}{80.000 + 9.000 - 9.000} = \frac{9.000}{80.000} = 0,1125$$
Nilai IoU sebesar **0,11** berada di bawah ambang batas deteksi bawaan (0,30), sehingga perawat dideteksi **tidak membawa instrumen** (berstatus `Monitoring` ❌).

**Solusi: Penerapan Overlap Ratio**
Untuk mengatasi masalah tersebut, sistem menggunakan **Overlap Ratio** terhadap luas instrumen medis sebagai parameter keputusan, dengan rumus:

$$\text{Overlap Ratio} = \frac{\text{Luas } (A \cap B)}{\text{Luas } B}$$

Menggunakan data kasus riil yang sama:
$$\text{Overlap Ratio} = \frac{9.000}{9.000} = 1,0 \quad (100\%)$$
Karena nilai Overlap Ratio (1,0) melebihi batas keputusan yang ditetapkan yaitu **0,50 (50%)**, sistem secara akurat menyimpulkan bahwa tenaga medis tersebut sedang membawa instrumen medis (berstatus `CARRYING_INSTRUMENT` ✅).

### 3. Penentuan Status Cuci Tangan: Logika Spasial-Temporal (Shapely & Dwell Time)
Sistem mendeteksi apakah seorang tenaga medis melakukan tindakan cuci tangan melalui dua tahapan verifikasi logis: analisis spasial geometris dan analisis temporal (*dwell time*).

#### A. Analisis Spasial (Pengecekan Interseksi Poligon)
Area cuci tangan didefinisikan sebagai poligon interaktif $Z_{\text{poly}}$ melalui antarmuka web, sedangkan objek perawat yang bergerak direpresentasikan oleh *bounding box* $P_{\text{box}}$ dengan koordinat $[x_1, y_1, x_2, y_2]$.

Secara geometris, *bounding box* perawat diubah menjadi poligon persegi panjang:
$$P_{\text{box}} = \{ (x, y) \mid x_1 \le x \le x_2, y_1 \le y \le y_2 \}$$

Menggunakan pustaka **Shapely**, sistem melakukan operasi interseksi set spasial untuk mendeteksi kontak fisik:
$$\text{Intersects}(P_{\text{box}}, Z_{\text{poly}}) \iff P_{\text{box}} \cap Z_{\text{poly}} \neq \emptyset$$

Jika $\text{Intersects}(P_{\text{box}}, Z_{\text{poly}})$ bernilai **True**, artinya sebagian atau seluruh tubuh perawat telah menyentuh atau berada di dalam area fasilitas cuci tangan.

#### B. Analisis Temporal (Dwell Time & Grace Period)
Kontak spasial saja tidak cukup untuk menyatakan seseorang telah melakukan cuci tangan, karena subjek bisa saja hanya berjalan melewati wastafel tanpa melakukan tindakan kebersihan tangan. Oleh karena itu, sistem menerapkan filter waktu menetap (*dwell time*):
1. **Dwell Time Threshold**: Ketika perawat dengan ID pelacak $T_{\text{id}}$ pertama kali menyentuh zona pada waktu $t_{\text{entry}}$, sistem mencatat waktu tersebut. Status perawat diperbarui menjadi `HAND_WASHED` jika dan hanya jika durasi menetap memenuhi batas minimum:
   $$\text{Dwell Time} = t_{\text{current}} - t_{\text{entry}} \ge 2,0 \text{ detik}$$
2. **Grace Period (Toleransi Hilang Kontak)**: Jika terjadi kegagalan tracking atau perawat sedikit bergeser ke luar zona saat mencuci tangan, sistem memberikan masa tenggang (*grace period*) selama 5,0 detik sebelum meriset timer. Timer hanya akan dihapus jika subjek berada di luar zona secara terus-menerus melebihi batas toleransi tersebut:
   $$t_{\text{current}} - t_{\text{last\_in\_zone}} > 5,0 \text{ detik}$$
   Logika ini menjamin kestabilan deteksi meskipun terjadi interferensi visual/oklusi sesaat di sekitar wastafel.

### 4. Pelacakan Objek Antar Frame (ByteTrack)
Sistem menggunakan algoritma pelacakan multi-objek ByteTrack untuk menjaga konsistensi identitas (`person_id`) tenaga medis saat bergerak di area koridor. ByteTrack menonjol karena melacak objek tidak hanya pada deteksi dengan skor keyakinan tinggi, tetapi juga memanfaatkan deteksi berkeyakinan rendah (misalnya akibat pencahayaan buruk atau *motion blur*) dengan cara mencocokkannya menggunakan kemiripan lokasi spasial (*Kalman Filter*).

Parameter ByteTrack yang dikonfigurasi dalam sistem ini adalah:
- **Ambang keyakinan minimum pelacakan**: 0,35 (menghindari pembuatan ID pelacak baru untuk deteksi palsu/noise).
- **Toleransi kehilangan (*frame loss tolerance*)**: 30 frame (jika perawat terhalang pilar atau keluar frame sesaat selama kurang dari 2 detik pada video 15 FPS, ID pelacaknya tetap dipertahaman).
- **Ambang kecocokan (*match threshold*)**: 0,80 (skor kemiripan minimal posisi deteksi baru agar diakui sebagai ID yang sama).

### 4. State Machine Kepatuhan (Compliance Engine)
Setelah identitas dilacak secara konsisten, status kepatuhan dievaluasi menggunakan *State Machine* per individu (`person_id`). 

```
            [ IDLE (Monitoring) ]
                      │
                      │ (Overlap Ratio baki/troli ≥ 0.50)
                      ▼
          [ CARRYING_INSTRUMENT ] ◄────────────────┐
                      │                            │
      ┌───────────────┴───────────────┐            │
      │ (Masuk zona cuci tangan       │ (Masuk     │ (Waktu jeda /
      │  dan dwell time ≥ 2 detik)    │  pintu     │  session timeout
      ▼                               │  langsung) │  > 180 detik)
 [ HAND_WASHED (Sudah Cuci Tangan) ]  │            │
      │                               │            │
      │ (Masuk zona pintu)            │            │
      ▼                               ▼            │
  [ PATUH ]                      [ TIDAK PATUH ] ──┘
 (Simpan Log & Snapshot)        (Simpan Log & Snapshot)
```

Untuk menghindari kegagalan deteksi akibat kondisi operasional di lapangan, dilakukan beberapa perbaikan logika pada *Compliance Engine* (v2):
1. **Penerapan Multi-person State Dictionary**: State disimpan secara unik per `person_id` menggunakan kamus terstruktur (`dict[str, PersonState]`). Hal ini menghentikan masalah *race condition* di mana status satu perawat tertimpa oleh perawat lain yang terekam pada kamera yang sama.
2. **Pre-emptive Wash Window**: Sistem mengizinkan pencatatan waktu mencuci tangan (`wash_time`) bahkan saat status perawat masih `IDLE` (belum membawa instrumen) atau sudah `CARRYING`. Logika temporal menetapkan rentang waktu aktif (*session window*) selama 180 detik (3 menit). Jika cuci tangan dan membawa instrumen terdeteksi dalam rentang waktu tersebut, tindakan dianggap sah saat perawat melewati pintu.
3. **Pemicu Log Tunggal pada Zona Pintu**: Untuk mencegah tersimpannya log sampah, evaluasi kepatuhan dan penulisan log database **hanya dipicu ketika perawat terdeteksi berada di zona pintu masuk**. Jika perawat membawa baki lalu mencuci tangan tetapi tidak masuk ke ruangan (hanya melintas), tidak ada data yang disimpan.

---

## 4.4 Penerapan Metode YOLOv8 pada Sistem

Bagian ini menjelaskan prosedur pelaksanaan teknis mulai dari pengumpulan dataset hingga pengujian inferensi model YOLOv8.

### 1. Pengumpulan Dataset
Meskipun sistem telah menggunakan **sistem zona poligon virtual (Shapely)**, pengumpulan dataset masih diperlukan khusus untuk **objek dinamis (bergerak)**. Alasan pemisahan perlakuan dataset ini dijabarkan sebagai berikut:
*   **Objek Dinamis (Wajib Dataset & Training)**: Objek seperti perawat (`tenaga_kesehatan`) dan instrumen medis (`baki_medis` / `troli_medis`) bergerak secara dinamis lintas waktu dan ruang di dalam koridor rumah sakit. Posisinya terus berubah pada setiap frame video, sehingga sistem **harus menggunakan model YOLOv8** untuk mendeteksinya secara dinamis. Oleh karena itu, dataset baki medis tetap dikumpulkan dan dilatih.
*   **Objek Statis (Bebas Dataset & Training)**: Fasilitas seperti wastafel cuci tangan, dispenser *hand sanitizer*, dan pintu masuk ruangan berada di posisi yang tetap (tidak berpindah tempat). Dengan mengganti peran deteksi objek YOLOv8 menjadi sistem **Zona Poligon Virtual**, kita **tidak perlu lagi mengumpulkan ribuan gambar wastafel, dispenser, atau pintu** pada berbagai kondisi cahaya dan sudut kamera. Hal ini meningkatkan efisiensi pengembangan secara masif, memangkas waktu anotasi gambar, serta memperkecil ukuran dataset pelatihan yang dibutuhkan secara signifikan.

- **Sumber Dataset Objek Dinamis**: Kombinasi dataset baki medis lokal (`tray_dataset_raw` sebanyak ~328 gambar) dan dataset sekunder dari Roboflow (`Medical Tray.v1i.yolov8`).
- **Pembagian Dataset**: Dataset dibagi secara otomatis dengan proporsi **80% untuk data pelatihan (*training set*)** dan **20% untuk data validasi (*validation set*)** menggunakan script [prepare_dataset.py](file:///e:/skripsi/sistem/training/prepare_dataset.py).
- **Konfigurasi Kelas (data.yaml)**: Karena perawat (`tenaga_kesehatan`) dideteksi menggunakan model *pre-trained* COCO (`yolov8n.pt`) untuk kategori `person`, kita tidak perlu melakukan pelatihan ulang ataupun mengumpulkan dataset manusia. Pelatihan model kustom (`best.pt`) **hanya difokuskan untuk mendeteksi instrumen medis** (`baki_medis`). Oleh karena itu, file konfigurasi [data.yaml](file:///e:/skripsi/sistem/dataset/data.yaml) yang digunakan dalam proses *fine-tuning* hanya memiliki 1 kelas saja:
  ```yaml
  path: E:/skripsi/sistem/dataset
  train: images/train
  val: images/val
  nc: 1
  names:
    0: baki_medis
  ```

  **Logika Integrasi Kelas pada Penerapan Inferensi:**
  Meskipun model kustom hanya melatih kelas `baki_medis` (indeks 0), sistem monitoring mengintegrasikan dua model sekaligus (*dual-model*) di dalam script [detector.py](file:///e:/skripsi/sistem/handhygiene-cnn/core/detector.py):
  1. Deteksi perawat diambil dari model bawaan `yolov8n.pt` pada **indeks 0 (person)** dan diberi nama kelas `tenaga_kesehatan`.
  2. Deteksi baki diambil dari model kustom `best.pt` pada **indeks 0 (baki_medis)**, yang kemudian secara programmatif diubah indeksnya menjadi **indeks 1** agar tidak bertabrakan dengan indeks perawat.
  3. Hasil deteksi dari kedua model kemudian digabungkan (*merge*) sebelum masuk ke modul pelacakan (*tracker*) dan evaluasi kepatuhan.

### 2. Pelabelan Gambar
Pelabelan gambar dilakukan menggunakan aplikasi **LabelImg** atau **CVAT** untuk membuat kotak pembatas (*bounding box*) di sekitar objek target (`baki_medis`).
- Proses anotasi menghasilkan file teks (`.txt`) dengan nama yang sama dengan file gambarnya.
- Setiap baris dalam file koordinat `.txt` mewakili satu objek dengan format:
  `<class_id> <x_center> <y_center> <width> <height>`
  Di mana koordinat tersebut telah dinormalisasi antara 0 hingga 1 terhadap resolusi gambar asli.

### 3. Persiapan Pelatihan Model
Pelatihan model dikonfigurasi melalui script [train.py](file:///e:/skripsi/sistem/training/train.py) dengan parameter pelatihan (*hyperparameters*) sebagai berikut:
- **Model Dasar**: `yolov8n.pt` (arsitektur YOLOv8 Nano pre-trained COCO untuk mempercepat konvergensi pelatihan).
- **Ukuran Gambar (*imgsz*)**: $640 \times 640$ piksel.
- **Jumlah Epoch**: 100 epoch.
- **Batch Size**: 16 (disesuaikan dengan kapasitas VRAM GPU).
- **Optimizer**: AdamW dengan *learning rate* awal ($\text{lr}_0$) = 0.001.
- **Patience**: 20 (Early stopping diaktifkan apabila nilai kerugian (*loss*) validasi tidak mengalami penurunan selama 20 epoch berturut-turut untuk mencegah *overfitting*).
- **Augmentasi Data**: Penerapan augmentasi dinamis seperti rotasi gambar ringan (10 derajat) untuk mensimulasikan kemiringan kamera CCTV, *horizontal flip* (50%), serta teknik *Mosaic* (1.0) dan *Mixup* (0.1) untuk melatih ketahanan model terhadap objek yang saling menumpuk.

### 4. Pelatihan Model YOLOv8
Proses pelatihan dijalankan pada terminal dengan memanggil modul Ultralytics. Selama proses pelatihan, sistem memantau grafik penurunan fungsi kerugian (*Loss Curves*) yang terdiri dari:
- **Box Loss**: Mengukur presisi koordinat *bounding box* prediksi terhadap koordinat asli.
- **Class Loss (Cls Loss)**: Mengukur keakuratan prediksi label kelas.
- **DFL Loss (Distribution Focal Loss)**: Membantu presisi penentuan batas tepi objek.

Model terbaik yang menghasilkan nilai kerugian validasi terendah diekspor secara otomatis sebagai model final bernama `best.pt` ke dalam direktori `models/`.

### 5. Hasil Deteksi dan Visualisasi Status
Model yang telah dilatih kemudian dijalankan dalam loop pemrosesan video *real-time* di layanan AI FastAPI. Hasil deteksi divisualisasikan langsung pada frame video dengan label status kepatuhan yang dinamis. 

Setiap status direpresentasikan dengan warna *bounding box* yang berbeda pada *live feed* monitoring:

| No | Label Status | Warna Box | Deskripsi Kondisi Transisi |
|---|---|---|---|
| 1 | **Monitoring** | Abu-abu | Status default saat objek tenaga kesehatan terdeteksi, namun tidak membawa instrumen medis. |
| 2 | **Membawa Instrumen** | Oranye | Muncul saat koordinat deteksi perawat dan instrumen medis memiliki rasio tumpang tindih (*Overlap Ratio*) $\ge 0,50$. |
| 3 | **Cuci Tangan...** | Kuning Redup | Perawat berada di area poligon zona cuci tangan, namun dwell time belum mencapai 2 detik. |
| 4 | **Cuci Tangan** | Kuning | Perawat terdeteksi berada di zona cuci tangan secara stabil selama $\ge 2$ detik. |
| 5 | **Sudah Cuci Tangan ✓** | Hijau-Tosca | Tindakan mencuci tangan telah terkonfirmasi oleh sistem, dan perawat sedang berjalan meninggalkan wastafel menuju ruangan. |
| 6 | **PATUH** | Hijau Terang | Perawat memasuki zona pintu dengan status terakhir `HAND_WASHED`. Kejadian ini secara otomatis memicu penyimpanan log "Patuh" dan snapshot ke database. |
| 7 | **TIDAK PATUH** | Merah | Perawat memasuki zona pintu dengan status `CARRYING_INSTRUMENT` tanpa melewati zona cuci tangan terlebih dahulu. Kejadian memicu penyimpanan log "Tidak Patuh" dan snapshot ke database. |
