# Dokumentasi Alur Persetujuan (Approval Flow)

Dokumen ini adalah **referensi validasi** alur approval, disusun dari pembacaan langsung `FLOWCHART REVISI.pdf` (versi terbaru), dibandingkan baris-per-baris dengan implementasi kode di `database/seeders/WorkflowSeeder.php`.

Setiap org type dibaca sebagai kolom independen dari "Mulai" sampai "Selesai" — tidak mengasumsikan kesejajaran baris antar kolom, karena panjang alur tiap tipe organisasi berbeda-beda.

---

## 1. KAK / LPJ Flow

> Nama workflow di kode: `ALUR TOR, KAK/LPJ/PROPOSAL SPONSORSHIP`

### Per Tipe Organisasi (dari flowchart)

| Tipe Org | Urutan Approval (flowchart) |
|---|---|
| **SBH** | Pengaju → Ketua SBH → Pembina SBH → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → **Wadir II** → Direktur → Selesai |
| **UKM** | Pengaju → Ketua UKM → Pembina UKM → **Menteri Minbak BEM** → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Wadir III → Direktur → Selesai |
| **HMPS (Ormawa Prodi)** | Pengaju → Ketua HMPS → **[PJ Mahasiswa, dan Alumni Jurusan/Prodi/Kajur]** *(kotak gabungan)* → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Wadir III → Direktur → Selesai |
| **HMJ (Ormawa Jurusan)** | Pengaju → Ketua HMJ → **[PJ Mahasiswa dan Alumni Jurusan/Kajur]** *(kotak gabungan)* → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → **Ka Bag Adm Akademik** *(tanpa "Umum")* → Wadir III → Direktur → Selesai |
| **BEM** | Pengaju → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Wadir III → Direktur → Selesai |
| **BLM** | Pengaju → Ketua BLM → **[Komisi B BLM]** *(kuning)* → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Wadir III → Direktur → Selesai |

**Catatan penting per kolom:**
- **UKM tidak memiliki step Presiden BEM** pada KAK/LPJ — hanya Menteri Minbak BEM lalu langsung ke Komisi B BLM. (Berbeda dengan Persuratan, lihat bagian 2.)
- **HMJ memakai "Ka Bag Adm Akademik" tanpa kata "Umum"** — kemungkinan merujuk ke role berbeda (`KA_BAG_AKADEMIK` bukan `KA_BAG_AKADEMIK_UMUM`). Tipe org lain konsisten memakai "...Umum".
- **SBH satu-satunya yang memakai Wadir II**, semua tipe lain memakai Wadir III.

### Kode Saat Ini (`resolveKakLpjFlow()`)

```php
SBH: KETUA_SBH, PEMBINA_SBH, PRESIDEN_BEM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA,
     KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM, WADIR_III, DIREKTUR

UKM: KETUA_UKM, PEMBINA_UKM, MENTERI_MINAT_BAKAT_BEM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA,
     KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM, WADIR_III, DIREKTUR

HMPS: KETUA_HMPS, PJ_MAHASISWA_ALUMNI_JURUSAN, KAPRODI, KAJUR, PRESIDEN_BEM, KOMISI_B_BLM,
      PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM, WADIR_III, DIREKTUR

HMJ: KETUA_HMJ, PJ_MAHASISWA_ALUMNI_JURUSAN, KAJUR, PRESIDEN_BEM, KOMISI_B_BLM,
     PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM, WADIR_III, DIREKTUR

BEM: PRESIDEN_BEM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK,
     KA_BAG_AKADEMIK_UMUM, WADIR_III, DIREKTUR

BLM: KETUA_BLM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK,
     KA_BAG_AKADEMIK_UMUM, WADIR_III, DIREKTUR
```

### Hasil Perbandingan

