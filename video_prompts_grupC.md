# 🎬 GRUP C — Final Version: Multi-Kamera Baki Medis
> **Fix:** (1) Kedua suster bawa baki medis · (2) Timeline fisika realistis · (3) Tidak ada frame kosong  
> **Solusi kosong:** Ditambah **Nakes C** (teal) dan **Nakes D** (putih) sebagai karakter latar aktif

---

## 👔 Karakter — Identik di SEMUA 9 Prompt

| ID | Karakter | Penampilan Lengkap | Properti |
|----|----------|--------------------|---------|
| **A** | Suster A (utama) | Indonesian female nurse, mid 20s, **salmon pink scrubs**, long black hair in **high ponytail**, **light blue surgical mask**, white rubber clogs | Silver stainless steel **medical instrument tray (baki medis)** held with both hands, wrapped instruments on tray |
| **B** | Suster B (utama) | Indonesian female nurse, early 30s, **lavender purple scrubs**, **short black bob hair**, **dark navy blue surgical mask**, white sneakers | Silver stainless steel **medical instrument tray (baki medis)** held with both hands, wrapped instruments on tray |
| **C** | Nakes C (latar) | Indonesian female nurse, **teal green scrubs**, hair in low bun, **white surgical mask**, dark rubber clogs | Tidak bawa baki |
| **D** | Nakes D (latar) | Indonesian female nurse, **white nurse uniform**, hair in tight bun, **pink surgical mask**, white sneakers | Tidak bawa baki |

---

## 🗺️ Layout Fisik & Timeline

```
[KAMERA C1]
[pojok atas lorong ─────────────────────────────────────────►]
  far end                    mid (sink)            near (door)
     │                          │                      │
     │◄── A+B masuk T=0         │ A pisah T=12          │ A masuk T=22
     │◄── C berjalan balik      │ C sudah di sini       │ B tiba T=25
                                │
                          [KAMERA C2]          [KAMERA C3]
                         (fokus wastafel)      (fokus pintu)

Timeline:
T=0–12s  : A+B jalan bersama di lorong panjang (CAM-C1 aktif penuh)
T=12s    : A belok ke wastafel (baki diletakkan di rak samping wastafel)
T=12–22s : A cuci tangan (CAM-C2 aktif). B terus jalan (CAM-C1 clip2)
T=22s    : A selesai, ambil baki, jalan ke pintu
T=22–25s : A tiba di pintu, masuk langsung (CAM-C3 clip2)
T=25–30s : B tiba di pintu, pakai sanitizer (siku), masuk (CAM-C3 clip3)

Pengisi kosong:
- CAM-C1: Nakes C berjalan berlawanan arah (mengisi clip 2 & 3)
- CAM-C2: Nakes C sudah di wastafel di clip 1 sebelum A tiba
- CAM-C3: Nakes D pakai sanitizer di clip 1 sebelum A & B tiba
```

---
---

# 📷 CAM-C1 — Lorong Utama (Full 30 Detik Aktif)
**Kamera:** Pojok atas lorong, 35° ke bawah, melihat ke panjang lorong  
**Statis:** Lorong putih panjang, lampu fluorescent, dispenser sanitizer di dinding kiri mid-frame, wastafel terlihat di sisi kanan ujung lorong, pintu di ujung paling jauh

---

### 🟤 PROMPT C1-CLIP1 — *T=0–10s: A dan B masuk lorong bersama, C berpapasan*

```
Realistic CCTV surveillance footage. Camera mounted at upper 
corner of a long hospital corridor, looking down at 35 degrees 
from the near end toward the far end. Long perspective view — 
white walls, grey linoleum floor, bright fluorescent ceiling 
strip lights. White wall-mounted hand sanitizer dispenser 
visible on left wall at mid-corridor. A sink station visible 
on the right side near the far end. A door at the very far end.

An Indonesian female nurse in salmon pink scrubs, long black 
hair in high ponytail, light blue surgical mask, white rubber 
clogs (Suster A) carries a silver stainless steel medical 
instrument tray with both hands, walking side by side with 
an Indonesian female nurse in lavender purple scrubs, short 
black bob hair, dark navy blue surgical mask, white sneakers 
(Suster B) also carrying a silver medical instrument tray 
with both hands. Both enter frame from the NEAR end (close 
to camera) and walk together toward the far end of the corridor.

Coming toward them from the far end, an Indonesian female 
nurse in teal green scrubs, hair in low bun, white surgical 
mask, dark rubber clogs (Nakes C) walks toward the camera. 
They pass each other mid-corridor. Nakes C continues walking 
toward the near end past the camera.

Fixed static CCTV camera. 10 seconds.
```

