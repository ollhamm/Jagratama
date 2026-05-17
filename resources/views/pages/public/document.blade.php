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

        {{-- Tanda tangan --}}
        @if($approvalSignatures->isNotEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold text-gray-700">Tanda Tangan Persetujuan</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                @foreach($approvalSignatures as $item)
                    <div class="rounded-xl border border-gray-200 p-3 text-center">
                        <img src="{{ $item['signature_value'] }}"
                            class="mx-auto mb-2 max-h-20 rounded border border-gray-100 bg-white"
                            alt="Tanda Tangan">
                        <p class="text-xs font-semibold text-gray-700">{{ $item['role_name'] }}</p>
                        <p class="text-[11px] text-gray-500">{{ $item['approver_name'] }}</p>
                        <p class="text-[10px] text-gray-400">{{ $item['signed_at'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

    </main>

    <footer class="mt-10 border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} Jagratama — Dokumen ini dapat diverifikasi keasliannya melalui sistem kami.
    </footer>

</body>
</html>
