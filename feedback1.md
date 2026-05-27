# Planning Feedback - Sistem Persuratan Jagratama

**Tanggal:** 24 Mei 2026  
**Status:** Draft Planning

---

## Ringkasan

Dokumen ini merangkum seluruh feedback dari hasil pengujian sistem persuratan dan merencanakan perbaikan serta fitur tambahan yang perlu diimplementasikan.

---

## 1. BUG FIX — Prioritas Tinggi

### 1.1 Error 403 pada "Kelola Dokumen"
- **Masalah:** Setiap kali pengguna mengklik menu "Kelola Dokumen", sistem mengembalikan error 403 (Forbidden).
- **Dampak:** Fitur kelola dokumen tidak dapat digunakan sama sekali.
- **Rencana Perbaikan:**
  - Audit middleware auth/policy pada route kelola dokumen
  - Periksa Gate/Policy Laravel yang mengatur akses kelola dokumen
  - Pastikan role pengguna yang login memiliki permission yang sesuai
  - Tambahkan logging untuk mendeteksi sumber error 403

---

### 1.2 Notifikasi "Gagal" Padahal Dokumen Berhasil Tersimpan
- **Masalah:** Saat pengajuan dokumen, muncul notifikasi error "gagal", namun dokumen sebenarnya berhasil disimpan di database.
- **Dampak:** Pengguna menjadi bingung dan tidak percaya terhadap sistem.
- **Rencana Perbaikan:**
  - Telusuri response handling di frontend (JavaScript/Ajax callback)
  - Periksa apakah ada request kedua (misalnya upload file) yang gagal setelah data utama berhasil disimpan
  - Pastikan semua operasi dalam satu transaksi sebelum menampilkan notifikasi berhasil
  - Perbaiki kondisi success/error pada response handler

---

### 1.3 Format Pengajuan Harus PDF (Bukan Word)
- **Masalah:** Dokumen hasil pengajuan menghasilkan file Word (.docx), padahal seharusnya berbentuk PDF.
- **Rencana Perbaikan:**
  - Ganti library generate dokumen menjadi output PDF langsung (misalnya menggunakan DomPDF atau Snappy/wkhtmltopdf)
  - Jika dokumen awalnya dibuat dari template Word, tambahkan konversi otomatis ke PDF sebelum disimpan
  - Pastikan semua titik generate dokumen menghasilkan `.pdf`

---

### 1.4 Download TTD di BLM Tidak Ada Logo
- **Masalah:** Di halaman BLM, setelah surat selesai dibuat, saat diunduh tanda tangan tidak menyertakan logo. Sementara file di modul lain tidak bermasalah.
- **Rencana Perbaikan:**
  - Bandingkan template PDF BLM dengan template modul lain yang berfungsi normal
  - Periksa path aset logo dalam template BLM
  - Pastikan logo di-embed ke dalam PDF, bukan di-link dari URL eksternal
  - Lakukan render ulang template BLM

---

### 1.5 Tampilan Terpotong (Crop) Saat User Zoom
- **Masalah:** Beberapa pengguna melaporkan tampilan ter-crop/terpotong ketika melakukan zoom pada browser.
- **Rencana Perbaikan:**
  - Audit CSS layout — ganti penggunaan `overflow: hidden` yang tidak tepat
  - Gunakan unit responsif (`rem`, `%`, `vw/vh`) dan hindari fixed pixel width pada container utama
  - Tambahkan `min-width` yang tepat agar konten tidak terpotong saat zoom in
  - Uji pada berbagai level zoom (75%, 100%, 125%, 150%) dan berbagai resolusi layar

---

## 2. PERBAIKAN ALUR (FLOWCHART) — Prioritas Tinggi

### 2.1 Koreksi Alur Log Persetujuan BLM
- **Masalah:** Setelah Ketua BLM menyetujui, sistem langsung melanjutkan ke PJ Kemahasiswaan. Seharusnya setelah Ketua BLM, dokumen masuk ke **Komisi B** terlebih dahulu.
- **Alur yang Benar:**
  ```
  [SEBELUM - SALAH]
  Ketua BLM → PJ Kemahasiswaan

  [SESUDAH - BENAR]
  Ketua BLM → Komisi B → PJ Kemahasiswaan
  ```
- **Rencana Perbaikan:**
  - Update tabel workflow/flowchart di database untuk menambahkan step Komisi B
  - Tambahkan role/actor Komisi B pada sistem jika belum ada
  - Sesuaikan tampilan log timeline di frontend
  - Update notifikasi email/sistem untuk Komisi B

---

## 3. FITUR BARU — Tanda Tangan Digital & QR Code

### 3.1 Tanda Tangan Online pada Form Pengajuan
- **Kebutuhan:** Saat pengajuan dokumen, pengaju wajib melampirkan tanda tangan digital dari:
  - Ketua Pengaju (pemohon)
  - Ketua Ormawa/UKM
- **Rencana Implementasi:**
  - Tambahkan komponen signature pad (canvas-based, contoh: `signature_pad.js`) di form pengajuan
  - Simpan hasil TTD sebagai gambar (PNG/SVG) ke storage
  - Tampilkan preview TTD sebelum submit

---

### 3.2 Generate QR Code / Barcode dari TTD
- **Kebutuhan:** Setelah TTD berhasil ditambahkan, sistem otomatis generate QR code yang terhubung ke TTD tersebut.
- **Aturan QR Code berdasarkan peran:**

  | Pemilik TTD | Isi Ketika Di-scan |
  |---|---|
  | Ketua Acara | Menampilkan gambar tanda tangan |
  | Ketua Ormawa/UKM | Menampilkan gambar tanda tangan |
  | Ka. Bag (Persuratan) | Menampilkan dokumen surat secara penuh |
  | Direktur | Menampilkan dokumen surat secara penuh |

