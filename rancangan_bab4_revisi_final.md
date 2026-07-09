# BAB IV
# HASIL DAN PEMBAHASAN

## A. Deskripsi Objek Penelitian

Penelitian ini dilakukan di Rumah Sakit Azra, khususnya pada koridor transisi dan bangsal keperawatan yang menjadi area dengan mobilitas tenaga medis yang tinggi. Kebersihan tangan (*hand hygiene*) merupakan pilar utama dalam program Pencegahan dan Pengendalian Infeksi (PPI) di rumah sakit untuk mencegah terjadinya infeksi nosokomial (*Healthcare-Associated Infections* atau HAIs). Rumah sakit telah menerapkan prosedur tetap (SOP) kebersihan tangan yang mengacu pada standar *World Health Organization* (WHO) mengenai 5 Momen Kebersihan Tangan, khususnya Momen 1 yaitu melakukan tindakan cuci tangan sebelum melakukan kontak atau tindakan klinis kepada pasien. Dalam aktivitas sehari-hari, salah satu indikasi kuat bahwa tenaga medis akan berinteraksi langsung dengan pasien di dalam ruangan adalah ketika mereka membawa baki medis (*medical tray*) atau troli medis yang berisi instrumen medis.

Meskipun SOP kebersihan tangan telah ditetapkan secara ketat, proses pemantauan kepatuhan di Rumah Sakit Azra selama ini masih dilakukan secara manual melalui pengamatan langsung (*direct observation*) oleh petugas PPI (Infection Prevention Control Link Nurse atau IPCLN). Pengawasan manual ini memiliki keterbatasan signifikan, seperti keterbatasan waktu petugas, subjektivitas penilaian, serta munculnya *Hawthorne Effect* (perubahan perilaku tenaga medis menjadi lebih patuh hanya ketika merasa sedang diawasi oleh petugas). Situasi ini menyulitkan komite PPI dalam mendapatkan data statistik kepatuhan yang akurat dan kontinu 24 jam.

Untuk mengatasi permasalahan tersebut, dalam penelitian ini dikembangkan sistem monitoring kepatuhan kebersihan tangan otomatis berbasis *Computer Vision* dengan mengintegrasikan kamera pengawas (IP Camera/CCTV), modul pemrosesan AI berbasis Python FastAPI (YOLOv8 + ByteTrack + Shapely), serta web dashboard berbasis Laravel 11. Sistem dirancang untuk melacak pergerakan tenaga kesehatan secara pasif, mendeteksi apakah mereka sedang membawa baki medis, memverifikasi durasi cuci tangan di zona wastafel/sanitizer minimal selama 2 detik, serta mencatat status kepatuhan secara otomatis tanpa mengganggu aktivitas pelayanan medis.

---

## B. Hasil Penelitian dan Pengembangan

Hasil penelitian dan pengembangan sistem ini disajikan berdasarkan tahapan dalam prosedur pengembangan sistem yang digunakan. Adapun tahapan tersebut meliputi:

### 1. Analisis Kebutuhan dan Hasil Analisis Kebutuhan

#### a. Analisis Kebutuhan

Tahap ini diawali dengan pengumpulan informasi kebutuhan sistem dari lokasi penelitian guna memahami kondisi aktual di lapangan. Informasi tersebut dianalisis untuk menentukan spesifikasi sistem yang sesuai dengan kebutuhan operasional pemantauan kebersihan tangan. Data dikumpulkan melalui dua pendekatan, yaitu observasi dan wawancara.

**(a) Observasi**

Observasi dilakukan di koridor dan bangsal keperawatan Rumah Sakit Azra untuk memahami alur pergerakan tenaga medis serta posisi fasilitas kebersihan tangan (wastafel dan dispenser *hand sanitizer*). Berdasarkan hasil pengamatan, area koridor merupakan jalur transisi utama di mana tenaga medis berlalu-lalang membawa baki medis menuju kamar pasien. Fasilitas cuci tangan berupa wastafel dan dispenser *hand sanitizer* telah tersedia di sepanjang koridor bangsal, khususnya di area dekat pintu masuk ruangan pasien.

Kegiatan observasi juga dilakukan melalui peninjauan rekaman video CCTV (*Closed-Circuit Television*) yang sudah terpasang di rumah sakit. Pengamatan difokuskan pada area tangkapan kamera yang mengarah ke fasilitas kebersihan tangan serta pintu masuk ruangan pasien. Melalui peninjauan rekaman CCTV tersebut, dilakukan evaluasi terhadap beberapa parameter teknis krusial:
- **Tingkat kejelasan resolusi video**: Mengidentifikasi apakah gestur tubuh dan pergerakan tangan tenaga medis terlihat jelas. Evaluasi menunjukkan bahwa resolusi minimal 720p pada *frame rate* 15 FPS sudah mencukupi untuk mendeteksi aktivitas membawa baki medis.
- **Kondisi pencahayaan**: Meninjau variasi pencahayaan pada pagi, siang, dan malam hari di area koridor rumah sakit yang berpengaruh terhadap kualitas pendeteksian.
- **Sudut pandang (*angle*) dan luas cakupan (*field of view*)**: Mengidentifikasi potensi terhalangnya objek (*occlusion* atau *blind spot*) saat tenaga medis berpapasan atau bergerombol di depan area wastafel. Kamera pemantau diposisikan menghadap ke wastafel dan koridor untuk menangkap sudut pandang pergerakan secara optimal.

Seluruh proses pemantauan kepatuhan kebersihan tangan masih dilakukan secara visual langsung oleh petugas PPI tanpa bantuan sistem kamera otomatis atau deteksi berbasis komputer.

**(b) Wawancara**

Wawancara dilakukan dengan komite PPI dan pihak manajemen Rumah Sakit Azra untuk memperoleh informasi yang lebih mendalam mengenai proses pemantauan dan evaluasi kepatuhan kebersihan tangan (*hand hygiene*). Berdasarkan keterangan yang diperoleh, seluruh tenaga medis diwajibkan menerapkan prosedur 6 langkah dan 5 momen kebersihan tangan sesuai standar WHO, khususnya Momen 1 (sebelum kontak dengan pasien). Perawat yang membawa instrumen medis menuju kamar pasien wajib melakukan kebersihan tangan terlebih dahulu di wastafel atau dispenser *hand sanitizer* yang tersedia di koridor.

Untuk mendukung aktivitas pemantauan, rumah sakit menugaskan IPCLN (*Infection Prevention Control Link Nurse*) di setiap unit keperawatan. Namun, petugas IPCLN mengakui bahwa pemantauan manual secara terus-menerus sulit dilakukan, terutama di area dengan mobilitas tinggi atau pada jam operasional sibuk. Pemantauan kepatuhan hanya dapat dilakukan pada waktu-waktu tertentu secara sampling, sehingga data yang dihasilkan tidak merepresentasikan kondisi kepatuhan yang sebenarnya secara menyeluruh. Selain itu, terdapat fenomena *Hawthorne Effect* di mana tenaga medis cenderung berperilaku lebih patuh hanya ketika merasa sedang diawasi oleh petugas.

Oleh karena itu, komite PPI membutuhkan alat bantu berupa sistem pemantauan otomatis yang dapat bekerja secara pasif tanpa memerlukan kehadiran fisik petugas secara terus-menerus. Sistem tersebut diharapkan mampu menyajikan dashboard visualisasi data kepatuhan harian secara *real-time*, lengkap dengan bukti visual berupa foto *snapshot* kejadian untuk keperluan audit internal. Sebagai acuan teknis awal, disepakati bahwa batas toleransi kesalahan deteksi (*error rate*) maksimal yang dapat diterima untuk uji coba awal adalah sebesar 30%.

#### b. Hasil Analisa Kebutuhan

**(a) Deskripsi**

Hasil observasi dan wawancara menunjukkan bahwa pemantauan kepatuhan kebersihan tangan masih dilakukan secara langsung oleh petugas PPI. Pemantauan ini bergantung pada jadwal kehadiran petugas IPCLN di masing-masing unit keperawatan dan dilakukan berdasarkan panduan SOP 5 momen serta 6 langkah cuci tangan sesuai standar WHO. Salah satu permasalahan utama yang ditemukan adalah ketidakmampuan petugas untuk mengawasi seluruh aktivitas tenaga medis secara kontinu, terutama pada area koridor dengan mobilitas tinggi dan jam operasional sibuk. Hal ini menyebabkan data kepatuhan yang tercatat tidak sepenuhnya akurat, karena pemantauan hanya dapat dilakukan secara sampling pada waktu-waktu tertentu saja. Selain itu, munculnya fenomena *Hawthorne Effect* membuat data observasi manual tidak merepresentasikan perilaku kebersihan tangan yang sesungguhnya.

