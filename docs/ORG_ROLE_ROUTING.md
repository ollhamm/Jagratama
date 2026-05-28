# Org-Scoped Role Routing

Dokumen ini menjelaskan masalah, solusi arsitektur, dan daftar lengkap role/organisasi untuk sistem routing dokumen berbasis jurusan/UKM.

---

## Masalah

HMJ, HMPS, dan UKM memiliki role yang spesifik per organisasi. Contoh: Kajur Keperawatan dan Kajur Kebidanan adalah dua orang berbeda dengan role yang sama (`KAJUR`). Apabila **HMJ Kebidanan** mengajukan dokumen, dokumen tersebut harus masuk ke **Kajur Kebidanan** — bukan ke semua Kajur.

Kondisi saat ini: `KAJUR` dan `KAPRODI` diperlakukan sebagai *global access role* di `BaseUserAccess.php`, sehingga semua Kajur melihat semua dokumen tanpa filter jurusan.

---

## Solusi: Organization-Scoped Role Routing

### Prinsip

> Satu kode role (`KAJUR`, `KAPRODI`, dll) dipakai oleh banyak user. Yang membedakan adalah `user_roles.organization_id` — kolom ini sudah ada di skema, tinggal digunakan untuk routing.

### Hierarki Organisasi

```
Institusi Poltekkes Kemenkes Yogyakarta
├── Jurusan TLM                          ← org type: JURUSAN (baru)
│   ├── HMJ TLM
│   │   ├── HMPS D3 TLM
│   │   └── HMPS STr TLM
├── Jurusan Gizi
│   ├── HMJ Gizi
│   │   ├── HMPS D3 Gizi
│   │   └── HMPS STr Gizi dan Dietetika
├── Jurusan Kebidanan
│   ├── HMJ Kebidanan
│   │   ├── HMPS D3 Kebidanan
│   │   └── HMPS STr Kebidanan
├── Jurusan Keperawatan
│   ├── HMJ Keperawatan
│   │   ├── HMPS D3 Keperawatan
│   │   ├── HMPS STr Keperawatan + Ners
│   │   └── HMPS STr Keperawatan Anestesiologi
├── Jurusan Kesehatan Gigi
│   ├── HMJ Kesehatan Gigi
│   │   ├── HMPS D3 Kesehatan Gigi
│   │   └── HMPS STr Terapi Gigi
├── Jurusan Kesehatan Lingkungan
│   ├── HMJ Kesehatan Lingkungan
│   │   ├── HMPS D3 Sanitasi
│   │   ├── HMPS STr Sanitasi Lingkungan
│   │   └── HMPS D3 Rekam Medis
├── BEM Poltekkes Kemenkes Yogyakarta
├── BLM Poltekkes Kemenkes Yogyakarta
├── UKM MB                               ← masing-masing UKM org terpisah
├── UKM Keprotokoleran
├── UKM Paskibra
├── UKM SBH
├── UKM PSQ
├── UKM KSR
├── UKM Pers
├── UKM PSM
├── UKM Karawitan
├── UKM Tari
├── UKM PIK-M
├── UKM Taekwondo
├── UKM Riset
├── UKM PMKK
├── UKM P4GN
├── UKM Eclipse
├── UKM SKI
├── UKM IT
├── UKM Teater
├── UKM Mapapy
└── UKM Olahraga
```

### Cara Assign Role

| User | Role (code) | organization_id |
|---|---|---|
| Kajur Keperawatan | `KAJUR` | `jurusan_keperawatan.id` |
| Kajur Kebidanan | `KAJUR` | `jurusan_kebidanan.id` |
| Kaprodi D3 Keperawatan | `KAPRODI` | `hmps_d3_keperawatan.id` |
| Kaprodi STr Kebidanan | `KAPRODI` | `hmps_str_kebidanan.id` |
| PJ Kemahasiswaan Keperawatan | `PJ_MAHASISWA_ALUMNI_JURUSAN` | `jurusan_keperawatan.id` |
| Pembina UKM MB | `PEMBINA_UKM` | `ukm_mb.id` |
| Pembina UKM Paskibra | `PEMBINA_UKM` | `ukm_paskibra.id` |

### Cara Routing Bekerja

