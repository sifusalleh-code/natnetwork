# SPESIFIKASI SISTEM AFFILIATE — NATNETWORK

Dokumen ini mengandungi semua keputusan yang telah disahkan dalam perbincangan. Gunakan sebagai rujukan tunggal semasa pembangunan.

Status: **Spesifikasi disahkan. Langkah 2–3 (asas data, penjejakan, pendaftaran, profil, sekatan route) telah dilaksanakan — 24 Sep 2026.**

---

## 0. PERATURAN KERJA (WAJIB DIPATUHI)

- Audit dan fahami struktur projek `E:\NatNetwork` sebelum sebarang perubahan kod.
- Laksanakan **hanya** apa yang dinyatakan dalam dokumen ini.
- Tiada andaian, tiada feature tambahan, tiada redesign, tiada refactor tanpa arahan.
- Perubahan minimum sahaja. Semua fungsi sedia ada mesti kekal berfungsi.
- Perubahan database, schema, API, authentication atau authorization perlu dibentangkan dan disahkan dahulu.
- Bug di luar skop tidak dibetulkan. Catat sahaja: *"Isu dikesan tetapi berada di luar skop. Tiada perubahan dibuat."*
- Jika arahan tidak jelas atau bercanggah dengan sistem sedia ada, berhenti dan minta pengesahan.

---

## Modul

Engine baharu `Affiliate` (`app/Engines/Affiliate`).

---

## 1. KIRAAN KOMISYEN

### 1.1 Asas kiraan
- Berdasarkan **jualan kasar** (jumlah penuh jualan).

### 1.2 Kadar bertingkat (incremental)
Setiap bahagian jumlah dikira dengan kadar masing-masing, kemudian dicampur.

| Bahagian jumlah | Kadar |
|---|---|
| RM0 – RM500 | 10% |
| RM500.01 – RM2,000 | 8% |
| RM2,000.01 – RM5,000 | 6% |
| RM5,000.01 – RM10,000 | 4% |
| Melebihi RM10,000 | 3% |

Julat dan kadar ini adalah nilai awal. Admin boleh mengubahnya (lihat 6.6).

### 1.3 Jumlah terkumpul per portal pelanggan
- Setiap pelanggan hanya ada **satu portal**.
- Semua pembelian dalam portal pelanggan (sekali atau berasingan) **dijumlahkan**, dan kadar bertingkat dikira atas **jumlah terkumpul**.
- Jumlah terkumpul kekal **sepanjang hayat portal, tanpa reset**.
- Sebab: pelanggan mungkin menambah skop kerja yang meningkatkan nilai pembelian.
- Komisyen bagi setiap pembelian = komisyen atas jumlah terkumpul selepas pembelian − komisyen atas jumlah terkumpul sebelum pembelian.

**Contoh:**

| Pembelian | Jumlah terkumpul | Komisyen pembelian ini |
|---|---|---|
| RM300 | RM300 | RM30 |
| RM1,700 | RM2,000 | RM140 |
| RM3,000 | RM5,000 | RM180 |
| **Jumlah** | | **RM350** |

**Jadual rujukan (jumlah terkumpul):**

| Jumlah terkumpul | Jumlah komisyen | Kadar efektif |
|---|---|---|
| RM500 | RM50 | 10.00% |
| RM2,000 | RM170 | 8.50% |
| RM5,000 | RM350 | 7.00% |
| RM10,000 | RM550 | 5.50% |
| RM20,000 | RM850 | 4.25% |

### 1.3a Keputusan pelaksanaan (24 Sep 2026)
- **Pembelian = setiap invois production** (deposit, bayaran akhir, caj tambahan) bagi pelanggan yang dikaitkan dengan affiliate.
- Komisyen **Pending** dicipta semasa invois dikeluarkan. Bayaran invois sahaja **tidak** melepaskan komisyen (lihat 1.5).
- Jumlah terkumpul = semua pembelian **Pending + Released** (Cancelled tidak dikira).
- Admin boleh **tambah/buang tingkat** kadar. Setiap perubahan disimpan sebagai versi baharu; komisyen lama merujuk versi asalnya.

### 1.4 Peraturan gabung pesanan 24 jam
- **Tidak digunakan.** Digantikan oleh kiraan jumlah terkumpul per portal (1.3).