Walaupun sistem pelaporan daring telah digunakan untuk mendokumentasikan hasil observasi, sistem tersebut belum menyediakan mekanisme verifikasi visual secara langsung. Pemantauan masih mengandalkan kehadiran fisik petugas di lapangan, tanpa adanya sistem otomatis yang dapat mendeteksi dan mencatat pelanggaran kepatuhan secara independen. Oleh karena itu, dibutuhkan sistem tambahan yang mampu memantau aktivitas kebersihan tangan secara pasif dan otomatis, menyajikan bukti visual (*snapshot*), serta menyimpan data kepatuhan untuk keperluan evaluasi dan audit tanpa mengharuskan petugas PPI selalu berada di lokasi pemantauan.

**(b) Kesimpulan**

Pemantauan kepatuhan kebersihan tangan masih bergantung pada kehadiran petugas PPI di lapangan. Dalam kondisi mobilitas tinggi dan jam sibuk, pelanggaran kepatuhan sering kali tidak terpantau meskipun fasilitas cuci tangan telah tersedia di sepanjang koridor. Hal ini menunjukkan perlunya sistem pendukung pemantauan berbasis visual otomatis yang dapat mendeteksi aktivitas tenaga medis dan mencatat kepatuhan kebersihan tangan secara lebih cepat, konsisten, dan objektif. Sistem monitoring kepatuhan yang akan dikembangkan membutuhkan tiga komponen utama: (1) IP Camera/CCTV sebagai pengambil gambar, (2) Python FastAPI sebagai *engine* AI (detektor YOLOv8, *tracker* ByteTrack, dan analisis spasial Shapely), serta (3) Laravel 11 sebagai aplikasi web pengelola data dan dashboard visualisasi.

---

### 2. Hasil Analisa Kebutuhan

#### a. Hasil Analisis Alur Komunikasi IoT

Penelitian ini diawali dengan memahami kebutuhan sistem untuk memantau aktivitas kebersihan tangan tenaga medis secara *real-time*. Berdasarkan hasil pengamatan, sistem perlu dirancang agar mampu menangkap aliran video secara kontinu dari kamera pengawas, memproses setiap frame menggunakan algoritma deteksi objek, dan menyajikan hasilnya kepada pengguna melalui antarmuka web. Pendekatan *Internet of Things* (IoT) digunakan dalam sistem ini untuk menghubungkan IP Camera/CCTV, server pemroses AI (Python FastAPI), dan aplikasi web (Laravel 11) sebagai sistem yang saling terhubung.

Arsitektur sistem terdiri atas empat komponen utama, yaitu kamera pengawas (IP Camera/CCTV) sebagai perangkat pengambil gambar, layanan AI Python FastAPI sebagai pemroses citra menggunakan algoritma deteksi objek dan pelacakan, basis data PostgreSQL sebagai pusat penyimpanan data log kepatuhan, serta aplikasi web Laravel 11 yang menyajikan dashboard visualisasi dan kontrol monitoring. Keempat komponen ini saling terhubung melalui jaringan lokal (*Local Area Network*) dan berkomunikasi menggunakan protokol HTTP/REST API dan WebSocket.

Alur kerja sistem diawali ketika IP Camera/CCTV mengirimkan aliran video mentah melalui protokol RTSP ke layanan AI Python. Layanan AI memproses setiap frame video secara *real-time* untuk mendeteksi keberadaan tenaga medis dan instrumen medis, melacak identitas unik setiap orang menggunakan ByteTrack, serta mengevaluasi status kepatuhan menggunakan *Compliance Engine*. Jika status kepatuhan (Patuh/Tidak Patuh) telah difinalisasi, sistem Python akan langsung melakukan operasi SQL INSERT ke database PostgreSQL bersama dan menyimpan file gambar *snapshot* ke dalam direktori *storage* Laravel. Secara simultan, frame video ter-anotasi dikirimkan ke web browser Admin via WebSocket dalam format Base64 JPEG dengan kecepatan 15 FPS untuk menyajikan tampilan *live feed*. Diagram alur komunikasi sistem IoT ditunjukkan pada Gambar 4.1 Alur Komunikasi IoT.

**Gambar 4. 1 Alur Komunikasi IoT**

```mermaid
sequenceDiagram
    autonumber
    participant CCTV as IP Camera / CCTV
    participant Python as Python FastAPI (AI Service)
    participant DB as Database PostgreSQL
    participant Laravel as Laravel Web App
    participant Admin as Admin Browser (Dashboard)

    CCTV->>Python: Kirim Video Stream (RTSP)
    loop Pemrosesan Frame Real-Time
        Python->>Python: Deteksi YOLOv8 & Tracking ByteTrack
        Python->>Python: Evaluasi Compliance Spasial-Temporal (Shapely)
    end
    rect rgb(240, 248, 255)
        Note over Python,DB: Jika Event Kepatuhan Difinalisasi (Patuh/Tidak Patuh)
        Python->>DB: INSERT log kepatuhan
        Python->>Laravel: Simpan file snapshot (.jpg) ke storage
    end
    Python->>Admin: Kirim Frame Video Ter-anotasi (WebSocket - Base64)
    Admin->>Laravel: HTTP GET/POST (Kelola kamera/grup/konfigurasi)
    Laravel->>Python: REST API Sync (Start/Stop/Update poligon)
    Laravel->>DB: Query data log & statistik
    Laravel->>Admin: Tampilkan halaman dashboard & laporan
```


Diagram ini menggambarkan alur komunikasi sistem berbasis IoT yang terdiri atas pengambilan video oleh IP Camera/CCTV, pemrosesan AI oleh layanan Python FastAPI, penyimpanan data ke basis data PostgreSQL, hingga penyajian hasil deteksi kepada pengguna melalui antarmuka web Laravel. Seluruh proses berjalan secara *real-time* dan terhubung dalam jaringan lokal dengan memanfaatkan REST API dan WebSocket sebagai jembatan pertukaran data.

Dengan menggabungkan IP Camera/CCTV, layanan AI Python FastAPI, basis data PostgreSQL, serta antarmuka web Laravel, sistem ini mampu membentuk suatu alur kerja yang saling terhubung melalui jaringan lokal. Pendekatan ini menunjukkan bahwa teknologi IoT dapat diterapkan untuk menghasilkan sistem pemantauan kepatuhan kebersihan tangan berbasis *Computer Vision* yang terintegrasi dan dapat diakses secara *real-time*. Integrasi tersebut mendukung proses pengambilan video, pemrosesan AI, pencatatan log kepatuhan, hingga penyajian hasil secara efisien kepada petugas PPI.

#### b. Hasil Analisis Metode Deteksi Objek Berbasis YOLOv8 & Tracking ByteTrack

Salah satu komponen penting dalam sistem ini adalah proses deteksi objek untuk mengenali keberadaan tenaga medis dan instrumen medis (baki medis) dalam setiap frame video. Proses ini dilakukan menggunakan algoritma YOLOv8 (*You Only Look Once* versi 8), yaitu sebuah metode deteksi objek berbasis pembelajaran mendalam (*deep learning*) yang mampu mengenali beberapa objek dalam satu gambar secara cepat dan akurat. Alur proses deteksi objek yang diterapkan dalam penelitian ini dapat dilihat pada Gambar 4.2 Diagram proses deteksi objek menggunakan YOLOv8.

**Gambar 4. 2 Diagram Proses Deteksi Objek Menggunakan YOLOv8**