```
HMJ Kebidanan submit dokumen → step KAJUR
  → Kajur Kebidanan di-assign ke jurusan_kebidanan
  → organizationIds() ekspansi subtree jurusan_kebidanan:
       [jurusan_kebidanan.id, hmj_kebidanan.id, hmps_d3_kebidanan.id, hmps_str_kebidanan.id]
  → document.organization_id = hmj_kebidanan.id → MATCH ✓
  → Kajur Kebidanan melihat dokumen, dapat notif

  → Kajur Keperawatan di-assign ke jurusan_keperawatan
  → subtree jurusan_keperawatan tidak mengandung hmj_kebidanan.id → TIDAK match ✗
  → Kajur Keperawatan tidak melihat dokumen
```

### Titik Kritis: `organizationIds()` Harus Ekspansi Subtree

Ini adalah perubahan paling penting. Method `organizationIds()` di `BaseUserAccess.php` saat ini hanya mengembalikan org yang di-assign **langsung** ke user:

```
// Sekarang (salah untuk kasus ini):
Kajur Keperawatan → [jurusan_keperawatan.id]

// Document dari HMJ Keperawatan:
document.organization_id = hmj_keperawatan.id
hmj_keperawatan.id NOT IN [jurusan_keperawatan.id] → filter gagal ❌
```

Harus diubah agar mengembalikan seluruh ID turunan:

```
// Seharusnya:
Kajur Keperawatan → [
    jurusan_keperawatan.id,     ← assigned langsung
    hmj_keperawatan.id,         ← anak
    hmps_d3_keperawatan.id,     ← cucu
    hmps_str_keperawatan.id,    ← cucu
    ...
]
document.organization_id = hmj_keperawatan.id → MATCH ✓
```

Method ini juga dipakai di `DocumentRepository.php` (baris 21 dan 78) untuk visibilitas daftar dokumen — perubahan di sini otomatis memperbaiki kedua tempat sekaligus.

---

## Perubahan Kode yang Diperlukan

| # | File | Perubahan | Keterangan |
|---|---|---|---|
| 1 | `app/Enums/OrganizationType.php` | Tambah `case JURUSAN = 'JURUSAN'` | Tipe org baru untuk node Jurusan |
| 2 | `database/seeders/OrganizationSeeder.php` | Buat 6 Jurusan, expand semua HMJ/HMPS, buat 21 org UKM terpisah | Bentuk hierarki lengkap |
| 3 | `app/Models/Organization.php` | Tambah method `descendants(): Collection` | Rekursi lewat `children()`, kumpulkan semua ID turunan |
| 4 | `app/Repositories/Eloquent/BaseUserAccess.php` | (a) Hapus `KAJUR`, `KAPRODI`, `PJ_MAHASISWA_ALUMNI_JURUSAN` dari global list; (b) Ubah `organizationIds()` gunakan `descendants()` | **Perubahan terpenting** — berdampak ke ApprovalRepository dan DocumentRepository sekaligus |

**Tidak perlu:** tambah role baru, ubah skema/migration tabel, ubah WorkflowSeeder.

### Catatan Dampak Lintas File

`organizationIds()` dipakai di dua repository:
- `ApprovalRepository::paginatePendingForUser()` — antrian approval per user
- `ApprovalRepository::findPendingByIdForUser()` — akses satu approval
- `DocumentRepository::paginateForUser()` — daftar dokumen
- `DocumentRepository::findByIdForUser()` — detail dokumen

Semua empat fungsi ini otomatis mendapat perilaku yang benar setelah `organizationIds()` diperbaiki — tidak perlu edit keempat fungsi tersebut satu per satu.

---

## UI: Form Buat Dokumen (Pengaju)

### Masalah

`DocumentPageController::create()` saat ini mengirim semua organisasi tanpa filter. Setelah hierarki diperluas, dropdown akan berisi 40+ entri termasuk node internal (JURUSAN, SBH) yang tidak boleh dipilih pengaju.

### Pendekatan: Auto-Assign dari Role Pengaju

Pengaju tidak perlu memilih organisasi secara manual. Sistem mengambil `organization_id` dari `user_roles` milik user yang login (role `PENGAJU`).

