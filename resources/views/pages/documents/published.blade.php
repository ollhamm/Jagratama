@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Dokumen Resmi" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Dokumen Resmi</h3>
                <p class="text-sm text-gray-500">Dokumen yang sudah dipublikasikan dan sah secara resmi.</p>
            </div>
            <form method="GET" action="{{ route('app.documents.published') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-nowrap sm:items-center">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari judul / nama pengaju"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 sm:w-56"
                />
                <button type="submit" class="h-10 w-full rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600 sm:w-auto">Cari</button>
                @if(request('search'))
                    <a href="{{ route('app.documents.published') }}" class="h-10 flex items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 sm:w-auto w-full">Reset</a>
                @endif
            </form>
        </div>

        <div class="space-y-3">
            @forelse($documents as $document)
                @php
                    $approvalLog = $document->approvals->sortBy(fn ($a) => [$a->step_order, $a->created_at])->values();
                    $publicUrl = route('public.document.show', $document->id);
                @endphp
                <div class="rounded-xl border border-gray-200 dark:border-gray-800" x-data="{ open: false }">
                    {{-- Baris list: judul + pengaju (kiri), QR (kanan) --}}
                    <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-4 p-4 text-left hover:bg-gray-50 dark:hover:bg-white/5">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $document->title }}</p>
                                <p class="text-xs text-gray-500">
                                    Pengaju: {{ $document->creator->name ?? '-' }}
                                    · {{ $document->organization->name ?? '-' }}
                                    · Dipublikasikan {{ optional($document->published_at)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                        <div id="qr-{{ $document->id }}" data-url="{{ $publicUrl }}" class="shrink-0" @click.stop></div>
                    </button>

                    {{-- Detail: status, tipe dokumen, organisasi, riwayat catatan --}}
                    <div x-show="open" x-cloak class="border-t border-gray-200 p-4 dark:border-gray-800">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm text-gray-500">Status: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $document->current_status->value ?? $document->current_status }}</span></span>
                            <a href="{{ $publicUrl }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg border border-success-300 px-3 py-1 text-xs font-medium text-success-600 hover:bg-success-50 dark:border-success-500/40 dark:text-success-400 dark:hover:bg-success-500/10">
                                Buka Dokumen Resmi
                            </a>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Tipe Dokumen</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->documentType->code ?? '-' }} - {{ $document->documentType->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Organisasi</p>
                                <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->organization->name ?? '-' }}</p>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800 md:col-span-2">
                                <p class="text-xs uppercase text-gray-500">Riwayat Perjalanan Dokumen</p>
                                @if($approvalLog->isNotEmpty())
                                    <div class="mt-2 space-y-2">
                                        @foreach($approvalLog as $log)
                                            @php
                                                $logStatus = $log->status->value ?? $log->status;
                                                $badgeClass = match ($logStatus) {
                                                    'APPROVED' => 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400',
                                                    'REJECTED' => 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400',
                                                    default    => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
                                                };
                                            @endphp
                                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                                            Step {{ $log->step_order }} — {{ $log->workflowStep->role->name ?? $log->workflowStep->role->code ?? '-' }}
                                                        </span>
                                                        <span class="rounded px-2 py-0.5 text-[10px] font-semibold {{ $badgeClass }}">{{ $logStatus }}</span>
                                                    </div>
                                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                        {{ $log->approver->name ?? '-' }}
                                                        @if($log->approved_at)
                                                            · {{ $log->approved_at->format('d/m/Y H:i') }}
                                                        @endif
                                                    </span>
                                                </div>
                                                @if($log->notes)
                                                    <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                                        <span class="font-medium">Catatan:</span> {{ $log->notes }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-1 text-sm text-gray-500">Belum ada riwayat approval.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700">
                    Belum ada dokumen resmi yang dipublikasikan.
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $documents->appends(request()->query())->links() }}</div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id^="qr-"]').forEach(function (el) {
            const url = el.dataset.url;
            if (!url) return;
            new QRCode(el, {
                text: url,
                width: 64,
                height: 64,
                colorDark: '#101828',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        });
    });
</script>
@endpush