```mermaid
flowchart TD
    Input[Input Frame Video] --> DualModel{YOLOv8 Dual-Model}
    DualModel -->|Person Detection| PersonModel[yolov8n.pt (COCO)]
    DualModel -->|Medical Tray Detection| CustomModel[best.pt (Fine-Tuning)]
    
    PersonModel --> Tracker[ByteTrack Tracker]
    Tracker -->|Detections with Tracker ID| ZoneMgr[Shapely Zone Manager]
    
    CustomModel --> OverlapCheck{Proximity / Overlap Ratio >= 0.50?}
    OverlapCheck -->|Ya| UpdateCarry[Set carrying_instrument]
    OverlapCheck -->|Tidak| Normal[Monitoring biasa]
    
    ZoneMgr -->|Intersects Wastafel Zone & Dwell Time >= 2s| reportWash[report_hand_wash()]
    
    UpdateCarry --> Compliance[Group Compliance Engine]
    reportWash --> Compliance
    
    Compliance --> DoorCheck{Masuk Zona Pintu?}
    DoorCheck -->|Ya: Cuci Tangan & Bawa Baki| FinalPatuh[PATUH]
    DoorCheck -->|Ya: Bawa Baki, Tidak Cuci Tangan| FinalTidakPatuh[TIDAK PATUH]
    
    FinalPatuh --> LogDB[Simpan Log ke PostgreSQL & Snapshot ke Storage]
    FinalTidakPatuh --> LogDB
```


Gambar di atas menggambarkan alur proses mulai dari pengambilan frame video, deteksi objek menggunakan dual-model YOLOv8, pelacakan identitas unik menggunakan ByteTrack, evaluasi zona cuci tangan menggunakan Shapely, hingga evaluasi kepatuhan oleh *Compliance Engine*. Model yang telah dilatih digunakan oleh layanan AI Python FastAPI untuk memproses setiap frame video yang diterima dari IP Camera/CCTV, kemudian hasil evaluasi kepatuhan disimpan ke database PostgreSQL dan ditampilkan melalui dashboard web Laravel.

Dalam sistem ini, terdapat **pemisahan deteksi yang sangat penting untuk efisiensi komputasi**:
- **Objek Dinamis (Dideteksi oleh YOLOv8)**: Hanya mendeteksi objek bergerak yang membutuhkan pelacakan intensif, yaitu `tenaga_kesehatan` (menggunakan model *pre-trained* `yolov8n.pt` kelas *person*), serta `baki_medis` (menggunakan model kustom `best.pt` hasil *fine-tuning*).
- **Objek Statis (Ditangani oleh Sistem Zona Poligon)**: Objek seperti `wastafel` dan `hand_sanitizer` **tidak dilatih kembali pada model YOLOv8**. Sebagai gantinya, objek statis ini didefinisikan secara manual sebagai koordinat poligon (*virtual zones*) melalui antarmuka web Laravel, dan dievaluasi secara geometris menggunakan library Python Shapely.

Penerapan YOLOv8 dalam penelitian ini dilakukan melalui beberapa tahapan, yaitu pengumpulan dataset, pelabelan gambar, persiapan pelatihan model, pelatihan model, serta hasil deteksi. Uraian penerapan tersebut dijelaskan sebagai berikut:

**(1) Pengumpulan Dataset**

Pengumpulan dataset difokuskan khusus untuk **objek dinamis (bergerak)**, yaitu `baki_medis`. Objek statis seperti `wastafel`, `hand_sanitizer`, dan `pintu` tidak dimasukkan ke dalam dataset YOLOv8 karena posisinya yang selalu tetap di dinding. Dengan mengganti peran deteksi objek statis dari YOLOv8 ke sistem **Zona Poligon Virtual (Shapely)**, kita tidak perlu lagi mengumpulkan ribuan gambar wastafel atau dispenser pada berbagai kondisi cahaya dan sudut kamera. Hal ini meningkatkan efisiensi pengembangan secara masif, memangkas waktu anotasi gambar, serta memperkecil ukuran dataset pelatihan yang dibutuhkan secara signifikan.

Pengumpulan dataset dilakukan dengan mengambil gambar baki medis di lingkungan rumah sakit secara langsung. Jumlah dataset yang terkumpul adalah sebanyak ~328 gambar kustom yang dikombinasikan dengan dataset sekunder dari Roboflow (`Medical Tray.v1i.yolov8`). Proses pengambilan gambar dilakukan dengan memperhatikan variasi kondisi, baik dari segi latar belakang (*background*), pencahayaan, sudut pengambilan gambar, maupun variasi bentuk dan ukuran baki medis. Upaya ini bertujuan agar dataset yang dihasilkan lebih beragam dan mampu meningkatkan kemampuan model dalam melakukan deteksi objek pada berbagai situasi. Dataset yang digunakan dapat dilihat pada Gambar 4.3.

**Gambar 4. 3 Struktur Sample Dataset Baki Medis**

```
+-------------------------------------------------------------+
| [baki_medis] sampel citra training                          |
|                                                             |
|   Gambar 1: Nampan Plastik Biru (Pencahayaan Terang)        |
|   Gambar 2: Nampan Stainless Steel (Sudut Miring)           |
|   Gambar 3: Nampan Medis di atas Meja Dorong                |
+-------------------------------------------------------------+
```


Selanjutnya, seluruh gambar yang telah dikumpulkan dibagi menjadi dua kelompok data, yaitu data pelatihan (*training set*) dan data validasi (*validation set*). Pembagian dilakukan secara otomatis dengan proporsi **80% untuk data pelatihan** dan **20% untuk data validasi** menggunakan script `prepare_dataset.py`. Data pelatihan digunakan untuk melatih model YOLOv8 agar mampu mengenali objek baki medis secara akurat, sedangkan data validasi berfungsi untuk mengevaluasi kinerja model selama proses pelatihan sehingga dapat mencegah terjadinya *overfitting*.

Karena tenaga kesehatan (`person`) dideteksi menggunakan model *pre-trained* COCO (`yolov8n.pt`) yang sudah memiliki kemampuan mengenali manusia, kita tidak perlu melakukan pelatihan ulang ataupun mengumpulkan dataset manusia. Pelatihan model kustom (`best.pt`) **hanya difokuskan untuk mendeteksi instrumen medis** (`baki_medis`). Oleh karena itu, file konfigurasi `data.yaml` yang digunakan dalam proses *fine-tuning* hanya memiliki 1 kelas saja:
```yaml
path: E:/skripsi/sistem/dataset
train: images/train
val: images/val
nc: 1
names:
  0: baki_medis
```

**(2) Pelabelan Gambar**

Setelah proses pengumpulan dan pembagian dataset, tahap berikutnya adalah pelabelan pada setiap gambar. Pelabelan dilakukan menggunakan aplikasi **LabelImg** atau **CVAT**, yang berfungsi untuk menentukan letak objek pada citra dengan menggambar *bounding box* di sekitar objek target (`baki_medis`). Sebelum pelabelan dilakukan, terlebih dahulu disiapkan konfigurasi kelas yang memuat daftar kategori objek yang digunakan dalam penelitian ini.

Proses pelabelan dilakukan dengan cara memberikan *bounding box* pada objek baki medis di dalam gambar, kemudian menetapkan kelas sesuai kategori yang telah ditentukan. Hasil dari proses pelabelan menghasilkan file teks dengan ekstensi `.txt` yang memuat informasi berupa nomor indeks kelas dan koordinat *bounding box*. Setiap baris dalam file `.txt` mewakili satu objek dengan format:
`<class_id> <x_center> <y_center> <width> <height>`
Di mana koordinat tersebut telah dinormalisasi antara 0 hingga 1 terhadap resolusi gambar asli.

**Gambar 4. 4 Skema Bounding Box Pelabelan Gambar Menggunakan LabelImg**

```
+---------------------------------------------------+
|                                                   |
|     Perawat/Tenaga Medis                          |
|     +-------------------------+                   |
|     |                         |                   |
|     |    [Baki Medis]         |                   |
|     |    +---------------+    |                   |
|     |    |               |    |                   |
|     |    |   class 0     |    |                   |
|     |    +---------------+    |                   |
|     |                         |                   |
|     +-------------------------+                   |
|                                                   |
+---------------------------------------------------+
```


**Gambar 4. 5 Format Koordinat Normalisasi dalam File Label (.txt)**

```txt
0 0.512638 0.634125 0.150241 0.080512
```

Keterangan format:
- `0`: Indeks kelas objek (`baki_medis`).
- `0.512638`: Titik tengah X ($x_{\text{center}}$) ternormalisasi.
- `0.634125`: Titik tengah Y ($y_{\text{center}}$) ternormalisasi.
- `0.150241`: Lebar bounding box ($w$) ternormalisasi.
- `0.080512`: Tinggi bounding box ($h$) ternormalisasi.

**(3) Persiapan Pelatihan Model**

Sebelum proses pelatihan dilakukan, diperlukan sejumlah file pendukung yang harus disiapkan terlebih dahulu. File-file tersebut meliputi dataset, konfigurasi, dan bobot awal yang akan digunakan pada YOLOv8. Daftar lengkap file yang dipersiapkan dapat dilihat pada Tabel 4.1.

