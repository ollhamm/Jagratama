<?php

use App\Services\PdfSignatureSlotService;
use Dompdf\Dompdf;
use Smalot\PdfParser\Parser;

/**
 * @param  array<string, int>  $slotPages  identifier => page number (1-based) to place it on
 */
function buildSignaturePdf(array $slotPages, int $pageCount): string
{
    $path = sys_get_temp_dir().'/sig_'.uniqid('', true).'.pdf';

    $html = '<html><body>';
    for ($page = 1; $page <= $pageCount; $page++) {
        $html .= '<div style="page-break-after: always;">';
        $html .= "<p>Halaman {$page}</p>";

        foreach ($slotPages as $identifier => $slotPage) {
            if ($slotPage !== $page) {
                continue;
            }

            $html .= "<p>\${jabatan_{$identifier}}</p><p>\${ttd_{$identifier}}</p><p>\${nama_{$identifier}}</p>";
        }

        $html .= '</div>';
    }
    $html .= '</body></html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    file_put_contents($path, $dompdf->output());

    return $path;
}

it('finds signature slots by explicit key identity spread across different pages', function () {
    $path = buildSignaturePdf([
        PdfSignatureSlotService::SLOT_PENGAJU => 2,
        PdfSignatureSlotService::approverSlotId(1) => 5,
    ], 5);

    $service = new PdfSignatureSlotService();
    $result = $service->extractSlots($path, [
        PdfSignatureSlotService::SLOT_PENGAJU,
        PdfSignatureSlotService::approverSlotId(1),
    ]);

    expect($result['slots'])->toHaveKeys([
        PdfSignatureSlotService::SLOT_PENGAJU,
        PdfSignatureSlotService::approverSlotId(1),
    ]);
    expect($result['slots'][PdfSignatureSlotService::SLOT_PENGAJU]['page_index'])->toBe(1); // page 2
    expect($result['slots'][PdfSignatureSlotService::approverSlotId(1)]['page_index'])->toBe(4); // page 5

    @unlink($path);
});

it('is not affected by the physical order slots are placed in the document', function () {
    // approver_1 physically appears BEFORE pengaju in the file — identity, not
    // position, decides who owns which slot.
    $path = buildSignaturePdf([
        PdfSignatureSlotService::approverSlotId(1) => 1,
        PdfSignatureSlotService::SLOT_PENGAJU => 3,
    ], 3);

    $service = new PdfSignatureSlotService();
    $result = $service->extractSlots($path, [
        PdfSignatureSlotService::SLOT_PENGAJU,
        PdfSignatureSlotService::approverSlotId(1),
    ]);

    expect($result['slots'][PdfSignatureSlotService::SLOT_PENGAJU]['page_index'])->toBe(2); // page 3
    expect($result['slots'][PdfSignatureSlotService::approverSlotId(1)]['page_index'])->toBe(0); // page 1

    @unlink($path);
});

it('embeds a QR only onto the page its own slot belongs to', function () {
    $path = buildSignaturePdf([
        PdfSignatureSlotService::SLOT_PENGAJU => 2,
        PdfSignatureSlotService::approverSlotId(1) => 5,
    ], 5);

    $service = new PdfSignatureSlotService();
    $extracted = $service->extractSlots($path, [
        PdfSignatureSlotService::SLOT_PENGAJU,
        PdfSignatureSlotService::approverSlotId(1),
    ]);

    $service->embedSlot(
        $path,
        $extracted['slots'][PdfSignatureSlotService::approverSlotId(1)],
        'Direktur',
        'Budi Santoso',
        'https://example.test/verify/abc'
    );

    $parser = new Parser();
    $pdf = $parser->parseFile($path);
    $pages = $pdf->getPages();

    // Page 2 (index 1, Pengaju's slot) must be untouched.
    expect($pages[1]->getText())->not->toContain('Budi Santoso');

    // Page 5 (index 4, approver_1's slot) must have the new name drawn onto it.
    expect($pages[4]->getText())->toContain('Budi Santoso');

    @unlink($path);
});

it('throws when a required slot identifier is missing from the document', function () {
    $path = buildSignaturePdf([
        PdfSignatureSlotService::SLOT_PENGAJU => 1,
    ], 1);

    $service = new PdfSignatureSlotService();

    expect(fn () => $service->extractSlots($path, [
        PdfSignatureSlotService::SLOT_PENGAJU,
        PdfSignatureSlotService::approverSlotId(1),
    ]))->toThrow(DomainException::class);

    @unlink($path);
});

it('throws when jabatan/ttd/nama of the same slot are split across pages', function () {
    $html = '<html><body>'
        .'<div style="page-break-after: always;"><p>${jabatan_pengaju}</p><p>${ttd_pengaju}</p></div>'
        .'<div><p>${nama_pengaju}</p></div>'
        .'</body></html>';

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $path = sys_get_temp_dir().'/sig_'.uniqid('', true).'.pdf';
    file_put_contents($path, $dompdf->output());

    $service = new PdfSignatureSlotService();

    expect(fn () => $service->extractSlots($path, [PdfSignatureSlotService::SLOT_PENGAJU]))
        ->toThrow(DomainException::class);

    @unlink($path);
});
