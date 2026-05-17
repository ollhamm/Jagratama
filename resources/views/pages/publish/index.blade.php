@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Publish Dokumen" />

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">{{ session('error') }}</div>
        @endif

        {{-- Pending Upload --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Menunggu Publish</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Dokumen berikut telah selesai disetujui. Pastikan dokumen sudah benar dan semua tanda tangan lengkap sebelum dipublish ke publik.</p>

            @forelse($pending as $doc)
                <div class="mb-4 rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/40 dark:bg-warning-500/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-400">Selesai: {{ optional($doc->completed_at)->format('d/m/Y H:i') ?? '-' }}</p>
                            <p class="text-xs text-warning-700 dark:text-warning-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3.5 w-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Download dokumen Word, tempelkan semua tanda tangan yang diperlukan, export ke PDF, lalu upload hasil finalnya di sini sebelum dipublish.
                            </p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('app.documents.show', $doc->id) }}" target="_blank"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                                Lihat Dokumen
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('app.publish.publish', $doc->id) }}"
                        class="mt-4 border-t border-warning-200 pt-4 dark:border-warning-700/40"
                        enctype="multipart/form-data"
                        x-data="{ confirmed: false }">
                        @csrf
                        <p class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">Upload PDF Final (dengan tanda tangan)</p>
                        <input type="file" name="attachment" accept="application/pdf,.pdf" required
                            class="mb-3 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300">

                        <label class="mb-3 flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" x-model="confirmed" class="rounded border-gray-300">
                            Saya sudah memastikan dokumen benar dan tanda tangan lengkap
                        </label>

                        <button type="submit"
                            :disabled="!confirmed"
                            :class="confirmed ? 'bg-brand-500 hover:bg-brand-600 cursor-pointer' : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700'"
                            class="rounded-lg px-5 py-2 text-sm font-medium text-white transition">
                            Publish ke Publik
                        </button>
                    </form>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada dokumen yang menunggu publish.</p>
                </div>
            @endforelse
        </div>

        {{-- History --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">History Publish</h3>

            @forelse($history as $doc)
                @php $publicUrl = route('public.document.show', $doc->id); @endphp
                <div
                    class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700 mb-2"
                    x-data="{ showQr: false, generated: false }"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-0.5 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-400">Published: {{ optional($doc->published_at)->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button type="button"
                                @click="showQr = !showQr; if (showQr && !generated) { generated = true; $nextTick(() => initQr($refs.qrbox, '{{ $publicUrl }}')); }"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800 sm:flex-none">
                                <span x-text="showQr ? 'Sembunyikan QR' : 'Tampilkan QR'">Tampilkan QR</span>
                            </button>
                            <a href="{{ $publicUrl }}" target="_blank"
                                class="flex-1 rounded-lg bg-brand-500 px-3 py-1.5 text-center text-xs font-medium text-white hover:bg-brand-600 sm:flex-none">
                                Lihat Publik
                            </a>
                        </div>
                    </div>

                    {{-- QR Panel --}}
                    <div x-show="showQr" x-transition class="mt-4 flex flex-col items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <div x-ref="qrbox" class="rounded-xl bg-white p-3 shadow-sm"></div>
                        <p class="text-[11px] text-gray-400 break-all text-center">{{ $publicUrl }}</p>
                        <button type="button"
                            @click="downloadQr($refs.qrbox, '{{ $doc->id }}')"
                            class="rounded-lg border border-gray-300 px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                            Download QR
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada dokumen yang dipublish.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    function initQr(el, url) {
        el.innerHTML = '';
        new QRCode(el, {
            text: url,
            width: 160,
            height: 160,
            correctLevel: QRCode.CorrectLevel.M,
        });
    }

    function downloadQr(el, docId) {
        const canvas = el.querySelector('canvas');
        if (!canvas) return;
        const a = document.createElement('a');
        a.download = 'qr-' + docId + '.png';
        a.href = canvas.toDataURL('image/png');
        a.click();
    }
</script>
@endpush
