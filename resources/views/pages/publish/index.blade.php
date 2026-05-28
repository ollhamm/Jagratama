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

        {{-- ══════════════════════════════════════════════════════════
             TAMPILAN KOMISI B — Antrian Review
        ══════════════════════════════════════════════════════════ --}}
        @if($isKomisiB)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Menunggu Persetujuan</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Dokumen berikut diajukan oleh Pengaju untuk dipublish ke publik. Periksa dokumen dengan seksama sebelum memberikan keputusan.</p>

                @forelse($reviewQueue as $doc)
                    <div class="mb-5 rounded-xl border border-brand-200 bg-brand-50/30 dark:border-brand-700/40 dark:bg-brand-500/5" x-data="{ showReject: false, showApprove: false }">
                        {{-- Header dokumen --}}
                        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1 min-w-0">
                                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Pengaju: <span class="font-medium text-gray-600 dark:text-gray-300">{{ $doc->creator->name ?? '-' }}</span>
                                </p>
                                <p class="text-xs text-gray-400">
                                    Selesai approval: {{ optional($doc->completed_at)->format('d/m/Y H:i') ?? '-' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="{{ route('app.documents.show', $doc->id) }}" target="_blank"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Lihat Dokumen
                                </a>
                                <a href="{{ route('app.publish.preview-pdf', $doc->id) }}" target="_blank"
                                    class="rounded-lg border border-brand-300 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-50 dark:border-brand-600 dark:text-brand-400">
                                    Preview PDF Final
                                </a>
                            </div>
                        </div>

                        {{-- Ringkasan tanda tangan approval --}}
                        @if($doc->approvals->isNotEmpty())
                            <div class="border-t border-brand-100 px-4 py-3 dark:border-brand-700/30">
                                <p class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-400">Tanda Tangan Approval</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($doc->approvals->where('status', 'APPROVED') as $approval)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-[11px] font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            {{ $approval->workflowStep->role->name ?? '-' }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tombol aksi --}}
                        <div class="flex flex-wrap items-center gap-3 border-t border-brand-100 px-4 py-3 dark:border-brand-700/30">
                            {{-- Approve --}}
                            <button type="button" @click="showApprove = true"
                                class="rounded-lg bg-success-500 px-5 py-2 text-sm font-medium text-white hover:bg-success-600">
                                Setujui & Publikasikan
                            </button>

                            {{-- Toggle form reject --}}
                            <button type="button" @click="showReject = !showReject"
                                class="rounded-lg border border-error-300 px-5 py-2 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-error-700 dark:text-error-400 dark:hover:bg-error-500/10">
                                Tolak
                            </button>
                        </div>

                        {{-- Form reject (inline) --}}
                        <div x-show="showReject" x-transition class="border-t border-error-100 bg-error-50/50 px-4 py-3 dark:border-error-700/30 dark:bg-error-500/5">
                            <form method="POST" action="{{ route('app.publish.reject', $doc->id) }}">
                                @csrf
                                <p class="mb-2 text-xs font-semibold text-error-700 dark:text-error-400">Alasan penolakan <span class="text-red-500">*</span></p>
                                <textarea name="notes" rows="3" required placeholder="Tuliskan alasan penolakan yang jelas untuk Pengaju..."
                                    class="mb-3 w-full rounded-lg border border-error-200 bg-white px-3 py-2 text-sm text-gray-800 outline-hidden focus:border-error-400 dark:border-error-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="rounded-lg bg-error-500 px-4 py-1.5 text-sm font-medium text-white hover:bg-error-600">
                                        Kirim Penolakan
                                    </button>
                                    <button type="button" @click="showReject = false"
                                        class="rounded-lg border border-gray-300 px-4 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Modal konfirmasi Setujui --}}
                        <div x-show="showApprove" x-transition.opacity
                            class="fixed flex items-center justify-center bg-gray-900/40 p-4"
                            style="inset:0; z-index:9999999; position:fixed;">
                            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900"
                                @click.outside="showApprove = false">
                                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Konfirmasi Publikasi</h3>
                                    <button type="button" @click="showApprove = false"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="px-6 py-5">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        Dokumen <span class="font-semibold text-gray-800 dark:text-white/90">"{{ $doc->title }}"</span> akan dipublikasikan dan dapat diakses oleh publik.
                                    </p>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pastikan dokumen sudah diperiksa dengan seksama sebelum menyetujui.</p>
                                </div>
                                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                                    <button type="button" @click="showApprove = false"
                                        class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                        Batal
                                    </button>
                                    <form method="POST" action="{{ route('app.publish.approve', $doc->id) }}">
                                        @csrf
                                        <button type="submit"
                                            class="rounded-lg bg-success-500 px-5 py-2 text-sm font-medium text-white hover:bg-success-600">
                                            Ya, Publikasikan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada dokumen yang menunggu persetujuan.</p>
                    </div>
                @endforelse
            </div>

            {{-- Riwayat Review Komisi B --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Riwayat Review</h3>
                @forelse($reviewHistory as $doc)
                    @php $publicUrl = route('public.document.show', $doc->id); @endphp
                    <div class="mb-2 rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700"
                        x-data="{ showQr: false, generated: false }">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-500">{{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}</p>
                                <p class="text-xs text-gray-400">Pengaju: {{ $doc->creator->name ?? '-' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                @if($doc->published_at)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Disetujui {{ optional($doc->published_at)->format('d/m/Y') }}
                                    </span>
                                    <button type="button"
                                        @click="showQr = !showQr; if (showQr && !generated) { generated = true; $nextTick(() => initQr($refs.qrbox, '{{ $publicUrl }}')); }"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                        <span x-text="showQr ? 'Sembunyikan QR' : 'Tampilkan QR'">Tampilkan QR</span>
                                    </button>
                                    <a href="{{ $publicUrl }}" target="_blank"
                                        class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600">
                                        Lihat Publik
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-700 dark:bg-error-500/10 dark:text-error-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Ditolak
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($doc->published_at)
                            <div x-show="showQr" x-transition class="mt-4 flex flex-col items-center gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                                <div x-ref="qrbox" class="rounded-xl bg-white p-3 shadow-sm"></div>
                                <p class="text-[11px] text-gray-400 break-all text-center">{{ $publicUrl }}</p>
                                <button type="button"
                                    @click="downloadQr($refs.qrbox, '{{ $doc->id }}')"
                                    class="rounded-lg border border-gray-300 px-4 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                                    Download QR
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat review.</p>
                @endforelse
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             TAMPILAN PENGAJU
        ══════════════════════════════════════════════════════════ --}}
        @if($isPengaju)

            {{-- Ditolak Komisi B — bisa upload ulang --}}
            @if($rejected->isNotEmpty())
                <div class="rounded-2xl border border-error-200 bg-white p-5 dark:border-error-700/40 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-1 text-base font-semibold text-error-700 dark:text-error-400">Ditolak oleh Komisi B</h3>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Dokumen berikut ditolak. Perbaiki sesuai catatan dan upload ulang PDF final.</p>

                    @foreach($rejected as $doc)
                        <div class="mb-4 rounded-xl border border-error-200 bg-error-50/50 p-4 dark:border-error-700/40 dark:bg-error-500/5">
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}</p>
                            @if($doc->publish_notes)
                                <div class="mt-2 rounded-lg border border-error-200 bg-white px-3 py-2 text-xs text-error-700 dark:border-error-700/40 dark:bg-gray-900 dark:text-error-400">
                                    <span class="font-semibold">Catatan Komisi B:</span> {{ $doc->publish_notes }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('app.publish.publish', $doc->id) }}"
                                class="mt-4 border-t border-error-100 pt-4 dark:border-error-700/30"
                                enctype="multipart/form-data"
                                x-data="{ confirmed: false }">
                                @csrf
                                <p class="mb-2 text-xs font-medium text-gray-700 dark:text-gray-300">Upload Ulang PDF Final</p>
                                <input type="file" name="attachment" accept="application/pdf,.pdf" required
                                    class="mb-3 block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300">
                                <label class="mb-3 flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" x-model="confirmed" class="rounded border-gray-300">
                                    Saya sudah memperbaiki sesuai catatan dan dokumen sudah benar
                                </label>
                                <button type="submit"
                                    :disabled="!confirmed"
                                    :class="confirmed ? 'bg-brand-500 hover:bg-brand-600 cursor-pointer' : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700'"
                                    class="rounded-lg px-5 py-2 text-sm font-medium text-white transition">
                                    Ajukan Ulang ke Komisi B
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Siap Upload --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Siap Diajukan ke Publik</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Dokumen telah selesai disetujui. Upload PDF final lalu ajukan ke Komisi B untuk direview sebelum dipublikasikan.</p>

                @forelse($readyToUpload as $doc)
                    <div class="mb-4 rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/40 dark:bg-warning-500/10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-500">{{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}</p>
                                <p class="text-xs text-gray-400">Selesai: {{ optional($doc->completed_at)->format('d/m/Y H:i') ?? '-' }}</p>
                                <p class="text-xs text-warning-700 dark:text-warning-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="inline h-3.5 w-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Download dokumen, tempelkan semua tanda tangan, export ke PDF, lalu upload di sini untuk diajukan ke Komisi B.
                                </p>
                            </div>
                            <a href="{{ route('app.documents.show', $doc->id) }}" target="_blank"
                                class="shrink-0 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                                Lihat Dokumen
                            </a>
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
                                Ajukan ke Komisi B
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada dokumen yang menunggu upload.</p>
                    </div>
                @endforelse
            </div>

            {{-- Menunggu Review Komisi B --}}
            @if($pendingReview->isNotEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                    <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Menunggu Persetujuan Komisi B</h3>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Dokumen berikut sedang direview oleh Komisi B.</p>
                    @foreach($pendingReview as $doc)
                        <div class="mb-2 flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <div class="min-w-0 space-y-0.5">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-500">{{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}</p>
                            </div>
                            <span class="ml-3 shrink-0 inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                                <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Menunggu Review
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- History Publish --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">History Publish</h3>
                @forelse($history as $doc)
                    @php $publicUrl = route('public.document.show', $doc->id); @endphp
                    <div class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700 mb-2"
                        x-data="{ showQr: false, generated: false }">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-0.5 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-500">{{ $doc->documentType->name ?? '-' }} &bull; {{ $doc->organization->name ?? '-' }}</p>
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
        @endif
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    function initQr(el, url) {
        el.innerHTML = '';
        new QRCode(el, { text: url, width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
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