**Tabel 4. 1 Daftar Folder dan Berkas Persiapan Dataset**

| No | Nama Berkas / Folder | Keterangan |
|---|---|---|
| 1 | `train/` | Folder yang berisi kumpulan gambar dataset yang digunakan pada tahap pelatihan (*training set*). Gambar dalam folder ini merupakan berbagai variasi kondisi baki medis di rak, seperti perbedaan pencahayaan, sudut pengambilan gambar, dan jumlah baki yang berbeda. Data ini digunakan agar model dapat mempelajari ciri visual baki medis secara menyeluruh. |
| 2 | `val/` | Folder yang berisi kumpulan gambar dataset untuk tahap validasi (*validation set*). Gambar pada folder ini berbeda dengan yang terdapat pada folder `train/` karena digunakan untuk menguji kemampuan model dalam mengenali objek baru yang tidak terdapat pada data pelatihan. Data ini berfungsi untuk menilai tingkat akurasi dan kemampuan model dalam mengenali pola pada gambar yang belum pernah dilihat sebelumnya. |
| 3 | `data.yaml` | File konfigurasi utama YOLOv8 yang berisi informasi penting seperti lokasi dataset, jumlah kelas (`nc: 1`), serta daftar nama kelas (`names: {0: baki_medis}`). File ini menjadi acuan ketika model dijalankan agar sistem dapat mengenali struktur dataset dan mengatur parameter pelatihan sesuai kebutuhan penelitian. |
| 4 | `yolov8n.pt` | File bobot awal (*pre-trained weights*) yang diperoleh dari model YOLOv8 Nano yang telah dilatih menggunakan dataset umum (COCO dataset), kemudian disesuaikan (*fine-tuned*) dengan dataset penelitian agar model mampu mendeteksi baki medis secara lebih spesifik. |

**(4) Pelatihan Model YOLOv8**

Proses pelatihan model YOLOv8 dilakukan setelah seluruh dataset, file label, serta file konfigurasi dipersiapkan. Pelatihan ini bertujuan agar model mampu mengenali serta mengklasifikasikan objek baki medis sesuai dengan kategori yang telah ditentukan. Pada penelitian ini digunakan model dasar YOLOv8 Nano dengan bobot awal (*pre-trained weights*) `yolov8n.pt` yang telah dilatih sebelumnya pada dataset COCO. Bobot awal ini digunakan untuk mempercepat proses pelatihan dan meningkatkan akurasi, kemudian dilakukan pelatihan ulang (*fine-tuning*) menggunakan dataset penelitian agar model dapat mengenali baki medis secara spesifik.

Proses pelatihan dijalankan melalui script `train.py` dengan parameter pelatihan (*hyperparameters*) sebagai berikut:
*   *Base Model*: `yolov8n.pt` (arsitektur YOLOv8 Nano, sangat efisien untuk pengolahan *real-time*).
*   *Epoch / Batch*: 100 Epoch, Batch Size 16, resolusi gambar 640.
*   *Optimizer*: AdamW dengan *learning rate* awal 0.001 dan *patience* 20 untuk *early stopping*.
*   *Augmentasi*: Mosaic (1.0), Mixup (0.1), dan rotasi ringan (10 derajat) untuk simulasi sudut kamera CCTV.

**Gambar 4. 6 Perintah Eksekusi Pelatihan YOLOv8 Melalui Terminal**

```bash
python train.py --data dataset/data.yaml --epochs 100 --imgsz 640 --batch 16 --device 0
```


Perintah tersebut digunakan untuk melatih model dengan ukuran citra 640 piksel, jumlah *batch* sebanyak 16, serta 100 *epoch* dengan memanfaatkan bobot awal `yolov8n.pt`. File `data.yaml` berisi pengaturan lokasi dataset dan kategori objek yang digunakan pada penelitian ini.

Selama proses pelatihan, sistem memantau grafik penurunan fungsi kerugian (*Loss Curves*) yang terdiri dari:
- **Box Loss**: Mengukur presisi koordinat *bounding box* prediksi terhadap koordinat asli.
- **Class Loss (Cls Loss)**: Mengukur keakuratan prediksi label kelas.
- **DFL Loss (Distribution Focal Loss)**: Membantu presisi penentuan batas tepi objek.

Model terbaik yang menghasilkan nilai kerugian validasi terendah diekspor secara otomatis sebagai model final bernama `best.pt` ke dalam direktori `models/`.

**Gambar 4. 7 Cuplikan Log Output Proses Pelatihan YOLOv8**

```
Ultralytics YOLOv8.1.0 🚀 Python-3.10.11 torch-2.0.1+cu118 CUDA:0 (GeForce RTX 3060, 12288MB)
Model summary (fused): 168 layers, 3151904 parameters, 0 gradients, 8.7 GFLOPs

      Epoch    gpu_mem   box_loss   cls_loss   dfl_loss  Instances       Size
     1/100      2.85G      1.632      2.541      1.221         24        640: 100%|██████| 21/21 [00:14<00:00, 1.42it/s]
     2/100      2.91G      1.485      2.102      1.114         18        640: 100%|██████| 21/21 [00:09<00:00, 2.15it/s]
    ...
    100/100      2.91G      0.648      0.408      0.821         16        640: 100%|██████| 21/21 [00:09<00:00, 2.17it/s]

100 epochs completed in 0.284 hours.
Optimizer stripped from runs/detect/train/weights/best.pt, 6.5MB
```


**(5) Hasil Deteksi & Perhitungan Overlap**

Setelah proses pelatihan model YOLOv8 selesai, tahap berikutnya adalah mengujinya pada citra uji untuk menilai kemampuan sistem dalam mendeteksi objek. Model menggunakan berkas *weights* hasil pelatihan (`best.pt`) dan menghasilkan deteksi berupa *bounding box*, kelas objek, serta tingkat kepercayaan (*confidence score*). Deteksi dilakukan dengan ambang batas kepercayaan sebesar 0,25 dan ambang batas IoU sebesar 0,45. Ambang batas kepercayaan berfungsi untuk menyaring objek dengan keyakinan rendah, sedangkan ambang batas IoU digunakan untuk menggabungkan *bounding box* yang saling bertumpuk melalui proses *Non-Maximum Suppression* (NMS).

Model `best.pt` diintegrasikan dalam script `detector.py` menggunakan pendekatan **Dual-Model**. Deteksi perawat diambil dari model bawaan `yolov8n.pt` pada kelas 0 (`person`) dan dipetakan sebagai kelas `tenaga_kesehatan`, sedangkan deteksi baki diambil dari model kustom `best.pt` kelas 0 (`baki_medis`) dan dipetakan ke kelas ID 1. Hasil deteksi digabungkan menggunakan `supervision.Detections.merge()`.

**Gambar 4. 8 Visualisasi Hasil Deteksi Inferensi Model YOLOv8**

```
+---------------------------------------------+
|                                             |
|      [tenaga_kesehatan: 0.94]               |
|      +-----------------------+              |
|      |                       |              |
|      |   [baki_medis: 0.88]  |              |
|      |   +---------------+   |              |
|      |   |               |   |              |
|      |   +---------------+   |              |
|      |                       |              |
|      +-----------------------+              |
|                                             |
+---------------------------------------------+
```


Pada proses inferensi, model menghasilkan nilai koordinat pusat objek beserta lebar dan tinggi *bounding box* dalam format YOLO ($x, y, w, h$). Agar dapat dibandingkan dengan data *ground truth*, koordinat tersebut dikonversi ke dalam format batas area yaitu $x_{min}$, $y_{min}$, $x_{max}$, $y_{max}$ dengan rumus sebagai berikut:
$$x_{min} = x - \frac{w}{2}; \quad x_{max} = x + \frac{w}{2}$$
$$y_{min} = y - \frac{h}{2}; \quad y_{max} = y + \frac{h}{2}$$

**Logika Overlap Ratio**:

Dalam menentukan apakah perawat membawa baki medis, sistem secara langsung menggunakan logika **Overlap Ratio** yang dihitung terhadap luas area instrumen medis (`baki_medis`).

*   **Tujuan Penerapan**:
    Menilai dan memastikan secara spasial apakah suatu instrumen medis (`baki_medis`) sedang berada dalam genggaman atau dibawa oleh perawat (`tenaga_kesehatan`) berdasarkan tingkat tumpang tindih (*overlap*) *bounding box* kedua objek tersebut.
