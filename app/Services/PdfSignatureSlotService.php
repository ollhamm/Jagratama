<?php

namespace App\Services;

use DomainException;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

class PdfSignatureSlotService
{
    private const KEY_JABATAN = 'jabatan';
    private const KEY_TTD = 'ttd';
    private const KEY_NAMA = 'nama';

    // Identifier slot Pengaju — dipakai di key ${jabatan_pengaju}, ${ttd_pengaju}, ${nama_pengaju}.
    public const SLOT_PENGAJU = 'pengaju';

    /**
     * Identifier slot approver ke-$n (1-based, urutan step_order yang wajib TTD) —
     * dipakai di key ${jabatan_approver_1}, ${ttd_approver_1}, dst. Angkanya adalah
     * posisi ke berapa di antara step yang wajib TTD, BUKAN step_order workflow asli,
     * supaya key di templat PDF tidak perlu tahu step_order internal.
     */
    public static function approverSlotId(int $position): string
    {
        return 'approver_'.$position;
    }

    // Role yang QR-nya ditempel logo Kemenkes di tengah — konsisten dengan tampilan
    // QR di halaman web (lihat LOGO_QR_ROLES di show.blade.php & public/document.blade.php).
    private const LOGO_QR_ROLES = ['KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM', 'DIREKTUR'];

    /**
     * Cari slot tanda tangan berdasarkan IDENTITAS EKSPLISIT di key-nya (mis.
     * ${jabatan_pengaju}/${ttd_pengaju}/${nama_pengaju} untuk SLOT_PENGAJU,
     * ${jabatan_approver_1}/${ttd_approver_1}/${nama_approver_1} untuk approverSlotId(1),
     * dst — lihat $slotIdentifiers). Dicari di SELURUH HALAMAN PDF, jadi slot boleh
     * tersebar bebas di halaman manapun (mis. pengaju di halaman 2, approver di halaman 5),
     * karena pemetaan ke penandatangan ditentukan oleh identitas key, bukan urutan fisik
     * penempatan di dokumen.
     *
     * @param  string[]  $slotIdentifiers  daftar identifier, urutan array menentukan urutan
     *                                     hasil di 'slots' (biasanya: [SLOT_PENGAJU, approverSlotId(1), approverSlotId(2), ...])
     * @throws DomainException jika salah satu identifier tidak ditemukan tepat 1 kali
     *                         untuk ketiga key (jabatan/ttd/nama), atau ketiganya tidak
     *                         berada di halaman yang sama
     */
    public function extractSlots(string $absolutePath, array $slotIdentifiers): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $pages = $pdf->getPages();

        if (empty($pages)) {
            throw new DomainException('PDF tidak memiliki halaman.');
        }

        $slots = [];