- **Rencana Implementasi:**
  - Generate QR code dengan library (contoh: `SimpleSoftwareIO/simple-qrcode` untuk Laravel)
  - Buat endpoint publik untuk setiap QR: `/qr/ttd/{id}` dan `/qr/dokumen/{id}`
  - Endpoint TTD mengembalikan halaman berisi gambar tanda tangan
  - Endpoint dokumen mengembalikan halaman preview surat/PDF
  - Embed QR code ke dalam dokumen PDF yang digenerate

---

### 3.3 Logo Ormawa/UKM pada QR Code
- **Kebutuhan:** QR code yang digenerate harus menyertakan logo Ormawa/UKM sesuai profil pengaju, ditampilkan di tengah QR code.
- **Rencana Implementasi:**
  - Ambil logo Ormawa/UKM dari profil organisasi pengaju
  - Overlay logo ke tengah QR code menggunakan GD/Imagick atau library QR yang mendukung logo
  - Pastikan ukuran logo tidak melebihi 30% dari luas QR agar tetap dapat di-scan
  - Fallback ke logo default jika organisasi belum upload logo

---

## 4. FITUR BARU — Alur Publikasi Dokumen

### 4.1 Publikasi Surat oleh Pengaju & Persetujuan Komisi B
- **Kebutuhan:** Setelah pengaju mempublikasikan surat, Komisi B dapat melakukan aksi **Approve** atau **Reject**.
- **Alur:**
  ```
  Pengaju → Publikasi Surat → Komisi B (Approve/Reject)
  ```
- **Rencana Implementasi:**
  - Tambahkan status `published` pada dokumen
  - Tambahkan tombol "Publikasikan" untuk pengaju setelah surat selesai
  - Buat tampilan antrian dokumen di dashboard Komisi B
  - Tambahkan tombol Approve dan Reject beserta kolom catatan/alasan

---

### 4.2 Posisi Log Publikasi Dokumen
- **Kebutuhan:** Penempatan langkah "Publikasi Dokumen" dalam log alur harus sesuai dengan jenis dokumen:
  - **Persuratan:** Publikasi berada setelah Ka. Bag (Persuratan)
  - **KAK / LPJ:** Publikasi berada setelah Direktur
- **Rencana Implementasi:**
  - Buat konfigurasi alur per jenis dokumen
  - Pastikan logic penentuan step publikasi membaca jenis dokumen
  - Update tampilan timeline log agar mencerminkan posisi publikasi yang benar

---

## 5. FITUR BARU — Role Khusus per Organisasi (HMJ / HMPS / UKM)

### 5.1 Routing Dokumen Berdasarkan Organisasi Pengaju
- **Kebutuhan:** Untuk HMJ, HMPS, dan UKM, dokumen yang diajukan harus diarahkan ke pejabat yang spesifik berdasarkan organisasi, bukan ke satu pejabat umum.
- **Contoh Kasus:**
  - HMJ Kebidanan mengajukan → masuk ke **Kajur Kebidanan**
  - HMJ Keperawatan mengajukan → masuk ke **Kajur Keperawatan**
  - (Tidak boleh campur ke satu Kajur yang sama)
- **Role yang Perlu Dibuat per Organisasi:**
  - Kajur (per jurusan)
  - Kaprodi (per program studi)
  - PJ Kemahasiswaan (per unit)
  - Pembina (per organisasi)

- **Rencana Implementasi:**
  - Tambahkan relasi `organisasi_id` pada tabel users/roles
  - Buat tabel mapping: `organisasi` → `pejabat_approver`
  - Saat dokumen diajukan, sistem otomatis mendeteksi organisasi pengaju dan menentukan approver yang tepat
  - Pastikan satu akun pejabat hanya menerima dokumen dari organisasi yang sesuai
  - Update UI admin untuk mengelola mapping organisasi ↔ pejabat

---

## Prioritas Pengerjaan

| No | Item | Prioritas | Estimasi |
|---|---|---|---|
| 1 | Fix error 403 Kelola Dokumen | KRITIS | 1-2 hari |
| 2 | Fix notifikasi gagal padahal berhasil | KRITIS | 1 hari |
| 3 | Koreksi alur BLM → Komisi B → PJ | TINGGI | 1-2 hari |
| 4 | Fix format PDF (bukan Word) | TINGGI | 1-2 hari |
| 5 | Fix logo TTD di BLM | TINGGI | 1 hari |
| 6 | Fix tampilan zoom/crop | SEDANG | 2-3 hari |
| 7 | TTD online pada form pengajuan | TINGGI | 3-4 hari |
| 8 | Generate QR code + logo ormawa | TINGGI | 3-4 hari |
| 9 | Perilaku scan QR berdasarkan peran | TINGGI | 2-3 hari |
| 10 | Alur publikasi + persetujuan Komisi B | SEDANG | 2-3 hari |
| 11 | Posisi log publikasi per jenis dokumen | SEDANG | 1-2 hari |
| 12 | Role khusus per organisasi HMJ/HMPS/UKM | TINGGI | 4-5 hari |

---

## Catatan Teknis

- Semua perbaikan bug harus disertai pengujian regresi sebelum deploy
- Fitur QR code memerlukan keputusan: apakah QR hanya valid saat online, atau bisa diverifikasi offline?
- Role per organisasi memerlukan migrasi data yang hati-hati agar tidak merusak data yang sudah ada
- Perlu diskusi dengan tim terkait siapa yang berwenang mapping organisasi ↔ pejabat approver