| Tipe Org | Status | Keterangan |
|---|---|---|
| SBH | ⚠️ **Beda** | Kode: `WADIR_III`. Flowchart: **Wadir II**. Perlu klarifikasi sebelum diubah. |
| UKM | ⚠️ **Beda** | Kode menyertakan langkah role `MENTERI_MINAT_BAKAT_BEM` lalu langsung `KOMISI_B_BLM` — **sudah cocok**, tidak ada Presiden BEM di kode maupun flowchart. ✅ Sesuai. |
| HMPS | ⚠️ **Beda struktur** | Kode punya 2 step terpisah (`KAPRODI`, `KAJUR`); flowchart menggabungkan jadi 1 step (`PJ_MAHASISWA_ALUMNI_JURUSAN` saja). |
| HMJ | ⚠️ **Beda struktur + role** | Kode punya step terpisah `KAJUR`; flowchart menggabungkan ke step PJ. Selain itu kode pakai `KA_BAG_AKADEMIK_UMUM`, flowchart menunjukkan **`KA_BAG_AKADEMIK`** (tanpa Umum) khusus untuk HMJ. |
| BEM | ✅ **Sesuai** | Identik. |
| BLM | ✅ **Sesuai secara struktur** | Urutan kode = urutan flowchart. **Namun evaluator menandai flowchart KAK/LPJ BLM ini sendiri sebagai salah** (lihat `docs/Revisi/REVISI_2026-06-21.md` Evaluasi #2) — jangan dijadikan acuan perubahan sampai ada revisi gambar yang baru. |

---

## 2. PERSURATAN (Surat) Flow

> Nama workflow di kode: `ALUR PENGAJUAN PERSURATAN DIREKTORAT DAN DESAIN SERTIFIKAT`

### Per Tipe Organisasi (dari flowchart)

| Tipe Org | Urutan Approval (flowchart) |
|---|---|
| **SBH** | Pengaju → Ketua SBH → Pembina SBH → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Selesai |
| **UKM** | Pengaju → Ketua UKM → Pembina UKM → Menteri Minbak BEM → **Presiden BEM** → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Selesai |
| **HMPS (Ormawa Prodi)** | Pengaju → Ketua → **[PJ Mahasiswa, dan Alumni Jurusan/Prodi]** *(kotak gabungan)* → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Selesai |
| **HMJ (Ormawa Jurusan)** | Pengaju → Ketua HMJ → **[PJ Mahasiswa dan Alumni Jurusan/Kajur]** *(kotak gabungan)* → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Selesai |
| **BEM** | Pengaju → Presiden BEM → Komisi B BLM → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Selesai |
| **BLM** | Pengaju → Ketua BLM → **[Komisi B BLM]** *(kuning)* → Penanggung Jawab Mahasiswa dan Alumni → Ka Sub Bag Adm Akademik → Ka Bag Adm Akademik Umum → Selesai |

**Catatan penting:**
- **UKM Persuratan memiliki step Presiden BEM**, berbeda dari UKM KAK/LPJ yang tidak punya step ini. Ini adalah perbedaan yang sah antara dua jenis dokumen, bukan kesalahan baca.
- HMJ Persuratan memakai **"Ka Bag Adm Akademik Umum"** (dengan "Umum") — berbeda dari HMJ KAK/LPJ yang tanpa "Umum". Konsisten dengan tipe org lain di flow ini.
- Tidak ada step Wadir/Direktur di seluruh flow Persuratan — berhenti di Ka Bag Adm Akademik Umum, sesuai legenda tanda tangan ("Ketua Panitia, Ketua Ormawa/UKM, Ka.Bag").

### Kode Saat Ini (`resolveSuratFlow()`)

```php
SBH: KETUA_SBH, PEMBINA_SBH, PRESIDEN_BEM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA,
     KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM

UKM: KETUA_UKM, PEMBINA_UKM, MENTERI_MINAT_BAKAT_BEM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA,
     KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM

HMPS: KETUA_HMPS, PJ_MAHASISWA_ALUMNI_JURUSAN, KAPRODI, KAJUR, PRESIDEN_BEM, KOMISI_B_BLM,
      PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM

HMJ: KETUA_HMJ, PJ_MAHASISWA_ALUMNI_JURUSAN, KAJUR, PRESIDEN_BEM, KOMISI_B_BLM,
     PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK, KA_BAG_AKADEMIK_UMUM

BEM: PRESIDEN_BEM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK,
     KA_BAG_AKADEMIK_UMUM

BLM: KETUA_BLM, KOMISI_B_BLM, PENANGGUNG_JAWAB_MAHASISWA, KA_SUB_BAG_AKADEMIK,
     KA_BAG_AKADEMIK_UMUM
```

### Hasil Perbandingan

| Tipe Org | Status | Keterangan |
|---|---|---|
| SBH | ✅ **Sesuai** | Identik. |
| UKM | ❌ **Beda — kekurangan step** | Kode **tidak punya** `PRESIDEN_BEM`. Flowchart menyisipkan Presiden BEM setelah Menteri Minbak BEM, sebelum Komisi B BLM. |
| HMPS | ⚠️ **Beda struktur** | Kode punya 2 step terpisah (`KAPRODI`, `KAJUR`); flowchart 1 step gabungan. |
| HMJ | ⚠️ **Beda struktur** | Kode punya step terpisah `KAJUR`; flowchart gabung ke step PJ. `KA_BAG_AKADEMIK_UMUM` di kode **sudah cocok** dengan flowchart untuk flow ini (beda dengan KAK/LPJ). |
| BEM | ✅ **Sesuai** | Identik. |
| BLM | ✅ **Sesuai** | Identik — sudah divalidasi di `docs/Revisi/REVISI_2026-06-26.md` poin 1. |

---

## 3. Legenda Tanda Tangan (dari flowchart)

### KAK/LPJ
| Tipe | Penandatangan |
|---|---|
| ORMAWA (HMPS/HMJ) | Ketua Panitia (Pengaju), Ketua Ormawa, Kajur, Direktur |
| UKM | Ketua Panitia (Pengaju), Ketua UKM, Presiden BEM, Direktur |
| SBH | Ketua Panitia, Ketua SBH, Direktur |

### Persuratan
| Tipe | Penandatangan |
|---|---|
| Semua | Ketua Panitia (Ormawa/UKM), Ketua Ormawa/UKM, Ka.Bag |

---

## 4. Ringkasan Temuan & Tindak Lanjut

| # | Temuan | Sumber | Status |
|---|---|---|---|
| 1 | HMPS & HMJ: gabungkan Kaprodi/Kajur ke step PJ | Flowchart A.1 | Belum diimplementasikan ke kode |
| 2 | UKM Persuratan kekurangan step Presiden BEM | Flowchart 2 | Belum diimplementasikan ke kode |
| 3 | SBH KAK/LPJ: Wadir II vs Wadir III | Flowchart 1 | ⚠️ Perlu klarifikasi, jangan diubah dulu |
| 4 | HMJ KAK/LPJ: "Ka Bag Akademik" vs "Ka Bag Akademik Umum" | Flowchart 1 | ⚠️ Perlu klarifikasi — kemungkinan role berbeda (`KA_BAG_AKADEMIK` vs `KA_BAG_AKADEMIK_UMUM`) |
| 5 | BLM KAK/LPJ — flowchart diakui salah oleh evaluator meski cocok dengan kode | Evaluasi #2 | ❌ Jangan diubah sampai ada revisi gambar |
| 6 | BLM Persuratan | Evaluasi #1 | ✅ Sudah divalidasi sesuai (`REVISI_2026-06-26.md` poin 1) |

---

## Catatan Keterbatasan Pembacaan

- Label kotak gabungan "PJ Mahasiswa, dan Alumni Jurusan/Prodi/Kajur" dibaca dari hasil OCR/ekstraksi PDF dan berpotensi mengandung noise pada bagian Persuratan Ormawa Prodi (teks tampak terduplikasi). Tidak mengubah kesimpulan struktural (tetap 1 step gabungan), hanya redaksi label yang mungkin kurang presisi.
- Dokumen ini menggantikan isi `WORKFLOW_FLOW.md` versi sebelumnya yang disusun dari diagram lama (sudah digantikan oleh `FLOWCHART REVISI.pdf`).
