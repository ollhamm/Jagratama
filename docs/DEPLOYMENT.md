# Panduan Redeploy & Seeding ke Production (VPS)

> Ditulis setelah insiden redeploy 2026-07-22: Komisi B BLM hilang dari alur,
> baris `workflow_steps` nyasar (dobel Direktur), dan `document_types` basi
> (`LPJ`) bikin dropdown "Tipe Dokumen" tampil dobel. Ikuti urutan di dokumen
> ini supaya tidak terulang.

## Info Server

- Host: `202.10.48.67` (user: `root`)
- Path aplikasi: `/var/www/jagratama`
- PHP-FPM: **8.2** (bukan 8.4 seperti di lokal) — service: `php8.2-fpm`
- Database: MySQL, cek nama DB/kredensial asli di `.env` server (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), jangan asumsikan sama dengan lokal.

## Urutan Redeploy (WAJIB diikuti urutannya)

```bash
cd /var/www/jagratama

# 1. Backup DB dulu — SELALU, sebelum migrate apalagi reseed
mysqldump -u root -p <nama_db_sesuai_.env> > /root/backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Tarik kode terbaru
git pull origin main

# 3. Install dependency PHP (kalau composer.lock berubah)
composer install --no-dev --optimize-autoloader

# 4. Migrasi schema
php artisan migrate --force

# 5. Seeder — URUTAN INI PENTING, jangan dibalik:
php artisan db:seed --class=DocumentTypeSeeder --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=WorkflowSeeder --force

# 6. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart PHP-FPM
systemctl restart php8.2-fpm
```

### Kenapa urutan seeder harus begitu?

`WorkflowSeeder` membaca **semua** baris `document_types` yang ada (`DocumentType::query()->get()`) untuk memutuskan workflow mana yang perlu dibuat. Kalau `DocumentTypeSeeder` belum dijalankan lebih dulu, `document_type` yang sudah basi/legacy (mis. kode lama sebelum konsolidasi ke `KAK`) masih ikut kebaca, dan `WorkflowSeeder` akan bikin set workflow duplikat untuk kode basi itu — user cuma lihat labelnya kembar di dropdown "Tipe Dokumen" karena nama workflow-nya sama persis.

`RoleSeeder` harus jalan sebelum `WorkflowSeeder` karena `WorkflowSeeder` butuh `role_id` yang valid untuk tiap `role code` yang dipakai di definisi alur.

## Verifikasi Setelah Redeploy

Jalankan lewat `php artisan tinker`:

**1. Pastikan `document_types` cuma ada yang aktif (saat ini: `KAK`, `SURAT`) — tidak ada kode legacy/basi:**
```php
App\Models\DocumentType::all()->each(fn($d)=>print($d->id.' | '.$d->code.' | '.$d->name.PHP_EOL));
```

**2. Cek tidak ada workflow duplikat untuk kombinasi org+tipe dokumen yang sama:**
```php
App\Models\Workflow::selectRaw('organization_type, document_type_id, count(*) as total')
    ->groupBy('organization_type','document_type_id')
    ->havingRaw('count(*) > 1')
    ->get()
    ->each(fn($r)=>print($r->organization_type.' | '.$r->document_type_id.' | total='.$r->total.PHP_EOL));
```
Kalau ada hasil yang muncul di sini, berarti masih ada duplikat — jangan lanjut publish ke user sebelum ini kosong.

**3. Cek urutan step & siapa yang wajib TTD untuk tiap kombinasi organisasi + tipe dokumen** (ganti `SURAT`/`KAK` dan kode organisasi sesuai yang mau dicek):
```php
$wf = App\Models\Workflow::with('steps.role', 'documentType')
    ->whereHas('documentType', fn($q) => $q->where('code', 'KAK'))
    ->where('organization_type', 'HMPS')
    ->first();

foreach ($wf->steps->sortBy('step_order') as $s) {
    echo $s->step_order.'. '.$s->role->code.' | wajib_ttd: '.($s->is_required_signature?'YA':'tidak').PHP_EOL;
}
```
Bandingkan hasilnya dengan tabel referensi di `docs/WORKFLOW_FLOW.md` / flowchart terbaru. Kalau ada role yang muncul dobel di `step_order` yang sama padahal seharusnya cuma 1 (atau role yang sama muncul di 2 `step_order` berbeda), itu tanda ada baris nyasar — lihat bagian Troubleshooting.

**4. Cek tidak ada dokumen "zombie" dari data testing sebelumnya yang ikut ke production** (biasanya kalau testing dilakukan langsung di server, bukan cuma lokal):
```php
App\Models\Document::get(['id','title','current_status'])
    ->each(fn($d)=>print($d->id.' | '.$d->title.' | '.$d->current_status->value.PHP_EOL));
```

## Troubleshooting — Masalah yang Pernah Terjadi

### 1. Step approval hilang dari alur (mis. Komisi B BLM tidak muncul)

**Gejala:** "Progress Approval" di halaman dokumen menunjukkan jumlah step lebih sedikit dari yang seharusnya, dan salah satu step yang harusnya ada (mis. Komisi B BLM) tidak muncul sama sekali.