        foreach ($slotIdentifiers as $identifier) {
            $jabatanMatches = [];
            $ttdMatches = [];
            $namaMatches = [];

            foreach ($pages as $pageIndex => $page) {
                $jabatanMatches = array_merge($jabatanMatches, $this->findKeyOccurrences($page, self::KEY_JABATAN.'_'.$identifier, $pageIndex));
                $ttdMatches = array_merge($ttdMatches, $this->findKeyOccurrences($page, self::KEY_TTD.'_'.$identifier, $pageIndex));
                $namaMatches = array_merge($namaMatches, $this->findKeyOccurrences($page, self::KEY_NAMA.'_'.$identifier, $pageIndex));
            }

            if (count($jabatanMatches) !== 1 || count($ttdMatches) !== 1 || count($namaMatches) !== 1) {
                throw new DomainException(sprintf(
                    'Slot tanda tangan "%s" harus punya tepat 1 key ${%s_%s}, ${%s_%s}, ${%s_%s} di dokumen. Ditemukan: jabatan=%d, ttd=%d, nama=%d.',
                    $identifier,
                    self::KEY_JABATAN, $identifier,
                    self::KEY_TTD, $identifier,
                    self::KEY_NAMA, $identifier,
                    count($jabatanMatches),
                    count($ttdMatches),
                    count($namaMatches)
                ));
            }

            if ($jabatanMatches[0]['page'] !== $ttdMatches[0]['page']
                || $jabatanMatches[0]['page'] !== $namaMatches[0]['page']) {
                throw new DomainException(sprintf(
                    'Slot tanda tangan "%s" tidak lengkap: key jabatan/ttd/nama-nya harus berada di halaman yang sama.',
                    $identifier
                ));
            }

            $pageIndex = $jabatanMatches[0]['page'];
            $mediaBox = $pages[$pageIndex]->getDetails()['MediaBox'] ?? [0, 0, 595.28, 841.89];

            $slots[$identifier] = [
                'page_index' => $pageIndex,
                'page_width' => (float) $mediaBox[2],
                'page_height' => (float) $mediaBox[3],
                'jabatan' => ['x' => $jabatanMatches[0]['x'], 'y' => $jabatanMatches[0]['y']],
                'ttd' => ['x' => $ttdMatches[0]['x'], 'y' => $ttdMatches[0]['y']],
                'nama' => ['x' => $namaMatches[0]['x'], 'y' => $namaMatches[0]['y']],
            ];
        }