*   **Alasan Penggunaan**:
    1. **Perbedaan Ukuran Objek yang Sangat Signifikan**: Dimensi *bounding box* manusia (`tenaga_kesehatan`) secara fisik jauh lebih besar dibandingkan dengan *bounding box* baki medis (`baki_medis`).
    2. **Kondisi Saling Melingkupi (Enclosure)**: Ketika perawat sedang membawa baki medis, letak *bounding box* baki medis hampir selalu berada sepenuhnya atau sebagian besar di dalam area cakupan *bounding box* manusia. Dengan membagi luas irisan terhadap luas baki medis ($B$) itu sendiri (bukan terhadap gabungan area keduanya), nilai rasio yang dihasilkan tetap sensitif dan akurat menggambarkan kondisi objek yang sedang dibawa.
    3. **Konsistensi Jarak dan Skala**: Metode ini membuat keputusan pendeteksian tetap stabil terlepas dari dekat atau jauhnya perawat dari kamera, karena yang diukur adalah persentase ketercakupan baki medis di dalam area perawat, bukan kesamaan ukuran total dari kedua kotak tersebut.

Formula matematika Overlap Ratio yang digunakan adalah sebagai berikut:
$$\text{Overlap Ratio} = \frac{\text{Luas } (A \cap B)}{\text{Luas } B}$$

Keterangan:
- $A$ = Bounding box objek manusia (`tenaga_kesehatan`);
- $B$ = Bounding box objek instrumen (`baki_medis`);
- $A \cap B$ = Area irisan (tumpang tindih) antara kotak $A$ dan kotak $B$.

*Contoh Perhitungan Matematis*:
Misalkan Luas Bbox Orang ($A$) = $200 \times 400 = 80.000\text{ px}^2$, Luas Bbox Baki ($B$) = $150 \times 60 = 9.000\text{ px}^2$, dan baki berada 100% di dalam area tubuh perawat $\rightarrow$ Luas Irisan ($A \cap B$) = $9.000\text{ px}^2$.

$$\text{Overlap Ratio} = \frac{9.000}{9.000} = 1,0 \quad (100\%)$$

Karena nilai Overlap Ratio (1,0) melebihi batas keputusan (*threshold*) yang ditetapkan yaitu **0,50 (50%)**, sistem secara akurat menyimpulkan bahwa tenaga medis tersebut sedang membawa instrumen medis (baki medis) ✅.

Metode Overlap Ratio terbukti jauh lebih akurat untuk mendeteksi objek dengan perbedaan ukuran skala yang timpang. Hasil pengujian model YOLOv8 pada data uji disajikan pada Tabel 4.2.

**Tabel 4. 2 Contoh Hasil Perhitungan Overlap Ratio pada Citra Uji**

| Nama Gambar | Luas Orang ($A$) | Luas Baki ($B$) | Luas Irisan ($A \cap B$) | Overlap Ratio | Status Deteksi |
|---|---|---|---|---|---|
| `nurse_carry_01.jpg` | 82.400 | 8.200 | 8.200 | 1,00 | Membawa Alat (TP) |
| `nurse_carry_02.jpg` | 78.000 | 9.100 | 8.800 | 0,96 | Membawa Alat (TP) |
| `nurse_pass_03.jpg`  | 85.000 | 7.500 | 1.200 | 0,16 | Tidak Membawa (TN) |

Berdasarkan hasil pengujian pada citra uji, sebagian besar deteksi yang dilakukan oleh model YOLOv8 dengan metode Overlap Ratio menghasilkan status yang akurat. Hal ini menunjukkan bahwa model mampu mengenali posisi dan keberadaan instrumen medis di dekat tenaga kesehatan dengan cukup baik. Meskipun demikian, beberapa deteksi memiliki nilai Overlap yang rendah akibat oklusi parsial, sehingga deteksinya dikategorikan sebagai *False Positive* atau *False Negative*. Faktor-faktor yang memengaruhi ketidaktepatan deteksi antara lain kondisi pencahayaan, sudut pengambilan gambar, serta kedekatan antar tenaga medis. Secara keseluruhan, hasil pengujian menunjukkan bahwa model YOLOv8 berfungsi dengan baik dalam mendeteksi objek baki medis pada citra video CCTV rumah sakit.

**(6) Mekanisme Dwell Timer & State Cuci Tangan**

Selain mendeteksi instrumen medis menggunakan YOLOv8, sistem juga harus memverifikasi kepatuhan tindakan cuci tangan di area wastafel secara objektif. Mengingat posisi wastafel adalah objek statis, verifikasi dilakukan dengan mendeteksi keberadaan perawat (*bounding box person*) di dalam area poligon virtual (*virtual zone*) wastafel yang telah dikonfigurasi. Untuk memastikan perawat benar-benar mencuci tangan dan tidak sekadar melewati wastafel (*false positive*), sistem menerapkan mekanisme **Dwell Timer** (timer waktu menetap) yang memadukan analisis spasial geometris dan analisis temporal sebagai berikut:

**a. Analisis Spasial (Pengecekan Interseksi Poligon)**

Area cuci tangan didefinisikan sebagai poligon interaktif $Z_{\text{poly}}$ melalui antarmuka web, sedangkan objek perawat yang bergerak direpresentasikan oleh *bounding box* $P_{\text{box}}$ dengan koordinat $[x_1, y_1, x_2, y_2]$. Secara geometris, *bounding box* perawat diubah menjadi poligon persegi panjang:

$$P_{\text{box}} = \{ (x, y) \mid x_1 \le x \le x_2, \quad y_1 \le y \le y_2 \}$$

Menggunakan pustaka **Shapely** di Python, sistem melakukan operasi interseksi set spasial untuk mendeteksi apakah perawat menyentuh atau berada di dalam area wastafel:

$$\text{Intersects}(P_{\text{box}}, Z_{\text{poly}}) \iff P_{\text{box}} \cap Z_{\text{poly}} \neq \emptyset$$

Jika $\text{Intersects}(P_{\text{box}}, Z_{\text{poly}})$ bernilai **True**, sistem menyimpulkan perawat telah memasuki area fasilitas cuci tangan dan mengaktifkan deteksi dwell time.

**b. Analisis Temporal (Dwell Time & Transisi State)**

Setelah deteksi spasial bernilai True pada waktu masuk $t_{\text{entry}}$, sistem mencatat dan menghitung durasi menetap (*dwell time*) secara real-time pada frame-frame berikutnya dengan rumus:

$$\text{Dwell Time} = t_{\text{current}} - t_{\text{entry}}$$

Berdasarkan nilai durasi tersebut, transisi status visual perawat didefinisikan sebagai berikut:

*   **State Sedang Mencuci Tangan (`hand_wash_pending` / "Cuci Tangan...")**: 
    Kondisi ini tercapai ketika perawat terdeteksi berada di dalam poligon wastafel namun waktu menetap belum memenuhi batas minimal durasi cuci tangan:
    $$\text{Dwell Time} = t_{\text{current}} - t_{\text{entry}} < 2,0 \text{ detik}$$
    Pada video pemantauan, bounding box orang akan diberi warna **Kuning Redup** dengan label status "Cuci Tangan...".
*   **State Cuci Tangan Terkonfirmasi (`hand_wash_zone` / "Cuci Tangan")**: 
    Kondisi ini tercapai ketika perawat menetap di area wastafel secara konsisten hingga memenuhi batas minimal durasi cuci tangan yang disepakati:
    $$\text{Dwell Time} = t_{\text{current}} - t_{\text{entry}} \ge 2,0 \text{ detik}$$
    Status visual diubah menjadi "Cuci Tangan" (Kuning Terang) dan aktivitas cuci tangan dilaporkan secara formal ke engine kepatuhan (`GroupComplianceEngine`) dengan status `HAND_WASHED`.

**c. Grace Period (Toleransi Hilang Kontak)**

Untuk mengatasi fluktuasi pendeteksian akibat gerakan perawat yang tidak konsisten atau oklusi sesaat, sistem menyertakan *grace period* selama **5,0 detik** sebelum meriset timer. Jika perawat keluar dari area poligon kurang dari 5,0 detik dan kembali masuk, durasi cuci tangan tidak akan direset dari awal. Timer hanya akan dihapus jika perawat berada di luar zona secara terus-menerus melebihi batas toleransi tersebut:

$$t_{\text{current}} - t_{\text{last\_in\_zone}} > 5,0 \text{ detik}$$

Logika ini menjamin kestabilan deteksi meskipun terjadi interferensi visual/oklusi sesaat di sekitar wastafel.

**d. Hasil Deteksi Visual State Real-Time**

Untuk memperjelas transisi status visual dan pendeteksian di lapangan, visualisasi deteksi real-time pada tampilan aliran video (*live feed*) dari IP Camera/CCTV ditunjukkan pada Gambar 4.9.

**Gambar 4. 9 Visualisasi Deteksi Real-Time dan State Pemantauan**

![Visualisasi Deteksi Real-Time dan State Pemantauan](file:///e:/skripsi/sistem/assets/state_cuci_tangan.png)

Pada Gambar 4.9, dapat dilihat dua perawat yang terdeteksi secara bersamaan dengan state yang berbeda:
1. **Perawat #1 (Kanan)**: Berada di area wastafel (ditandai dengan poligon berwarna tosca/cyan) dan sedang mencuci tangan. Karena durasi menetapnya belum mencapai 2 detik, status visualnya adalah **"Cuci Tangan..."** (state `hand_wash_pending` dengan bounding box ungu).
2. **Perawat #2 (Kiri)**: Sedang berjalan membawa instrumen medis (baki medis ditandai dengan bounding box biru di tangannya). Karena overlap ratio antara baki medis dan tubuh perawat $\ge 0,50$, status perawat diubah menjadi **"Membawa Instrumen"** (state carrying dengan bounding box ungu). Selain itu, terdapat zona pintu yang ditandai dengan poligon berwarna merah sebagai pintu masuk ruangan pasien.

---

### 3. Perancangan Sistem

#### a. Hardware

Perangkat keras yang digunakan dalam sistem monitoring kepatuhan cuci tangan ini adalah sebagai berikut:

1) IP Camera / CCTV
   - (a) Resolusi: Minimal 720p (1280 × 720 piksel)
   - (b) Frame Rate: 15 FPS
   - (c) Koneksi: Mendukung protokol RTSP melalui kabel LAN UTP Kategori 6
   - (d) Pemasangan: Di langit-langit koridor bangsal setinggi 2,7 meter dengan *tilt angle* 35 derajat

2) PC Server
   - (a) Prosesor: Intel Core i7 11th Gen (8 Cores)
   - (b) RAM: 16 GB DDR4
   - (c) Penyimpanan: SSD 512 GB
   - (d) GPU: NVIDIA GeForce RTX 3060 (12 GB VRAM) untuk akselerasi CUDA pada inferensi YOLOv8
   - (e) Sistem Operasi: Windows 10/11 atau Ubuntu 22.04 LTS

#### b. Software

Perangkat lunak yang digunakan dalam sistem ini mendukung pengolahan data, pengelolaan antarmuka, serta pelatihan dan penerapan deteksi objek, dengan rincian sebagai berikut:

| No | Software | Versi / Deskripsi |
|---|---|---|
| 1 | Bahasa Pemrograman | Python 3.10+ (Layanan AI) dan PHP 8.2+ (Web Dashboard) |
| 2 | Framework AI | PyTorch (dengan dukungan CUDA 12.4), Ultralytics (YOLOv8), Supervision, Shapely |
| 3 | Framework Web | Laravel 11 |
| 4 | Database | PostgreSQL 15 |
| 5 | Web Server | Apache Web Server |
| 6 | Dependency Manager | Composer (PHP) dan pip (Python) |
| 7 | Asset Bundler | Node.js + Vite (untuk aset frontend Laravel) |
| 8 | Pelabelan Dataset | LabelImg / CVAT |
| 9 | Browser Utama | Google Chrome / Mozilla Firefox |

#### c. Desain Perancangan (BPMN)

Berdasarkan hasil pengamatan terhadap sistem pemantauan kebersihan tangan sebelumnya, proses pengawasan kepatuhan dilakukan dengan mendatangi lokasi secara langsung. Pendekatan ini memiliki keterbatasan karena memerlukan waktu dan tenaga, serta tidak dapat memberikan informasi kepatuhan secara berkala dan tepat waktu. Sebagai solusi, sistem ini dirancang untuk membantu pengguna dalam memantau aktivitas kebersihan tangan tenaga medis secara otomatis tanpa harus selalu berada di lokasi pemantauan.

Perbedaan proses sebelum dan sesudah adanya sistem ini digambarkan melalui pemodelan *Business Process Model and Notation* (BPMN).

**(1) BPMN Lama**

Pada proses bisnis lama (Gambar 4.10), pemantauan kepatuhan berjalan secara reaktif dan manual. Skema proses bisnis lama dimulai dari tenaga medis yang melakukan tindakan kebersihan tangan saat terdapat indikasi 5 momen *hand hygiene* (misalnya sebelum masuk ke ruangan pasien dengan membawa instrumen medis). Petugas PPI (IPCLN) harus hadir secara fisik di koridor bangsal untuk mengobservasi tindakan tersebut secara langsung.
- Jika tindakan cuci tangan tidak terpantau oleh petugas PPI (karena keterbatasan waktu atau kehadiran), maka proses berhenti dan data kepatuhan tidak tercatat.
- Jika kepatuhan terpantau, petugas PPI akan menilai kesesuaian tindakan tersebut dengan SOP 6 langkah kebersihan tangan, lalu mencatat data tersebut secara manual di kertas. Hasil observasi direkap secara berkala dan diinput manual ke sistem pelaporan daring. Proses ini memakan waktu dan rentan terhadap inkonsistensi penilaian.

**Gambar 4. 10 Diagram BPMN Proses Bisnis Lama**

```mermaid
flowchart TD
    Start([Mulai: Indikasi Kebersihan Tangan]) --> Medis[Tenaga Medis membawa instrumen ke kamar pasien]
    Medis --> Was[Tenaga Medis melakukan tindakan cuci tangan]
    Was --> Observasi{Apakah terpantau langsung oleh IPCLN?}
    
    Observasi -->|Tidak| Selesai([Selesai: Data tidak tercatat])
    Observasi -->|Ya| Nilai{Apakah sesuai SOP 6 Langkah?}
    
    Nilai -->|Ya| Catat[Catat 'Patuh' secara manual pada kertas]
    Nilai -->|Tidak| CatatTidak[Catat 'Tidak Patuh' secara manual pada kertas]
    
    Catat --> Input[Input rekapitulasi data ke sistem pelaporan daring]
    CatatTidak --> Input
    Input --> End([Selesai: Laporan tersimpan])
```


**(2) BPMN Baru**

Pada proses bisnis baru (Gambar 4.11), pemantauan berjalan secara otomatis. Skema proses bisnis baru mengeliminasi kebutuhan kehadiran fisik petugas PPI secara terus-menerus dengan memanfaatkan otomatisasi *Computer Vision* (Python FastAPI + YOLOv8 + ByteTrack + Shapely) yang terintegrasi dengan Dashboard Laravel 11.

Proses bisnis baru berjalan dengan alur sebagai berikut:
1. Kamera CCTV merekam secara pasif. Sistem FastAPI mendeteksi perawat dan baki medis secara dinamis serta melacaknya menggunakan ByteTrack.
2. Jika terdeteksi adanya objek instrumen medis yang memiliki Overlap Ratio $\ge 0,50$ dengan *bounding box* orang, status orang tersebut diperbarui menjadi membawa instrumen.
3. Jika perawat terdeteksi berada di dalam poligon zona wastafel, status visual orang tersebut diubah menjadi sedang mencuci tangan (*Cuci Tangan...*). Apabila perawat menetap di area tersebut hingga memenuhi *dwell time* $\ge 2$ detik, sistem akan mendaftarkan aktivitas mencuci tangan tersebut (*Cuci Tangan* terkonfirmasi).
4. Evaluasi kepatuhan dijalankan di tingkat grup menggunakan jendela waktu sesi 180 detik. Status kepatuhan final (Patuh/Tidak Patuh) beserta *snapshot* langsung disimpan ke database PostgreSQL dan ditampilkan di dashboard Laravel.

**Gambar 4. 11 Diagram BPMN Proses Bisnis Baru (Otomatis)**

