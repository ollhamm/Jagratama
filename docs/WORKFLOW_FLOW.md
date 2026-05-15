# Dokumentasi Alur Persetujuan (Approval Flow)

Dokumen ini merangkum alur persetujuan berdasarkan dua jenis dokumen dan enam tipe organisasi,
beserta **catatan ketidaksesuaian** antara diagram flowchart dan implementasi kode (`WorkflowSeeder.php`).

---

## 1. KAK / LPJ Flow

> Nama workflow: `ALUR TOR, KAK/LPJ/PROPOSAL SPONSORSHIP`

### Alur berdasarkan Tipe Organisasi (dari Diagram)

| Step | SBH | UKM | HMPS (Ormawa Prodi) | HMJ (Ormawa Jurusan) | BEM | BLM |
|------|-----|-----|---------------------|----------------------|-----|-----|
| 1 | Pengaju | Pengaju | Pengaju | Pengaju | Pengaju | Pengaju |
| 2 | Ketua SBH | Ketua UKM | Ketua HMPS | Ketua HMJ | Presiden BEM | Ketua BLM |
| 3 | Pembina SBH | Pembina UKM | PJ Mahasiswa & Alumni Jurusan | PJ Mahasiswa & Alumni Jurusan | Komisi B BLM | PJ Mhs & Alumni |
| 4 | Presiden BEM | Menteri Minbat BEM | Kaprodi | Kajur | PJ Mhs & Alumni | Ka Sub Bag ADM Akademik |
| 5 | Komisi B BLM | Komisi B BLM | Kajur | Presiden BEM | Ka Sub Bag ADM Akademik | Ka Bag ADM Akademik Umum |
| 6 | PJ Mhs & Alumni | PJ Mhs & Alumni | Presiden BEM | Komisi B BLM | Ka Bag ADM Akademik Umum | **Wadir III** |
| 7 | Ka Sub Bag ADM Akademik | Ka Sub Bag ADM Akademik | Komisi B BLM | PJ Mhs & Alumni | **Wadir III** | Direktur |
| 8 | Ka Bag ADM Akademik Umum | Ka Bag ADM Akademik Umum | PJ Mhs & Alumni | Ka Sub Bag ADM Akademik | Direktur | SELESAI |
| 9 | **Wadir III** | **Wadir III** | Ka Sub Bag ADM Akademik | Ka Bag ADM Akademik Umum | SELESAI | |
| 10 | Direktur | Direktur | Ka Bag ADM Akademik Umum | **Wadir III** | | |
| 11 | SELESAI | SELESAI | **Wadir III** | Direktur | | |
| 12 | | | Direktur | SELESAI | | |
| 13 | | | SELESAI | | | |

### Yang Wajib Tanda Tangan (KAK/LPJ) — dari Legenda Diagram

| Tipe | Penandatangan |
|------|---------------|
| ORMAWA (HMPS/HMJ) | Ketua Panitia (Pengaju), Ketua Ormawa, Kajur, Direktur |
| UKM | Ketua Panitia (Pengaju), Ketua UKM, Presiden BEM, Direktur |
| SBH | Ketua Panitia, Ketua SBH, Direktur |
| BEM | Presiden BEM, Direktur |
| BLM | Ketua BLM, Direktur |

---

## 2. PERSURATAN (Surat) Flow

> Nama workflow: `ALUR PENGAJUAN PERSURATAN DIREKTORAT DAN DESAIN SERTIFIKAT`

### Alur berdasarkan Tipe Organisasi (dari Diagram)

| Step | SBH | UKM | HMPS (Ormawa Prodi) | HMJ (Ormawa Jurusan) | BEM | BLM |
|------|-----|-----|---------------------|----------------------|-----|-----|
| 1 | Pengaju | Pengaju | Pengaju | Pengaju | Pengaju | Pengaju |
| 2 | Ketua SBH | Ketua UKM | Ketua HMPS | Ketua HMJ | Presiden BEM | Ketua BLM |
| 3 | Pembina SBH | Pembina UKM | PJ Mahasiswa & Alumni Jurusan | PJ Mahasiswa & Alumni Jurusan | Komisi B BLM | PJ Mhs & Alumni |
| 4 | Presiden BEM | Menteri Minbat BEM | Kaprodi | Kajur | PJ Mhs & Alumni | Ka Sub Bag ADM Akademik |
| 5 | Komisi B BLM | Komisi B BLM | Kajur | Presiden BEM | Ka Sub Bag ADM Akademik | Ka Bag ADM Akademik Umum |
| 6 | PJ Mhs & Alumni | PJ Mhs & Alumni | Presiden BEM | Komisi B BLM | Ka Bag ADM Akademik Umum | SELESAI |
| 7 | Ka Sub Bag ADM Akademik | Ka Sub Bag ADM Akademik | Komisi B BLM | PJ Mhs & Alumni | SELESAI | |
| 8 | Ka Bag ADM Akademik Umum | Ka Bag ADM Akademik Umum | PJ Mhs & Alumni | Ka Sub Bag ADM Akademik | | |
| 9 | SELESAI | SELESAI | Ka Sub Bag ADM Akademik | Ka Bag ADM Akademik Umum | | |
| 10 | | | Ka Bag ADM Akademik Umum | SELESAI | | |
| 11 | | | SELESAI | | | |

