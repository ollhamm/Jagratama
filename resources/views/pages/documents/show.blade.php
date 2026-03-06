@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Detail Dokumen" />

    <div class="space-y-5">
        @if(session('success'))
            <div class="rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $document->title }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Status: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $document->current_status->value ?? $document->current_status }}</span></p>
                </div>

                @if(($document->current_status->value ?? $document->current_status) === 'DRAFT')
                    <form method="POST" action="{{ route('app.documents.submit', $document->id) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Submit Dokumen</button>
                    </form>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <p class="text-xs uppercase text-gray-500">Tipe Dokumen</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->documentType->code ?? '-' }} - {{ $document->documentType->name ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <p class="text-xs uppercase text-gray-500">Organisasi</p>
                    <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->organization->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <h4 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Lampiran</h4>
            <ul class="space-y-2">
                @forelse($document->attachments as $attachment)
                    <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-800">
                        <div>
                            <p class="text-sm text-gray-800 dark:text-white/90">{{ basename($attachment->file_path) }}</p>
                            <p class="text-xs text-gray-500">{{ $attachment->file_type }}</p>
                        </div>
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($attachment->file_path) }}" target="_blank" class="text-sm text-brand-600 hover:text-brand-700">Lihat</a>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">Belum ada lampiran.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