---

### 🟤 PROMPT C1-CLIP2 — *T=10–20s: A belok ke wastafel, B lanjut, C kembali jalan*

```
Realistic CCTV surveillance footage. Identical camera at upper 
corner of the same long hospital corridor, 35 degrees looking 
toward the far end. Same white walls, grey linoleum floor, 
fluorescent ceiling lights, white sanitizer dispenser on left 
mid-wall, sink station on right near far end, door at far end. 
Camera position unchanged.

An Indonesian female nurse in salmon pink scrubs, high black 
ponytail, light blue mask, white clogs (Suster A) carrying 
a silver medical instrument tray reaches the sink area on the 
right side near the far end. She slows and turns right toward 
the sink, exiting the main corridor view.

An Indonesian female nurse in lavender purple scrubs, short 
black bob, dark navy mask, white sneakers (Suster B) continues 
walking alone toward the far door, still carrying her silver 
medical instrument tray with both hands.

An Indonesian female nurse in teal green scrubs, low bun, 
white mask, dark clogs (Nakes C) re-enters from the near end 
and walks toward the far end again, passing the wall sanitizer 
dispenser on the left wall and briefly using it with her elbow 
as she walks.

Fixed static CCTV camera. 10 seconds.
```

---

### 🟤 PROMPT C1-CLIP3 — *T=20–30s: B mendekati pintu, C dan D di lorong*

```
Realistic CCTV surveillance footage. Identical camera at upper 
corner of same hospital corridor, 35 degrees toward far end. 
Same long white corridor, grey floor, fluorescent lights, 
sanitizer dispenser on left wall, sink area right side near 
far end (now empty), door at far end. Camera position unchanged.

An Indonesian female nurse in lavender purple scrubs, short 
black bob hair, dark navy blue surgical mask, white sneakers 
(Suster B) walks alone near the far end of the corridor, 
approaching the door, still carrying her silver medical 
instrument tray carefully with both hands.

An Indonesian female nurse in teal green scrubs, low bun, 
white mask, dark clogs (Nakes C) is also visible mid-corridor 
walking toward the far end.

An Indonesian female nurse in white nurse uniform, tight bun 
hair, pink surgical mask, white sneakers (Nakes D) exits 
through the far end door, walks toward camera down the corridor, 
passing Suster B who is about to reach the door.

Fixed static CCTV camera. 10 seconds.
```

---
---

# 📷 CAM-C2 — Area Wastafel (Full 30 Detik Aktif)
**Kamera:** Frontal 25° dari atas, melihat ke wastafel  
**Statis:** Wastafel stainless + keran lever + sabun di kiri + RAK KECIL di samping kanan wastafel (untuk meletakkan baki) + cermin kecil di atas + koridor sedikit terlihat di background

---

### 🔵 PROMPT C2-CLIP1 — *T=0–10s: Nakes C di wastafel, A+B terlihat di background*

```
Realistic CCTV surveillance footage. Static frontal camera 
slightly elevated at 25 degrees, looking at a hospital 
handwashing station built into a corridor alcove. Stainless 
steel wall-mounted sink with lever-handle faucet, white tiled 
wall, small mirror above the sink. White liquid soap dispenser 
(push pump) mounted on the wall to the LEFT of the sink. 
A small stainless steel shelf or side table is fixed to the 
RIGHT of the sink, clearly visible — for placing items. 
The main corridor is partially visible blurred in the background 
behind the sink alcove. Bright fluorescent lighting.

An Indonesian female nurse in teal green scrubs, hair in low 
bun, white surgical mask, dark rubber clogs (Nakes C) is at 
the sink, washing her hands with soap — white foam visible, 
hands scrubbing under running water.

In the background corridor (blurred), two nurses are visible 
walking together: one in salmon pink scrubs carrying a silver 
tray, and one in lavender purple scrubs also carrying a silver 
tray — approaching from the far background.

Fixed static camera. 10 seconds.
```

