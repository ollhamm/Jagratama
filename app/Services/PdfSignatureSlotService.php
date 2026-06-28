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
    private const KEY_JABATAN = 'jabatan_pengirim';
    private const KEY_TTD = 'ttd_pengirim';
    private const KEY_NAMA = 'nama_pengirim';

    /**
     * Cari slot tanda tangan (key ${jabatan_pengirim}, ${ttd_pengirim}, ${nama_pengirim})
     * di halaman TERAKHIR PDF, validasi jumlahnya sesuai $expectedCount, lalu urutkan
     * secara baca natural (kiri-atas ke kanan-bawah).
     *
     * @throws DomainException jika jumlah key tidak sesuai $expectedCount
     */
    public function extractSlots(string $absolutePath, int $expectedCount): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $pages = $pdf->getPages();

        if (empty($pages)) {
            throw new DomainException('PDF tidak memiliki halaman.');
        }

        $lastPageIndex = count($pages) - 1;
        $lastPage = $pages[$lastPageIndex];

        $jabatanMatches = $this->findKeyOccurrences($lastPage, self::KEY_JABATAN);
        $ttdMatches = $this->findKeyOccurrences($lastPage, self::KEY_TTD);
        $namaMatches = $this->findKeyOccurrences($lastPage, self::KEY_NAMA);

        if (count($jabatanMatches) !== $expectedCount
            || count($ttdMatches) !== $expectedCount
            || count($namaMatches) !== $expectedCount) {
            throw new DomainException(sprintf(
                'Dokumen harus memiliki %d slot tanda tangan (key ${%s}, ${%s}, ${%s}) di halaman terakhir. Ditemukan: jabatan=%d, ttd=%d, nama=%d.',
                $expectedCount,
                self::KEY_JABATAN,
                self::KEY_TTD,
                self::KEY_NAMA,
                count($jabatanMatches),
                count($ttdMatches),
                count($namaMatches)
            ));
        }

        $jabatanSorted = $this->sortByReadingOrder($jabatanMatches);
        $ttdSorted = $this->sortByReadingOrder($ttdMatches);
        $namaSorted = $this->sortByReadingOrder($namaMatches);

        $slots = [];
        for ($i = 0; $i < $expectedCount; $i++) {
            $slots[] = [
                'jabatan' => $jabatanSorted[$i],
                'ttd' => $ttdSorted[$i],
                'nama' => $namaSorted[$i],
            ];
        }

        $details = $lastPage->getDetails();
        $mediaBox = $details['MediaBox'] ?? [0, 0, 595.28, 841.89];

        return [
            'page_index' => $lastPageIndex,
            'page_width' => (float) $mediaBox[2],
            'page_height' => (float) $mediaBox[3],
            'slots' => $slots,
        ];
    }

    /**
     * Tempel QR code (link verifikasi) + teks jabatan & nama ke 1 slot tertentu
     * di halaman terakhir PDF. Menimpa file di $absolutePath dengan versi baru
     * (halaman lain & slot lain dibiarkan apa adanya, termasuk hasil tempel sebelumnya).
     */
    public function embedSlot(
        string $absolutePath,
        array $pageInfo,
        array $slot,
        string $jabatanText,
        string $namaText,
        string $verificationUrl
    ): void {
        $pdf = new Fpdi('P', 'pt');
        $pageCount = $pdf->setSourceFile($absolutePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($i - 1 === $pageInfo['page_index']) {
                $this->drawSlotOverlay($pdf, $pageInfo, $slot, $jabatanText, $namaText, $verificationUrl);
            }
        }

        $tmpOutput = $absolutePath.'.tmp';
        $pdf->Output('F', $tmpOutput);
        rename($tmpOutput, $absolutePath);
    }

    private function drawSlotOverlay(
        Fpdi $pdf,
        array $pageInfo,
        array $slot,
        string $jabatanText,
        string $namaText,
        string $verificationUrl
    ): void {
        $pageHeight = $pageInfo['page_height'];
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

        // TTD — tempel gambar QR code (bukan tanda tangan asli)
        $ttdX = (float) $slot['ttd']['x'];
        $ttdY = $toFpdfY((float) $slot['ttd']['y']);
        $qrSize = 50.0;
        $pdf->Rect($ttdX - 1, $ttdY - $qrSize + 2, $qrSize + 4, $qrSize + 4, 'F');

        $qrPngPath = $this->generateQrPng($verificationUrl);
        $pdf->Image($qrPngPath, $ttdX, $ttdY - $qrSize + 4, $qrSize, $qrSize, 'PNG');
        @unlink($qrPngPath);
    }

    private function generateQrPng(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 4,
        );

        $result = (new PngWriter())->write($qrCode);

        $tmpPath = sys_get_temp_dir().'/qr_'.uniqid('', true).'.png';
        $result->saveToFile($tmpPath);

        return $tmpPath;
    }

    private function findKeyOccurrences(Page $page, string $key): array
    {
        $needle = '${'.$key.'}';
        $dataTm = $page->getDataTm();
        $matches = [];

        foreach ($dataTm as $item) {
            $tm = $item[0];
            $text = $item[1];
            if (str_contains($text, $needle)) {
                $matches[] = ['x' => (float) $tm[4], 'y' => (float) $tm[5]];
            }
        }

        return $matches;
    }

    /**
     * Urutkan koordinat secara baca natural: baris atas dulu (y besar -> kecil,
     * origin PDF di kiri-bawah), dalam baris yang sama urut kiri ke kanan.
     * Baris dikelompokkan dengan toleransi 10pt.
     */
    private function sortByReadingOrder(array $points): array
    {
        usort($points, function (array $a, array $b) {
            $yDiff = $b['y'] - $a['y'];
            if (abs($yDiff) > 10) {
                return $yDiff > 0 ? 1 : -1;
            }

            return $a['x'] <=> $b['x'];
        });

        return $points;
    }
}
