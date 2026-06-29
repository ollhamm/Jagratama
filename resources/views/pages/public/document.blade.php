<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title }} — Jagratama</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Header publik --}}
    <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="mx-auto max-w-5xl flex items-center gap-3">
            <img src="/images/logo/jagratama-logo.png" alt="Jagratama" class="h-10">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Dokumen Publik</p>
                <p class="text-sm font-semibold text-gray-700">/jagratama/{{ $document->id }}</p>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 space-y-6">

        {{-- Info dokumen --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h1 class="text-xl font-bold text-gray-800 mb-4">{{ $document->title }}</h1>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <p class="text-xs uppercase text-gray-400">Tipe Dokumen</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ $document->documentType->name ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <p class="text-xs uppercase text-gray-400">Organisasi</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ $document->organization->name ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <p class="text-xs uppercase text-gray-400">Dipublish</p>
                    <p class="mt-1 text-sm font-medium text-gray-800">{{ optional($document->published_at)->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Preview PDF --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-4">
            <iframe
                src="{{ route('public.document.pdf', $document->id) }}"
                class="w-full rounded-xl border border-gray-200"
                style="height: 75vh;"
                title="Dokumen Final">
            </iframe>
        </div>
    </main>

    <footer class="mt-10 border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} Jagratama — Dokumen ini dapat diverifikasi keasliannya melalui sistem kami.
    </footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    var LOGO_ROLES    = ['KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM', 'DIREKTUR'];
    var KEMENKES_LOGO = '{{ asset('images/logo/kemenkes-logo.png') }}';

    function overlayLogoOnCanvas(canvas, logoUrl) {
        var img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function () {
            var ctx  = canvas.getContext('2d');
            var size = Math.floor(canvas.width * 0.20);
            var x    = Math.floor((canvas.width  - size) / 2);
            var y    = Math.floor((canvas.height - size) / 2);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(x - 5, y - 5, size + 10, size + 10);
            ctx.drawImage(img, x, y, size, size);
            // sync img tag yang dibuat QRCode.js
            var qrImg = canvas.parentElement && canvas.parentElement.querySelector('img');
            if (qrImg) qrImg.src = canvas.toDataURL('image/png');
        };
        img.src = logoUrl;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pub-sig-qr').forEach(function (el) {
            var url      = el.dataset.url;
            var role     = el.dataset.role || '';
            if (!url) return;
            var needsLogo = LOGO_ROLES.indexOf(role) !== -1;

            new QRCode(el, {
                text: url,
                width: 200,
                height: 200,
                colorDark: '#101828',
                colorLight: '#ffffff',
                correctLevel: needsLogo ? QRCode.CorrectLevel.H : QRCode.CorrectLevel.M,
            });

            // Kecilkan tampilan tapi biarkan canvas 200px untuk kualitas logo
            var canvas = el.querySelector('canvas');
            var qrImg  = el.querySelector('img');
            if (canvas) { canvas.style.width = '80px'; canvas.style.height = '80px'; }
            if (qrImg)  { qrImg.style.width  = '80px'; qrImg.style.height  = '80px'; }

            if (needsLogo && canvas) {
                // setTimeout memastikan QRCode.js selesai render sebelum overlay
                setTimeout(function () { overlayLogoOnCanvas(canvas, KEMENKES_LOGO); }, 0);
            }
        });
    });
</script>
</body>
</html>