### 1.5 Status dan pelepasan komisyen
- Komisyen dikira ketika pembelian → status **Pending**.
- **(Dikemas kini 24 Sep 2026)** Komisyen deposit, baki dan invois lain kekal **Pending** walaupun invois itu telah dibayar.
- Komisyen menjadi **Released** hanya apabila **admin mengesahkan bayaran keseluruhan projek selesai** (Admin → Sales → Quotation → "Sahkan bayaran projek selesai").
- Projek = satu quotation yang diterima; semua invois bersumberkan quotation itu. Butang pengesahan hanya aktif bila semua invois projek PAID (disahkan melalui callback Billplz di server) dan jumlah dibayar = nilai quotation. Disemak semula di server, direkod dalam audit, tidak boleh dibatalkan.
- Sementara modul Projek (pencetus 80%) belum wujud, admin mengeluarkan invois baki melalui butang "Keluarkan invois baki" (sekali sahaja).
- Tiada tempoh tahanan tetap.
- Bayaran **sandbox** (TEST-*) tidak mencipta atau melepaskan komisyen. Hanya bayaran production yang disahkan dikira.

### 1.6 Refund selepas komisyen dilepaskan
- Dicatat dalam rekod affiliate.
- Jumlah terkumpul pelanggan dikurangkan.
- Komisyen berkaitan **ditolak daripada perolehan komisyen seterusnya**.

### 1.7 Perubahan kadar oleh admin
- Komisyen yang dikira **sebelum** perubahan **kekal**.
- Kadar baru hanya terpakai untuk komisyen **akan datang**.
- Jumlah terkumpul pelanggan tetap bersambung (tidak reset). Contoh: pelanggan ada RM3,000 terkumpul; pembelian tambahan RM2,000 selepas perubahan → bahagian RM3,000.01 – RM5,000 dikira ikut jadual kadar baru.
- Sistem **wajib memaparkan amaran kepada admin** sebelum perubahan disimpan.

---

## 2. ATRIBUSI (PENJEJAKAN AFFILIATE)

- Format link affiliate: `?ref={username}` (boleh pada mana-mana halaman awam).
- **Cookie 30 hari** (tempoh boleh diubah admin).
- Pelawat klik link affiliate → cookie disimpan.
- "Buka portal" = akaun pelanggan **baru pertama kali dicipta dan emelnya disahkan**. Dalam tempoh cookie → pelanggan **dikaitkan kekal** dengan affiliate tersebut.
- Pelanggan sedia ada **tidak** dikaitkan. Pengguna portal bukan affiliate (Client, Investor) yang belum jadi affiliate ditawarkan **card affiliate** di **semua dashboard portal bukan affiliate**. Komponen `<x-affiliate-offer-card :email="..."/>` sudah tersedia; dipasang semasa dashboard Client/Investor dibina (belum wujud). Card disembunyikan jika emel sudah berdaftar sebagai affiliate.
- Jika pelawat klik link beberapa affiliate → **affiliate terakhir diklik** mendapat pelanggan. Setiap klik baru mengemas kini cookie.
- Selepas dikaitkan, semua pembelian pelanggan itu dikira untuk affiliate tersebut sepanjang hayat portal.
- Kaedah affiliate terakhir diklik dinyatakan dalam Terma & Syarat.

---

## 3. PENDAFTARAN DAN AKSES AFFILIATE

### 3.1 Aliran
1. Daftar akaun (pendaftaran **terbuka**, affiliate daftar sendiri) — medan: **nama, emel, no. telefon**. Semakan kewujudan akaun berdasarkan emel: jika emel sudah berdaftar sebagai affiliate → terus ke halaman login.
2. Sahkan emel melalui **OTP emel** (tiada kata laluan). Login affiliate juga guna OTP emel. Akaun affiliate berasingan daripada akaun pelanggan (jadual `affiliates`, guard `affiliate`); satu emel boleh jadi pelanggan dan affiliate.
3. Lengkapkan profil (**wajib**), termasuk maklumat akaun bank **atas nama pemilik akaun affiliate**
4. Akses dashboard dan semua submenu dibuka

Tiada kelulusan admin diperlukan untuk pendaftaran.

### 3.2 Sekatan profil belum lengkap
- Dashboard adalah pintu masuk. Tanpa akses dashboard, tiada akses submenu.
- Sekatan dikuatkuasakan pada **setiap route**, bukan hanya menu.
- Jika affiliate cuba akses mana-mana halaman terus melalui URL dengan profil belum lengkap → **redirect ke halaman profil**.

| Akses | Profil belum lengkap | Profil lengkap |
|---|---|---|
| `/affiliate/dashboard` | Redirect ke profil | Dibenarkan |
| `/affiliate/studio-poster` | Redirect ke profil | Dibenarkan |
| `/affiliate/wallet` | Redirect ke profil | Dibenarkan |
| Halaman profil | Dibenarkan | Dibenarkan |