**Penyebab:** `WorkflowSeeder` belum pernah dijalankan ulang di server sejak definisi alur di kode berubah — `php artisan migrate --force` **cuma mengubah struktur tabel**, tidak mengisi/memperbaiki data `workflow_steps`.

**Perbaikan:** jalankan `php artisan db:seed --class=WorkflowSeeder --force`.

### 2. Sistem minta slot tanda tangan yang tidak seharusnya ada (mis. `approver_3` padahal cuma ada 2 penanda tangan)

**Gejala:** error `DomainException` saat submit dokumen: *"Slot tanda tangan 'approver_N' harus punya tepat 1 key..."* padahal PDF template sudah benar dan sudah pernah jalan normal sebelumnya (mis. di lokal).

**Penyebab:** ini terjadi kalau ada step BARU yang disisipkan di TENGAH alur (mis. Komisi B BLM ditambahkan di posisi 2, menggeser semua step sesudahnya +1 posisi). Baris lama untuk role yang tergeser (mis. Direktur, dulunya di step 6, sekarang harusnya di step 7) **tidak otomatis hilang** — sehingga ada 2 baris untuk role yang sama (Direktur lama di step 6, Direktur baru di step 7), dan `requiredSignatureSteps()` menghitungnya sebagai 2 slot tanda tangan terpisah.

Bug ini **sudah diperbaiki** di `WorkflowSeeder` (commit terkait: perbaikan cleanup berbasis kombinasi `step_order`+`role_id`, bukan cuma cek "posisi di luar rentang"). Kalau sudah pull kode terbaru dan reseed, masalah ini seharusnya tidak terjadi lagi. Kalau masih terjadi:

```php
// Cek workflow_steps mana yang punya role duplikat lintas step_order
$wf = App\Models\Workflow::with('steps.role')
    ->whereHas('documentType', fn($q)=>$q->where('code','KAK'))
    ->where('organization_type','BLM')->first();
$wf->steps->groupBy('role_id')->filter(fn($g)=>$g->count()>1)
    ->each(fn($g)=>$g->each(fn($s)=>print($s->step_order.' - '.$s->role->code.PHP_EOL)));
```
Kalau ada role yang muncul di lebih dari 1 `step_order` dan baris yang salah **belum punya approval** (`$step->approvals()->count() === 0`), boleh dihapus manual, lalu jalankan ulang `WorkflowSeeder` untuk memastikan.

### 3. Dropdown "Tipe Dokumen" menampilkan nama yang sama 2x

**Gejala:** dropdown pilihan tipe dokumen (mis. saat pengaju bikin dokumen baru) menampilkan label yang identik persis, dobel.

**Penyebab:** ada baris `document_types` legacy yang tidak lagi dipakai di kode (mis. kode `LPJ` dari sebelum konsolidasi ke `KAK`) tapi belum pernah dibersihkan dari DB, karena `DocumentTypeSeeder` belum pernah dijalankan setelah kode-nya diubah. `WorkflowSeeder` ikut membuatkan workflow untuk document_type basi ini juga (karena dia baca SEMUA `document_types` yang ada), dan kebetulan nama workflow-nya sama dengan yang aktif — makanya kelihatan dobel di dropdown, padahal sebenarnya 2 workflow berbeda punya `document_type_id` berbeda.

**Perbaikan:**
1. **Cek dulu** apakah ada dokumen (`documents` table) yang masih pakai `document_type_id` basi itu — kalau ada dan itu data asli (bukan testing), JANGAN langsung dihapus, pikirkan cara migrasi datanya dulu ke document_type yang aktif.
2. Kalau cuma dokumen testing, hapus dulu dokumennya:
   ```php
   App\Models\Document::whereIn('id', [/* id-id dokumen testing */])->delete();
   ```
3. Baru jalankan `php artisan db:seed --class=DocumentTypeSeeder --force` — seeder ini otomatis menghapus `document_types` yang kodenya tidak ada lagi di daftar aktif (`KAK`, `SURAT`), beserta `workflows` & `workflow_steps` turunannya (asal tidak ada `documents` yang masih mereferensikannya — kalau masih ada, seeder akan gagal karena FK `restrictOnDelete`, itu sengaja sebagai pengaman).

## Catatan Keamanan

- **Jangan pernah** jalankan `migrate --force` atau seeder apa pun di production tanpa backup DB terlebih dahulu.
- Seeder (`RoleSeeder`, `WorkflowSeeder`, `DocumentTypeSeeder`) semuanya idempotent (aman dijalankan berkali-kali) dan dirancang untuk TIDAK menghapus data yang sudah punya relasi approval/dokumen asli (dilindungi FK `restrictOnDelete` + guard `whereDoesntHave('approvals')`) — tapi tetap **selalu verifikasi hasil** sebelum mengumumkan ke user, jangan asumsikan otomatis benar.
- Kalau ragu apakah suatu dokumen di production itu data asli atau sekadar testing, **tanya dulu** sebelum menghapus — jangan asumsikan.