```
User login sebagai Pengaju HMJ Kebidanan
  user_roles: { role: PENGAJU, organization_id: hmj_kebidanan.id }
  → field "Organisasi" otomatis terisi "HMJ Kebidanan" (read-only)

User login sebagai Pengaju UKM MB
  user_roles: { role: PENGAJU, organization_id: ukm_mb.id }
  → field "Organisasi" otomatis terisi "UKM MB" (read-only)
```

**Edge case — pengaju di lebih dari satu org:**
Tampilkan dropdown, tapi hanya berisi org milik user tersebut (bukan semua org).

**Admin "on behalf of":**
Setelah pilih user pengaju, sistem fetch org dari user tersebut dan populate field organisasi.

### Perubahan yang Diperlukan

| # | File | Perubahan |
|---|---|---|
| 1 | `DocumentPageController::create()` | Kirim hanya org yang di-assign ke user login via `user_roles`, bukan `Organization::all()` |
| 2 | `create.blade.php` | Jika 1 org → read-only. Jika >1 org → dropdown terbatas org milik user |
| 3 | `create.blade.php` (admin) | Setelah pilih "on behalf of", AJAX fetch org dari pengaju tersebut |

---

## UI: Form Tambah / Edit User (User Management)

### Masalah di Kode yang Ada

`buildRoleOrgMap()` di `UserManagementPageController.php` memiliki dua bug:

**Bug 1 — mapping role ke tipe org yang salah:**
```php
'KAJUR'                       => 'HMJ',  // ← seharusnya JURUSAN
'PJ_MAHASISWA_ALUMNI_JURUSAN' => 'HMJ',  // ← seharusnya JURUSAN
```

**Bug 2 — selalu ambil org pertama:**
```php
$orgByType->get($orgType)?->first()?->id
// jika ada 6 Jurusan, selalu ambil yang pertama secara alfabet → salah
```

### Pendekatan: Dropdown Difilter Berdasarkan Role

Field "Organisasi Role" berubah dari **disabled/auto-fill** menjadi **aktif dan difilter** sesuai role yang dipilih.

```
Pilih Role: KAJUR
→ Organisasi Role aktif → dropdown hanya tipe JURUSAN (6 pilihan)

Pilih Role: KAPRODI
→ Organisasi Role aktif → dropdown hanya tipe HMPS (14 pilihan)

Pilih Role: PEMBINA_UKM
→ Organisasi Role aktif → dropdown hanya tipe UKM (21 pilihan)

Pilih Role: PENGAJU
→ Organisasi Role aktif → dropdown semua tipe ormawa (HMJ/HMPS/UKM/BEM/BLM)

Pilih Role: DIREKTUR / WADIR / ADMIN / dll
→ Organisasi Role tetap disabled, nilai null (global)
```

### Mapping Role → Tipe Org (Koreksi)

| Role | Sekarang | Seharusnya |
|---|---|---|
| `KAJUR` | `HMJ` | `JURUSAN` |
| `PJ_MAHASISWA_ALUMNI_JURUSAN` | `HMJ` | `JURUSAN` |
| `KETUA_HMJ` | `HMJ` | `HMJ` ✓ |
| `KAPRODI` | `HMPS` | `HMPS` ✓ |
| `KETUA_HMPS` | `HMPS` | `HMPS` ✓ |
| `PEMBINA_UKM` | `UKM` | `UKM` ✓ |
| `KETUA_UKM` | `UKM` | `UKM` ✓ |
| `PENGAJU` | tidak ada | HMJ / HMPS / UKM / BEM / BLM |

### Perubahan yang Diperlukan

| # | File | Perubahan |
|---|---|---|
| 1 | `UserManagementPageController.php` | `buildRoleOrgMap` kirim semua org per tipe (bukan first), koreksi mapping KAJUR & PJ ke JURUSAN, tambah PENGAJU |
| 2 | `create.blade.php` & `edit.blade.php` | "Organisasi Role" jadi enabled + dropdown difilter by role; global role tetap disabled |

---

## Daftar Lengkap Role per Kategori

### Ketua Program Studi (KAPRODI)

Role code: `KAPRODI`