```mermaid
flowchart TD
    Start([Mulai]) --> CCTV[IP Camera / CCTV merekam koridor & wastafel]
    CCTV --> Deteksi[FastAPI mendeteksi orang & baki medis secara real-time]
    
    Deteksi --> Overlap{"Overlap Ratio >= 0.50?"}
    Overlap -->|Ya| Carry["Status: Membawa Instrumen"]
    Overlap -->|Tidak| Monitor["Status: Monitoring Biasa"]
    
    Carry --> ZoneCheck{Berada di Zona Wastafel?}
    ZoneCheck -->|Ya| Dwell{"Dwell Time >= 2 detik?"}
    Dwell -->|Ya| Wash["Status: Cuci Tangan Terkonfirmasi"]
    Dwell -->|Tidak: Sedang Mencuci| ZoneCheck
    
    Carry --> WindowCheck{Apakah Window Sesi 180 detik berakhir?}
    WindowCheck -->|Ya: Tanpa Cuci Tangan| FinalTidakPatuh["Status Final: TIDAK PATUH"]
    
    Wash --> FinalPatuh["Status Final: PATUH"]
    
    FinalPatuh --> Save[Simpan Log ke PostgreSQL & Snapshot ke Storage]
    FinalTidakPatuh --> Save
    
    Save --> Laravel[Laravel Dashboard ter-update via WebSocket]
    Laravel --> End([Selesai])
```


---

### 4. Desain Produk (UML & Antarmuka)

#### a. Use Case Diagram

Diagram *use case* memvisualisasikan fungsionalitas sistem yang melibatkan aktor Admin (Petugas PPI) dan Sistem AI (FastAPI Service). Diagram *use case* ini ditunjukkan pada Gambar 4.12.

**Gambar 4. 12 Use Case Diagram Sistem**

```mermaid
flowchart TD
  subgraph System ["Sistem Monitoring Kepatuhan Cuci Tangan"]
    UC1([Login / Logout])
    UC2([Kelola Grup Monitoring])
    UC3([Tambah Kamera])
    UC4([Scan USB Kamera])
    UC5([Konfigurasi Zona])
    UC6([Mulai / Stop Monitoring])
    UC7([Lihat Dashboard Real-time])
    UC8([Lihat Log & Laporan])
    UC9([Filter Log & Ekspor])
    UC10([Deteksi Cuci Tangan YOLO & Tracking])
    UC11([Catat Kepatuhan])
    UC12([Simpan Snapshot])
  end

  Admin([Admin])
  AI([Sistem AI])

  Admin --> UC1
  Admin --> UC2
  Admin --> UC3
  Admin --> UC5
  Admin --> UC6
  Admin --> UC7
  Admin --> UC8

  UC3 -.->|includes| UC4
  UC8 -.->|includes| UC9
  UC10 -.->|includes| UC11
  UC11 -.->|extends| UC12
  UC3 -.->|extends| UC5

  AI --> UC6
  AI --> UC10
  AI --> UC11
  AI --> UC12
```


Admin berinteraksi dengan Laravel untuk mengelola kamera, mengonfigurasi poligon zona secara interaktif, mengontrol monitor start/stop, dan melihat dashboard laporan. Sistem AI secara otomatis menjalankan proses deteksi, pelacakan, evaluasi status kepatuhan, serta penyimpanan *snapshot* kejadian ke database.

#### b. Skema Interaksi (Sequence Diagram)

Interaksi antar komponen sistem dalam mengeksekusi fitur-fitur penting dimodelkan melalui diagram sekuensial.
*   **(1) Sequence Diagram Login / Logout**: Menggambarkan autentikasi Admin pada aplikasi Laravel, pengecekan kecocokan sandi pada database *users*, dan pembuatan sesi login.
*   **(2) Sequence Diagram Konfigurasi Zona Poligon**: Menggambarkan alur Admin menggambar poligon wastafel/sanitizer pada *canvas* web. Data koordinat dikirim dari *front-end* ke Laravel Controller, disimpan di database `zones`, lalu disinkronkan ke FastAPI AI Service agar poligon deteksi ter-update tanpa perlu merestart sistem.
*   **(3) Sequence Diagram Dashboard (Mulai Monitoring & Live Feed)**: Menggambarkan Admin menekan tombol "Connect". Laravel mengirim request POST ke FastAPI `/api/groups/{id}/start` untuk mengaktifkan thread pemrosesan kamera. FastAPI membuka koneksi WebSocket ke browser Admin untuk menyiarkan Base64 frame secara *real-time*.
*   **(4) Sequence Diagram Deteksi Otomatis (Deteksi → Catat → Snapshot)**: Menggambarkan alur utama saat thread kamera memproses gambar. Setelah status kepatuhan difinalisasi oleh `GroupComplianceEngine` (Patuh atau Tidak Patuh), FastAPI secara otomatis menulis log ke database dan menyimpan file *snapshot* .jpg ke direktori *storage* Laravel.

#### c. Class Diagram

Diagram kelas pada Gambar 4.13 menggambarkan struktur data basis data PostgreSQL yang diintegrasikan dengan Eloquent ORM Laravel.

**Gambar 4. 13 Class Diagram Arsitektur Data & Controller**

```mermaid
classDiagram
    direction TB
    class SistemMonitoring {
        +id: int
    }
    class Dashboard {
        +selectedGroup: int
        +tampilkan()
    }
    SistemMonitoring *-- Dashboard : Bergantung

    class LoginInt {
        +email: string
        +password: string
    }
    class KameraInt {
        +nama_kamera: string
        +source: string
        +polygon_points: json
    }
    class LogInt {
        +filter_tanggal: date
        +filter_kamera: int
    }

    Dashboard o-- LoginInt : memiliki
    Dashboard o-- KameraInt : memiliki
    Dashboard o-- LogInt : memiliki

    class LamanLogin {
        +button_login()
    }
    LoginInt o-- LamanLogin : memiliki

    class LamanKamera {
        +button_scan_usb()
        +button_simpan()
    }
    KameraInt o-- LamanKamera : memiliki

    class LamanLog {
        +button_filter()
    }
    LogInt o-- LamanLog : memiliki

    class ProsesAuth {
        +autentikasi()
        +keluar()
    }
    LamanLogin --> ProsesAuth : melakukan

    class ProsesKameraAI {
        +store()
        +detect()
        +simpanZona()
    }
    LamanKamera --> ProsesKameraAI : melakukan

    class ProsesLog {
        +get_data()
        +filter()
    }
    LamanLog --> ProsesLog : melakukan

    class TabelUsers {
        +username: varchar
        +password: varchar
    }
    ProsesAuth --> TabelUsers : melakukan

    class TabelCameras {
        +nama_kamera: varchar
        +tipe_zona: varchar
        +polygon_points: json
    }
    ProsesKameraAI --> TabelCameras : melakukan

    class TabelLogs {
        +status: varchar
        +waktu: datetime
        +snapshot_path: varchar
    }
    ProsesKameraAI --> TabelLogs : melakukan
    ProsesLog --> TabelLogs : melakukan
```


Tabel `monitoring_groups` memiliki relasi *one-to-many* ke tabel `cameras` dan `zones`. Setiap kamera dapat memiliki banyak koordinat zona (`zones`). Tabel `monitoring_logs` mencatat hasil kepatuhan dengan menyimpan kunci asing (*foreign key*) `camera_id` dan `group_id`, serta menyimpan data `person_id`, status (patuh/tidak_patuh), dan lokasi file *snapshot*.

#### d. Deployment Diagram

Arsitektur fisik penyebaran sistem digambarkan pada Gambar 4.14.

**Gambar 4. 14 Deployment Diagram Sistem**

```mermaid
flowchart TD
    classDef device fill:#fcfcfc,stroke:#333,stroke-width:1.5px;
    classDef comp fill:#fdebeb,stroke:#cc7a7a,stroke-width:1.5px,rx:5px;
    classDef nested fill:#fffaf9,stroke:#999,stroke-width:1px;

    subgraph ServerNode ["«device»<br><b>Server Utama (PC Server Fisik)</b>"]
        direction TB
        subgraph PC ["Runtime Environment"]
            direction LR
            Apache["«component»<br>Apache Web Server"]:::comp
            PHP["«component»<br>PHP 8.2"]:::comp
            Laravel["«component»<br>Laravel 11 App"]:::comp
            PostgreSQL["«component»<br>PostgreSQL 15"]:::comp
            Python["«component»<br>Python 3.10"]:::comp
            FastAPI["«component»<br>FastAPI Engine"]:::comp
            YOLO["«component»<br>YOLOv8 + ByteTrack"]:::comp
        end
    end
    class ServerNode,PC device
    class PC nested

    subgraph ClientNode ["«device»<br><b>User (Admin / Petugas PPI)</b>"]
        direction TB
        subgraph Perangkat ["PC / Client Device"]
            direction TB
            Browser["«component»<br>Web Browser"]:::comp
        end
    end
    class ClientNode,Perangkat device
    class Perangkat nested

    subgraph CameraNode ["«device»<br><b>IP Camera / CCTV</b>"]
        direction TB
        subgraph HW ["Hardware Layer"]
            direction TB
            CCTV["«component»<br>IP Camera (RTSP Stream)"]:::comp
        end
    end
    class CameraNode,HW device
    class HW nested

    ServerNode <-->|"HTTPS / WSS"| ClientNode
    CameraNode -->|"RTSP Stream"| ServerNode
```