        return [
            'slots' => $slots,
        ];
    }

    /**
     * Tempel QR code (link verifikasi) + teks jabatan & nama ke 1 slot tertentu, di
     * halaman manapun slot itu berada (slot menyimpan page_index-nya sendiri, karena
     * slot lain bisa berada di halaman berbeda). Menimpa file di $absolutePath dengan
     * versi baru (halaman lain & slot lain dibiarkan apa adanya, termasuk hasil tempel
     * sebelumnya).
     */
    public function embedSlot(
        string $absolutePath,
        array $slot,
        string $jabatanText,
        string $namaText,
        string $verificationUrl,
        ?string $roleCode = null
    ): void {
        $pdf = new Fpdi('P', 'pt');
        $pageCount = $pdf->setSourceFile($absolutePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($i - 1 === $slot['page_index']) {
                $this->drawSlotOverlay($pdf, $slot, $jabatanText, $namaText, $verificationUrl, $roleCode);
            }
        }

        $tmpOutput = $absolutePath.'.tmp';
        $pdf->Output('F', $tmpOutput);
        rename($tmpOutput, $absolutePath);
    }

    private function drawSlotOverlay(
        Fpdi $pdf,
        array $slot,
        string $jabatanText,
        string $namaText,
        string $verificationUrl,
        ?string $roleCode = null
    ): void {
        $pageHeight = $slot['page_height'];
        $toFpdfY = fn (float $pdfY): float => $pageHeight - $pdfY;

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', 10);

        // Jabatan — tutup placeholder lama, tulis ulang teks asli
        $jabatanX = (float) $slot['jabatan']['x'];
        $jabatanY = $toFpdfY((float) $slot['jabatan']['y']);
        $pdf->Rect($jabatanX - 1, $jabatanY - 10, 220, 14, 'F');
        $pdf->SetXY($jabatanX, $jabatanY - 9);
        $pdf->Cell(220, 12, $jabatanText, 0, 0, 'L');

        // Nama — sama
        $namaX = (float) $slot['nama']['x'];
        $namaY = $toFpdfY((float) $slot['nama']['y']);
        $pdf->Rect($namaX - 1, $namaY - 10, 220, 14, 'F');
        $pdf->SetXY($namaX, $namaY - 9);
        $pdf->Cell(220, 12, $namaText, 0, 0, 'L');

        // TTD — tutup dulu placeholder teks aslinya (lebar generik 220pt, sama seperti
        // jabatan/nama) supaya tidak ada sisa teks ${ttd_...} yang bocor di luar QR kalau
        // placeholder-nya lebih lebar dari ukuran QR. Baru tempel gambar QR di atasnya
        // (bukan tanda tangan asli).
        $ttdX = (float) $slot['ttd']['x'];
        $ttdY = $toFpdfY((float) $slot['ttd']['y']);
        $pdf->Rect($ttdX - 1, $ttdY - 10, 220, 14, 'F');

        $qrSize = 50.0;
        $pdf->Rect($ttdX - 1, $ttdY - $qrSize + 2, $qrSize + 4, $qrSize + 4, 'F');

        $needsLogo = in_array($roleCode, self::LOGO_QR_ROLES, true);
        $qrPngPath = $this->generateQrPng($verificationUrl, $needsLogo);
        $pdf->Image($qrPngPath, $ttdX, $ttdY - $qrSize + 4, $qrSize, $qrSize, 'PNG');
        @unlink($qrPngPath);
    }

    private function generateQrPng(string $url, bool $withLogo = false): string
    {
        $qrCode = new QrCode(
            data: $url,
            // Error correction H (30%) untuk QR berlogo, supaya tetap bisa di-scan
            // walau sebagian modul tertutup logo — konsisten dengan versi client-side.
            errorCorrectionLevel: $withLogo ? ErrorCorrectionLevel::High : ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 4,
        );

        $result = (new PngWriter())->write($qrCode);

        $tmpPath = sys_get_temp_dir().'/qr_'.uniqid('', true).'.png';
        $result->saveToFile($tmpPath);

        if ($withLogo) {
            $this->overlayLogoOnPng($tmpPath);
        }

        return $tmpPath;
    }

    /**
     * Tempel logo Kemenkes di tengah QR (kotak putih sebagai padding di belakang logo
     * supaya tidak menyatu dengan modul QR) — pakai GD, sama persis konsepnya dengan
     * overlayLogoOnCanvas() di JS pada show.blade.php & public/document.blade.php.
     */
    private function overlayLogoOnPng(string $qrPngPath): void
    {
        $logoPath = public_path('images/logo/kemenkes-logo.png');
        if (! file_exists($logoPath) || ! function_exists('imagecreatefrompng')) {
            return;
        }

        $qrImage = imagecreatefrompng($qrPngPath);
        $logo = $this->loadImageAnyFormat($logoPath);
        if (! $qrImage || ! $logo) {
            return;
        }

        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoSize = (int) round($qrWidth * 0.20);
        $x = (int) round(($qrWidth - $logoSize) / 2);
        $y = (int) round(($qrHeight - $logoSize) / 2);
        $padding = 5;

        $white = imagecolorallocate($qrImage, 255, 255, 255);
        imagefilledrectangle($qrImage, $x - $padding, $y - $padding, $x + $logoSize + $padding, $y + $logoSize + $padding, $white);

        imagecopyresampled($qrImage, $logo, $x, $y, 0, 0, $logoSize, $logoSize, imagesx($logo), imagesy($logo));

        imagepng($qrImage, $qrPngPath);
        imagedestroy($qrImage);
        imagedestroy($logo);
    }

    /**
     * Muat gambar tanpa peduli ekstensi file — deteksi format asli dari konten
     * (mis. kemenkes-logo.png yang ternyata berisi data JPEG, bukan PNG sungguhan).
     */
    private function loadImageAnyFormat(string $path): \GdImage|false
    {
        $info = @getimagesize($path);
        $type = $info[2] ?? null;

        return match ($type) {
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_GIF  => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => false,
        };
    }

    private function findKeyOccurrences(Page $page, string $key, int $pageIndex): array
    {
        $needle = '${'.$key.'}';
        $dataTm = $page->getDataTm();
        $matches = [];

        foreach ($dataTm as $item) {
            $tm = $item[0];
            $text = $item[1];
            if (str_contains($text, $needle)) {
                $matches[] = ['page' => $pageIndex, 'x' => (float) $tm[4], 'y' => (float) $tm[5]];
            }
        }

        return $matches;
    }
}