| No | Nama | organization_id (HMPS) |
|---|---|---|
| 1 | Kaprodi D3 TLM | HMPS D3 TLM |
| 2 | Kaprodi STr TLM | HMPS STr TLM |
| 3 | Kaprodi D3 Gizi | HMPS D3 Gizi |
| 4 | Kaprodi STr Gizi dan Dietetika | HMPS STr Gizi dan Dietetika |
| 5 | Kaprodi D3 Kebidanan | HMPS D3 Kebidanan |
| 6 | Kaprodi STr Kebidanan | HMPS STr Kebidanan |
| 7 | Kaprodi D3 Keperawatan | HMPS D3 Keperawatan |
| 8 | Kaprodi STr Keperawatan + Ners | HMPS STr Keperawatan + Ners |
| 9 | Kaprodi STr Keperawatan Anestesiologi | HMPS STr Keperawatan Anestesiologi |
| 10 | Kaprodi D3 Kesehatan Gigi | HMPS D3 Kesehatan Gigi |
| 11 | Kaprodi STr Terapi Gigi | HMPS STr Terapi Gigi |
| 12 | Kaprodi D3 Sanitasi | HMPS D3 Sanitasi |
| 13 | Kaprodi STr Sanitasi Lingkungan | HMPS STr Sanitasi Lingkungan |
| 14 | Kaprodi D3 Rekam Medis | HMPS D3 Rekam Medis |

### Ketua Jurusan (KAJUR)

Role code: `KAJUR`

| No | Nama | organization_id (JURUSAN) |
|---|---|---|
| 1 | Kajur TLM | Jurusan TLM |
| 2 | Kajur Gizi | Jurusan Gizi |
| 3 | Kajur Kebidanan | Jurusan Kebidanan |
| 4 | Kajur Keperawatan | Jurusan Keperawatan |
| 5 | Kajur Kesehatan Gigi | Jurusan Kesehatan Gigi |
| 6 | Kajur Kesehatan Lingkungan | Jurusan Kesehatan Lingkungan |

### PJ Kemahasiswaan Jurusan

Role code: `PJ_MAHASISWA_ALUMNI_JURUSAN`

| No | Nama | organization_id (JURUSAN) |
|---|---|---|
| 1 | PJ Kemahasiswaan TLM | Jurusan TLM |
| 2 | PJ Kemahasiswaan Gizi | Jurusan Gizi |
| 3 | PJ Kemahasiswaan Kebidanan | Jurusan Kebidanan |
| 4 | PJ Kemahasiswaan Keperawatan | Jurusan Keperawatan |
| 5 | PJ Kemahasiswaan Kesehatan Gigi | Jurusan Kesehatan Gigi |
| 6 | PJ Kemahasiswaan Kesehatan Lingkungan | Jurusan Kesehatan Lingkungan |

### Pembina UKM

Role code: `PEMBINA_UKM`

| No | Nama | organization_id (UKM) |
|---|---|---|
| 1 | Pembina UKM MB | UKM MB |
| 2 | Pembina UKM Keprotokoleran | UKM Keprotokoleran |
| 3 | Pembina UKM Paskibra | UKM Paskibra |
| 4 | Pembina UKM SBH | UKM SBH |
| 5 | Pembina UKM PSQ | UKM PSQ |
| 6 | Pembina UKM KSR | UKM KSR |
| 7 | Pembina UKM Pers | UKM Pers |
| 8 | Pembina UKM PSM | UKM PSM |
| 9 | Pembina UKM Karawitan | UKM Karawitan |
| 10 | Pembina UKM Tari | UKM Tari |
| 11 | Pembina UKM PIK-M | UKM PIK-M |
| 12 | Pembina UKM Taekwondo | UKM Taekwondo |
| 13 | Pembina UKM Riset | UKM Riset |
| 14 | Pembina UKM PMKK | UKM PMKK |
| 15 | Pembina UKM P4GN | UKM P4GN |
| 16 | Pembina UKM Eclipse | UKM Eclipse |
| 17 | Pembina UKM SKI | UKM SKI |
| 18 | Pembina UKM IT | UKM IT |
| 19 | Pembina UKM Teater | UKM Teater |
| 20 | Pembina UKM Mapapy | UKM Mapapy |
| 21 | Pembina UKM Olahraga | UKM Olahraga |
