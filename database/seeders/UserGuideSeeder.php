<?php

namespace Database\Seeders;

use App\Models\UserGuide;
use Illuminate\Database\Seeder;

class UserGuideSeeder extends Seeder
{
    public function run(): void
    {
        $guides = [
            [
                'key'   => 'pengaju',
                'title' => 'Panduan Pengguna — Pengaju',
                'content' => '<h3>Cara Mengajukan Dokumen</h3>'
                    . '<p>Selamat datang! Berikut langkah-langkah dasar sebagai Pengaju:</p>'
                    . '<ol>'
                    . '<li>Buka menu <strong>Pengajuan</strong>, klik <strong>+ Buat Pengajuan</strong>.</li>'
                    . '<li>Pilih kategori dokumen (KAK/LPJ atau Persuratan) dan tipe dokumennya.</li>'
                    . '<li>Upload lampiran PDF (maksimal 5MB).</li>'
                    . '<li>Bubuhkan tanda tangan, lalu klik <strong>Submit</strong>.</li>'
                    . '<li>Pantau status dokumen di halaman detail — kalau ditolak, perbaiki dan submit ulang.</li>'
                    . '</ol>'
                    . '<p>Konten ini dapat diedit oleh Admin melalui menu Kelola Panduan.</p>',
            ],
            [
                'key'   => 'approval',
                'title' => 'Panduan Pengguna — Approval',
                'content' => '<h3>Cara Melakukan Approval</h3>'
                    . '<p>Selamat datang! Berikut langkah-langkah dasar sebagai Approver:</p>'
                    . '<ol>'
                    . '<li>Buka menu <strong>Approval</strong> untuk melihat dokumen yang menunggu persetujuan Anda.</li>'
                    . '<li>Periksa isi dokumen pada pratinjau PDF di sisi kiri.</li>'
                    . '<li>Klik <strong>Approve</strong> jika dokumen sudah sesuai (isi tanda tangan jika diwajibkan), atau <strong>Reject</strong> beserta catatan alasan jika perlu revisi.</li>'
                    . '<li>Riwayat seluruh approval dapat dilihat di bagian "Riwayat Approval" pada halaman yang sama.</li>'
                    . '</ol>'
                    . '<p>Konten ini dapat diedit oleh Admin melalui menu Kelola Panduan.</p>',
            ],
            [
                'key'   => 'admin',
                'title' => 'Panduan Pengguna — Admin',
                'content' => '<h3>Panduan Administrator</h3>'
                    . '<p>Sebagai Admin, Anda memiliki akses penuh ke seluruh fitur sistem, termasuk:</p>'
                    . '<ul>'
                    . '<li><strong>User Management</strong> — kelola akun, role, dan organisasi pengguna.</li>'
                    . '<li><strong>Kelola Panduan</strong> — edit konten panduan untuk ketiga role (halaman ini).</li>'
                    . '<li>Membuat pengajuan atas nama pengguna lain (on behalf of).</li>'
                    . '<li>Melakukan approval di step manapun tanpa batasan role.</li>'
                    . '</ul>'
                    . '<p>Konten ini dapat diedit langsung melalui tab ini.</p>',
            ],
        ];

        foreach ($guides as $guide) {
            UserGuide::query()->firstOrCreate(
                ['key' => $guide['key']],
                ['title' => $guide['title'], 'content' => $guide['content']]
            );
        }
    }
}