**Perbedaan utama SURAT vs KAK/LPJ:** Alur SURAT **tidak** naik ke Wadir III dan Direktur, berhenti di Ka Bag ADM Akademik Umum.

### Yang Wajib Tanda Tangan (SURAT) — dari Legenda Diagram

| Tipe | Penandatangan |
|------|---------------|
| Semua | Ketua Panitia (Ormawa/UKM), Ketua UKM/Ormawa, Kajur (jika ada), Ka.Bag Akademik Umum |

---

## ⚠️ Ketidaksesuaian: Diagram vs Kode (`WorkflowSeeder.php`)

### A. KAK/LPJ — Masalah `WADIR_II` vs `WADIR_III`

Diagram flowchart menunjukkan **WADIR III** untuk semua tipe organisasi, namun kode menggunakan `WADIR_II` untuk semua kecuali SBH.

| Tipe Org | Di Diagram | Di Kode (`resolveKakLpjFlow`) |
|----------|-----------|-------------------------------|
| SBH | WADIR_III | `WADIR_III` ✅ |
| UKM | WADIR_III | `WADIR_II` ❌ |
| HMPS | WADIR_III | `WADIR_II` ❌ |
| HMJ | WADIR_III | `WADIR_II` ❌ |
| BEM | WADIR_III | `WADIR_II` ❌ |
| BLM | WADIR_III | `WADIR_II` ❌ |

**Lokasi kode:** `database/seeders/WorkflowSeeder.php` method `resolveKakLpjFlow()`

---

### B. KAK/LPJ — Step `KA_BAG_AKADEMIK` Ekstra

Kode memiliki step `KA_BAG_AKADEMIK` (Ka Bag Akademik) yang **tidak muncul** di diagram flowchart.
Diagram hanya menampilkan `KA_BAG_AKADEMIK_UMUM` (Ka Bag ADM Akademik Umum).

| Tipe Org | Di Diagram | Di Kode |
|----------|-----------|---------|
| SBH | ...Ka Sub Bag → **Ka Bag ADM Akademik Umum** → Wadir III | ...`KA_SUB_BAG` → **`KA_BAG_AKADEMIK`** → `KA_BAG_AKADEMIK_UMUM` → `WADIR_III` ❌ |
| UKM | ...Ka Sub Bag → **Ka Bag ADM Akademik Umum** → Wadir III | ...`KA_SUB_BAG` → **`KA_BAG_AKADEMIK`** → `KA_BAG_AKADEMIK_UMUM` → `WADIR_II` ❌ |
| HMPS | ...Ka Sub Bag → **Ka Bag ADM Akademik Umum** → Wadir III | ...`KA_SUB_BAG` → **`KA_BAG_AKADEMIK`** → `KA_BAG_AKADEMIK_UMUM` → `WADIR_II` ❌ |
| HMJ | ...Ka Sub Bag → Ka Bag ADM Akademik Umum → Wadir III | ...`KA_SUB_BAG` → `KA_BAG_AKADEMIK_UMUM` → `WADIR_II` — (tidak ada KA_BAG_AKADEMIK ekstra, hanya masalah WADIR) |
| BEM | ...Ka Sub Bag → Ka Bag ADM Akademik Umum → Wadir III | ...`KA_SUB_BAG` → `KA_BAG_AKADEMIK_UMUM` → `WADIR_II` — (hanya masalah WADIR) |
| BLM | ...Ka Sub Bag → Ka Bag ADM Akademik Umum → Wadir III | ...`KA_SUB_BAG` → `KA_BAG_AKADEMIK_UMUM` → `WADIR_II` — (hanya masalah WADIR) |

---

### C. SURAT — Step `KA_BAG_AKADEMIK` Ekstra pada HMPS

| Tipe Org | Di Diagram | Di Kode (`resolveSuratFlow`) |
|----------|-----------|------------------------------|
| HMPS | ...Ka Sub Bag → Ka Bag ADM Akademik Umum → SELESAI | ...`KA_SUB_BAG` → **`KA_BAG_AKADEMIK`** → `KA_BAG_AKADEMIK_UMUM` → SELESAI ❌ |

Semua tipe lain (SBH, UKM, HMJ, BEM, BLM) untuk SURAT **sudah sesuai** dengan diagram. ✅

---

## Ringkasan Perbaikan yang Diperlukan

| # | File | Perubahan |
|---|------|-----------|
| 1 | `WorkflowSeeder.php` → `resolveKakLpjFlow()` | Ganti `WADIR_II` → `WADIR_III` untuk: UKM, HMPS, HMJ, BEM, BLM |
| 2 | `WorkflowSeeder.php` → `resolveKakLpjFlow()` | Hapus step `KA_BAG_AKADEMIK` untuk: SBH, UKM, HMPS |
| 3 | `WorkflowSeeder.php` → `resolveSuratFlow()` | Hapus step `KA_BAG_AKADEMIK` untuk: HMPS |