### 3.3 Halaman profil
- **Rujukan design dan format:** projek GrowBiz di `E:\GrowBiz\growbiz-stage1`.
- Medan (ikut GrowBiz): gambar diri sebenar (wajib), username (3–12 huruf kecil, dikunci selepas disimpan, digunakan dalam link), nama penuh (baca sahaja), emel (baca sahaja), no. telefon/WhatsApp, negeri, bank, no. akaun bank (disulitkan, dipapar bertopeng), Facebook/Instagram/X (pilihan).
- **Tiada medan penama akaun** — penama akaun ialah nama profil. Notis dipaparkan: akaun bank wajib atas nama pemilik akaun affiliate.
- Layout portal: sidebar gaya GrowBiz (Dashboard, Wallet, Studio Poster, Profil Saya) dengan brand NatNetwork. Profil belum lengkap → hanya Profil Saya dipaparkan.

---

## 4. HALAMAN AFFILIATE

### 4.1 `/affiliate/dashboard`
**Statistik:**
1. Pelawat: unik dan berulang
2. Sumber trafik: Facebook, Instagram, WhatsApp, TikTok, Telegram, X, LinkedIn, terus (direct), lain-lain
3. Prestasi setiap poster (jumlah klik setiap poster)
4. Jenis peranti: telefon atau desktop
5. Penukaran: pelawat → buka portal → pembelian
6. Kadar penukaran (%)
7. Tapisan tempoh: hari ini, 7 hari, 30 hari, julat tarikh pilihan

**Pautan express:**
- Pautan pintas ke Studio Poster dan Wallet
- Link affiliate utama untuk disalin dan dikongsi

Nota: data komisyen **tidak** dipaparkan di dashboard (lihat 4.3).

### 4.2 `/affiliate/studio-poster`
- Poster siap (ready-made) oleh admin.
- Ditapis mengikut kategori medium media sosial.
- Setiap poster dipautkan dengan **link affiliate unik** (gabungan poster + affiliate) beserta **caption**.
- Link unik membolehkan penjejakan prestasi setiap poster.
- Butang share yang muncul **bergantung kepada medium poster dipilih**.

**Cara berfungsi (link preview):**
Link unik mengandungi maklumat pratonton (gambar poster + caption). Bila dikongsi, platform memaparkan gambar poster. Audiens klik gambar → sistem rekod klik dan simpan cookie → redirect ke halaman yang dipromosi.

**Pengendalian mengikut platform:**

| Platform | Pengendalian |
|---|---|
| WhatsApp, Telegram, X | Share automatik penuh (gambar + caption + link) |
| Facebook, LinkedIn | Share automatik (gambar + link) + butang **Salin Caption** |
| Instagram, TikTok | Butang **Muat Turun Poster** + **Salin Caption** + **Salin Link** |

- Facebook dan LinkedIn tidak membenarkan caption diisi secara automatik.
- Instagram dan TikTok tidak menyokong kongsi dari web dan link dalam caption tidak boleh diklik; affiliate letak link di bio atau sticker link Instagram Story.

**Cadangan kategori saiz (untuk kegunaan admin):**

| Medium | Saiz |
|---|---|
| Instagram / Facebook Feed (segi empat) | 1080 × 1080 |
| Instagram Feed (menegak) | 1080 × 1350 |
| Story / Reels / TikTok / WhatsApp Status | 1080 × 1920 |
| Facebook Post (melintang) | 1200 × 630 |
| LinkedIn | 1200 × 627 |
| X (Twitter) | 1600 × 900 |

Nota: kad pratonton link biasanya melintang (±1200 × 630). Poster saiz Story akan dipotong jika dikongsi sebagai link.

### 4.3 `/affiliate/wallet`
- Semua butiran berkaitan komisyen: Pending, Released, telah dikeluarkan, potongan refund.
- Permohonan pengeluaran:
  - Boleh dibuat **bila-bila masa**
  - **Tiada jumlah minimum**
  - Tempoh proses **3 hingga 5 hari bekerja** dinyatakan kepada affiliate semasa membuat permohonan
  - Dibayar ke akaun bank atas nama pemilik akaun affiliate (dari profil)