---

### 🔵 PROMPT C2-CLIP2 — *T=10–20s: A letakkan baki, cuci tangan*

```
Realistic CCTV surveillance footage. Identical static frontal 
camera at 25 degrees, same hospital handwashing station — 
stainless steel sink with lever faucet, white tile, soap 
dispenser on left, small stainless shelf/table on the right 
of the sink, mirror above. Same fluorescent lighting. 
Camera position unchanged.

Nakes C (teal scrubs, white mask) has just finished washing 
and is drying her hands with a paper towel, then exits left.

An Indonesian female nurse in salmon pink scrubs, long black 
hair in high ponytail, light blue surgical mask, white rubber 
clogs (Suster A) arrives carrying a silver stainless steel 
medical instrument tray. She carefully sets the tray down 
on the stainless shelf to the RIGHT of the sink, keeping 
the instruments stable. She then faces the sink, uses her 
forearm to push the soap dispenser, turns on the lever 
faucet with her elbow, and begins washing both hands 
with visible soap lather.

Fixed static camera. 10 seconds.
```

---

### 🔵 PROMPT C2-CLIP3 — *T=20–30s: A selesai, ambil baki, pergi*

```
Realistic CCTV surveillance footage. Identical static frontal 
camera at 25 degrees, same handwashing station — stainless 
sink, lever faucet, soap dispenser on left, stainless shelf 
on right with the silver medical instrument tray still resting 
on it, mirror above. Same lighting. Camera position unchanged.

An Indonesian female nurse in salmon pink scrubs, long black 
hair in high ponytail, light blue surgical mask, white rubber 
clogs (Suster A) is finishing rinsing her hands under running 
water. She turns off the lever faucet with her elbow, reaches 
to the right for a paper towel, and dries both hands thoroughly. 
She then carefully picks up her silver medical instrument tray 
from the shelf with both hands and turns to her left, walking 
away from the sink toward the corridor direction leading to the door.

The sink area becomes empty as she exits. The soap dispenser 
and shelf are visible and recently used.

Fixed static camera. 10 seconds.
```

---
---

# 📷 CAM-C3 — Pintu Bangsal (Full 30 Detik Aktif)
**Kamera:** Eye-level 20° dari atas, horizontal menghadap pintu  
**Statis:** Pintu kayu putih + jendela kecil + tulisan bangsal, dispenser sanitizer putih di dinding KANAN pintu, sedikit koridor terlihat di kiri frame

---

### 🟡 PROMPT C3-CLIP1 — *T=0–10s: Nakes D pakai sanitizer dan masuk, pintu aktif*

```
Realistic CCTV surveillance footage. Static camera at corridor 
eye-level, slightly elevated at 20 degrees, facing a hospital 
ward entrance door. White painted wooden door with small 
rectangular glass panel, ward name plate on wall. White walls. 
White wall-mounted push-pump hand sanitizer dispenser fixed 
clearly on the RIGHT side of the door frame, prominent in 
frame. Grey linoleum floor. Hospital corridor partially 
visible to the left. Bright fluorescent lighting.

An Indonesian female nurse in white nurse uniform, hair in 
tight bun, pink surgical mask, white sneakers (Nakes D) 
approaches the door from the corridor on the left side 
of frame. She stops at the white sanitizer dispenser on 
the right, presses the pump with her right hand, rubs 
both hands together for 3 seconds, then pushes the door 
open with her shoulder and enters through the doorway. 
The door swings closed behind her.

The door area then becomes briefly empty. 
Fixed static camera. 10 seconds.
```

---

### 🟡 PROMPT C3-CLIP2 — *T=10–20s: Suster A tiba dan langsung masuk (sudah cuci tangan)*