IP Camera mengirim RTSP stream ke PC Server melalui *Local Area Network* (LAN). PC Server menjalankan dua layanan (Laravel Web App dan FastAPI Python AI Service) yang berbagi database PostgreSQL lokal. Admin mengakses aplikasi web melalui web browser di komputer klien menggunakan protokol HTTP/WebSocket.

#### e. Desain Antarmuka Aplikasi Web (Mockup)

Desain antarmuka dirancang secara minimalis dan modern menggunakan *layout* sidebar navigasi:
*   **Halaman Login**: Formulir sederhana berisi kolom email, password, dan tombol login.
*   **Halaman Dashboard**: Menampilkan grafik statistik kepatuhan hari ini (jumlah patuh, tidak patuh, dan persentasenya), log terbaru, dan tombol "▶ Connect" atau "⏹ Stop" untuk setiap slot kamera.
*   **Halaman Konfigurasi Zona**: Menampilkan *canvas* video di mana Admin dapat menggambar poligon zona wastafel atau sanitizer secara interaktif dengan mengklik titik-titik koordinat.
*   **Halaman Riwayat Log**: Menampilkan tabel seluruh log kepatuhan yang tersimpan, dilengkapi dengan kolom status berwarna (hijau untuk patuh, merah untuk tidak patuh), data deteksi sensor, dan tombol lihat *snapshot* visual.

---

## C. Pembahasan (Implementasi & Pengujian)

### 1. Pengkodean Alat (Python AI Service)

Layanan AI Python dikembangkan menggunakan FastAPI. Integrasi dual-model untuk deteksi perawat dan baki medis secara simultan diimplementasikan pada script `detector.py` dengan potongan kode berikut:

```python
# Deteksi orang (class 0) dari model standar COCO
res_person = self.model_person(frame, conf=self.conf, classes=[0], verbose=False)[0]
det_person = sv.Detections.from_ultralytics(res_person)

# Deteksi baki medis dari model training kustom
if self.model_custom:
    res_custom = self.model_custom(frame, conf=self.conf, verbose=False)[0]
    det_custom = sv.Detections.from_ultralytics(res_custom)
    
    # Map class ID baki (0) ke ID 1 agar tidak bentrok dengan person (0)
    if len(det_custom) > 0:
        det_custom.class_id = np.full_like(det_custom.class_id, 1)
    
    # Gabungkan hasil deteksi
    detections = sv.Detections.merge([det_person, det_custom])
```

Logika evaluasi kepatuhan temporal per orang di tingkat grup diimplementasikan pada berkas `group_compliance.py`. Engine melacak `person_id` menggunakan jendela sesi 180 detik. Jika cuci tangan (`wash_time`) dan membawa instrumen (`carrying_time`) terdeteksi dalam jendela waktu yang sama, status diubah menjadi **PATUH**. Jika jendela waktu berakhir tanpa aktivitas cuci tangan, status diubah menjadi **TIDAK PATUH**:

```python
def report_hand_wash(self, camera_id: int, person_id: str, frame=None):
    with self.lock:
        ps = self._get_person(person_id)
        now = time.time()
        ps.wash_time = now

        if ps.finalized:
            return

        if ps.instrumen_terdeteksi:
            if (now - ps.carrying_time) > self.window_seconds:
                # Durasi membawa instrumen kadaluarsa sebelum cuci tangan
                self._finalize_status("tidak_patuh", frame, camera_id, person_id, ps)
                return

        # Sukses melakukan cuci tangan dalam window membawa alat
        self._finalize_status("patuh", frame, camera_id, person_id, ps)
```

Selain mendeteksi kepatuhan jangka panjang di tingkat grup, penentuan state transisi real-time seperti sedang cuci tangan (`hand_wash_pending`) dan cuci tangan terkonfirmasi (`hand_wash_zone`) dikelola di level pemrosesan kamera menggunakan mekanisme *dwell timer* pada file `camera_manager.py`. Berikut adalah potongan kode implementasi *dwell timer* tersebut:

```python
# Zona wastafel: bbox menyentuh zona → mulai dwell timer
if in_handwash:
    if tid not in self.handwash_dwell_timers:
        self.handwash_dwell_timers[tid] = time.time()

    dwell = time.time() - self.handwash_dwell_timers[tid]
    if dwell >= 2.0:
        # Kirim event ke compliance engine global setelah 2 detik
        self.group_engine.report_hand_wash(self.camera_id, str(tid), frame)
        state = "hand_wash_zone"     # Terkonfirmasi (≥2 detik)
    else:
        state = "hand_wash_pending"  # Sedang mencuci tangan, menunggu 2 detik
else:
    # Keluar zona: timer direset jika berada di luar zona lebih dari 5 detik
    if tid in self.handwash_dwell_timers:
        gap = time.time() - self.handwash_dwell_timers[tid]
        if gap > 5.0:
            del self.handwash_dwell_timers[tid]
```


---

### 2. Pengkodean Aplikasi Web (Laravel)

Aplikasi Laravel bertugas mengontrol jalannya monitoring AI dengan mengirimkan perintah HTTP POST ke Python AI Service. Pada berkas `CameraController.php`, proses aktivasi kamera dilakukan melalui potongan fungsi berikut:

```php
public function start(Camera $camera)
{
    try {
        $response = Http::timeout(10)
            ->post(config('services.handhygiene-cnn.url') . "/api/cameras/{$camera->id}/start");

        if ($response->successful()) {
            $camera->update(['aktif' => true]);
            return response()->json(['success' => true, 'message' => 'Kamera dimulai']);
        }
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'AI Service tidak tersedia'], 503);
    }
    return response()->json(['success' => false, 'message' => 'Gagal memulai kamera'], 500);
}
```

---

### 3. Prototipe Alat (Kamera)

Prototipe fisik sistem dirancang dengan memasang kamera pengawas di langit-langit koridor bangsal setinggi 2,7 meter. Kamera diarahkan dengan sudut kemiringan (*tilt angle*) 35 derajat ke bawah untuk meminimalisasi terjadinya oklusi ketika terdapat beberapa tenaga medis yang berdiri berdekatan di depan area wastafel. Kamera dihubungkan ke PC Server menggunakan kabel LAN UTP kategori 6 untuk menjamin kestabilan transmisi aliran data video RTSP tanpa delay.

### 4. Prototipe Aplikasi Web (Tampilan Riil)

Implementasi antarmuka web dashboard yang telah berhasil dideploy menyajikan visualisasi data yang dinamis. 
*   **Halaman Live Feed Dashboard**: Admin dapat melihat video pemantauan secara langsung. Bounding box pada perawat secara dinamis berubah warna sesuai kondisinya: **Abu-abu** untuk status *Monitoring* (default), **Oranye** untuk *Membawa Alat* (Overlap baki $\ge 0,50$), **Kuning Redup** untuk *Cuci Tangan...* (dwell time < 2 detik), **Kuning Terang** untuk *Cuci Tangan* (terkonfirmasi $\ge 2$ detik), **Hijau-Tosca** untuk *Sudah Cuci Tangan ✓* (sedang berjalan ke ruangan), **Hijau Terang** untuk status final **PATUH**, dan **Merah** untuk status final **TIDAK PATUH**.
*   **Halaman Laporan & Snapshot**: Tabel menampilkan daftar log lengkap beserta waktu kejadian. Admin dapat mengklik ikon mata pada log untuk menampilkan pop-up gambar *snapshot* asli sebagai bukti fisik pelanggaran atau kepatuhan yang telah dilakukan oleh tenaga medis terkait.