### 4.4 `/affiliate/pelanggan` (ditambah 24 Sep 2026)
- Senarai pelanggan yang dikaitkan dengan affiliate. Nama **bertopeng** (contoh: Siti A***); tiada emel atau telefon.
- Setiap pelanggan: tarikh dikaitkan, jumlah jualan terkumpul, jumlah komisyen.
- Setiap bil production: no. bil, tarikh, jumlah (RM), status bayaran (Belum dibayar / Dibayar separa / Dibayar / Dibatalkan), komisyen (RM) dan status komisyen (Pending / Released / Dibatalkan).
- Bil sandbox tidak dipaparkan. Tertakluk kepada sekatan profil lengkap.

---

## 5. PROMOSI PROGRAM AFFILIATE

- Gunakan ayat **"Komisyen sehingga 10%"**. Jangan tulis "komisyen 10%" tanpa "sehingga".
- Tunjuk contoh nilai RM:

| Jika pelanggan beli | Affiliate dapat |
|---|---|
| RM300 | RM30 |
| RM500 | RM50 |
| RM1,000 | RM90 |
| RM2,000 | RM170 |
| RM5,000 | RM350 |
| RM10,000 | RM550 |

- Butiran penuh cara kiraan diletakkan dalam **Terma & Syarat / FAQ**.

---

## 6. BAHAGIAN ADMIN

Submenu di bawah perlu **dipadankan dengan menu dan fungsi admin sedia ada** selepas audit projek.

### 6.1 Senarai Affiliate
- Affiliate berdaftar dan maklumat asas
- Pelanggan (portal) yang dikaitkan dengan setiap affiliate
- Jumlah jualan terkumpul setiap pelanggan

### 6.2 Pengurusan Poster
- Muat naik poster siap
- Tetapkan kategori medium media sosial
- Isi caption
- Tetapkan halaman yang dipromosi (destinasi link)

### 6.3 Komisyen
- Senarai komisyen bagi setiap pembelian pelanggan
- Status Pending dan Released (Released bila admin sahkan bayaran keseluruhan projek selesai)
- Rekod refund selepas komisyen dilepaskan dan potongan daripada komisyen seterusnya

### 6.4 Pengeluaran
- Senarai permohonan pengeluaran
- Status: **Menunggu Semakan, Diluluskan, Ditolak**
- Admin semak dan luluskan atau tolak dalam tempoh 3 hingga 5 hari bekerja
- Tanda sebagai telah dibayar

### 6.5 Notifikasi
- Pilih kategori penerima: **Affiliate, Client, Investor**
- Cara hantar: **Bulk** (semua dalam kategori) atau **Selected** (penerima dipilih)
- Saluran: **dalam portal sahaja**
- Semak dahulu sama ada modul notifikasi sudah wujud; jika ada, tambah pada modul sedia ada
- Cara paparan di portal penerima ikut reka bentuk portal sedia ada

### 6.6 Tetapan Affiliate
- Admin boleh ubah julat dan kadar setiap tingkat komisyen
- Admin boleh ubah tempoh cookie
- Amaran wajib dipaparkan sebelum perubahan kadar disimpan (lihat 1.7)

---

## 7. CADANGAN ALIRAN PEMBANGUNAN

Ikut susunan kebergantungan. Setiap langkah disemak dan disahkan berfungsi sebelum langkah seterusnya.

1. **Audit projek sedia ada** — menu admin, portal pelanggan, aliran pengesahan bayaran, pengendalian refund, sistem peranan pengguna (termasuk kategori Client dan Investor), modul notifikasi. Tiada perubahan kod.
2. **Asas data dan penjejakan** — akaun affiliate, link dan cookie, kaitan pelanggan dengan affiliate. Perubahan database dibentangkan untuk kelulusan dahulu.
3. **Pendaftaran, pengesahan emel, profil wajib dan sekatan route.**
4. **Enjin komisyen + Tetapan Affiliate** — kiraan bertingkat atas jumlah terkumpul, disambungkan kepada pengesahan bayaran penuh sedia ada.
5. **Admin: Senarai Affiliate.**
6. **Admin: Pengurusan Poster**, diikuti `/affiliate/studio-poster`.
7. **Admin: Komisyen** (termasuk rekod refund).
8. **Admin: Pengeluaran**, diikuti `/affiliate/wallet`.
9. **`/affiliate/dashboard`** (bergantung kepada data penjejakan).
10. **Admin: Notifikasi.**
11. **Terma & Syarat / FAQ affiliate.**

---

## 8. PERKARA MENUNGGU

- Pasang `<x-affiliate-offer-card>` pada dashboard Client dan Investor apabila dashboard tersebut dibina.
- Padanan submenu admin dengan menu dan fungsi sedia ada NatNetwork — selepas audit projek.