```
Realistic CCTV surveillance footage. Identical static camera 
at corridor eye-level at 20 degrees, same hospital ward 
entrance — white door with glass panel, ward name plate, 
white walls, white hand sanitizer dispenser on the RIGHT 
of the door frame clearly visible, grey linoleum floor, 
corridor visible to the left. Same fluorescent lighting. 
Camera position unchanged.

An Indonesian female nurse in salmon pink scrubs, long black 
hair in high ponytail, light blue surgical mask, white rubber 
clogs (Suster A) enters frame from the left side of corridor, 
carrying a silver stainless steel medical instrument tray 
with both hands. She walks directly to the door without 
stopping at the sanitizer dispenser — she already washed 
her hands thoroughly at the sink moments earlier. 
She pushes the door open with her elbow and enters 
through the doorway, carefully balancing the tray. 
The door swings shut behind her.

Fixed static camera. 10 seconds.
```

---

### 🟡 PROMPT C3-CLIP3 — *T=20–30s: Suster B tiba, pakai sanitizer siku, lalu masuk*

```
Realistic CCTV surveillance footage. Identical static camera 
at corridor eye-level at 20 degrees, same hospital ward 
entrance door — white door with glass panel, ward name plate, 
white walls, white hand sanitizer dispenser clearly on the 
RIGHT of the door frame, grey linoleum floor, corridor to 
left. Same fluorescent lighting. Camera position unchanged.

An Indonesian female nurse in lavender purple scrubs, short 
black bob hair, dark navy blue surgical mask, white sneakers 
(Suster B) enters frame from the left, carrying a silver 
stainless steel medical instrument tray with both hands. 
She approaches the door and stops at the white sanitizer 
dispenser. Because both hands are holding the tray, she 
presses the dispenser pump lever using her RIGHT ELBOW, 
receiving gel on the back of her wrist. She rubs the gel 
between her fingers and wrists carefully for 3 seconds 
while still holding the tray. She then turns and pushes 
the door open with her shoulder, entering the ward 
while carefully balancing the tray.

Sanitizer dispenser clearly used with elbow. 
Fixed static camera. 10 seconds.
```

---
---

## 📊 Ringkasan 9 Prompt — Aktivitas Per Frame

| Clip | CAM-C1 (Lorong) | CAM-C2 (Wastafel) | CAM-C3 (Pintu) |
|------|-----------------|-------------------|-----------------|
| **1** (0–10s) | **A+B** jalan bersama bawa baki · **C** berpapasan | **C** cuci tangan · **A+B** terlihat blur di background | **D** pakai sanitizer · masuk pintu |
| **2** (10–20s) | **A** belok ke wastafel · **B** lanjut · **C** kembali | **C** selesai · **A** letakkan baki → cuci tangan | **A** tiba → langsung masuk (bawa baki) |
| **3** (20–30s) | **B** mendekati pintu · **C** di mid-lorong · **D** keluar | **A** selesai → ambil baki → pergi ke pintu | **B** tiba → sanitizer pakai siku → masuk (bawa baki) |

### ✅ Cek Konsistensi Fisika

| Cek | Status |
|-----|--------|
| Setiap kamera aktif penuh 30 detik | ✅ Tidak ada frame kosong |
| Baki medis dibawa A dan B sepanjang waktu | ✅ Disebutkan di setiap prompt |
| A cuci tangan = letakkan baki dulu | ✅ Rak samping wastafel digunakan |
| B pakai sanitizer = dengan siku (tangan penuh baki) | ✅ Teknik realistis nakes |
| A masuk pintu sebelum B | ✅ A sudah selesai di wastafel, jalan ke pintu lebih dulu |
| C dan D mengisi waktu kosong di setiap kamera | ✅ Karakter latar aktif di semua clip |

---

## 🎯 Cara Generate

```
Kling AI → Text to Video → paste satu prompt → Duration: 10s → Standard
Total: 9 kali generate
Nama file: c1_clip1.mp4, c1_clip2.mp4, ... c3_clip3.mp4
Gabung: c1_clip1+c1_clip2+c1_clip3 = cam_c1_30det.mp4 (dst)
```

> [!IMPORTANT]
> Frasa baki medis yang harus identik di setiap prompt untuk A:  
> `"carrying a silver stainless steel medical instrument tray with both hands"`  
> Untuk B sama persis. Jangan diganti atau disingkat agar AI render konsisten.

> [!TIP]
> Generate semua 9 prompt dalam satu sesi hari yang sama di Kling AI  
> agar style, pencahayaan, dan warna karakter lebih konsisten antar clip
