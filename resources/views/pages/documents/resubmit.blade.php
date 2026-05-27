@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Edit & Submit Ulang" />

    <div class="space-y-5">
        @if($errors->any())
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        {{-- Info alasan reject --}}
        @if($rejectedApproval)
            <div class="rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-800 dark:bg-error-500/10">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-error-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-error-800 dark:text-error-300">
                            Ditolak oleh {{ $rejectedApproval->approver->name ?? '-' }} — Step {{ $rejectedApproval->step_order }}
                        </p>
                        @if($rejectedApproval->notes)
                            <p class="mt-1 text-sm text-error-700 dark:text-error-400">
                                <span class="font-medium">Alasan:</span> {{ $rejectedApproval->notes }}
                            </p>
                        @endif
                        <p class="mt-2 text-xs text-error-600 dark:text-error-500">
                            Setelah submit ulang, proses approval akan dilanjutkan dari Step {{ $rejectedApproval->step_order }}.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form edit dokumen --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">Upload File Baru</h3>

            <form method="POST" action="{{ route('app.documents.resubmit', $document->id) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <p class="text-xs uppercase text-gray-500">Dokumen</p>
                        <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->title }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <p class="text-xs uppercase text-gray-500">Tipe Dokumen</p>
                        <p class="mt-1 text-sm font-medium text-gray-800 dark:text-white/90">
                            {{ $document->documentType->name ?? '-' }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        File PDF Baru <span class="text-red-500">*</span>
                        <span class="ml-1 font-normal text-gray-400">(menggantikan file lama)</span>
                    </label>
                    @if($document->attachments->isNotEmpty())
                        <p class="mb-2 text-xs text-gray-500">
                            File saat ini: <span class="font-medium">{{ basename($document->attachments->first()->file_path) }}</span>
                        </p>
                    @endif
                    <input type="file" name="attachment"
                        accept=".pdf,application/pdf"
                        required
                        class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('app.documents.show', $document->id) }}"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        Batal
                    </a>
                    <button type="submit"
                        class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Submit Ulang dari Step {{ $rejectedApproval?->step_order }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
