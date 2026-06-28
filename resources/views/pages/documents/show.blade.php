@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Konfirmasi Dokumen" />

    <div class="space-y-5">
        @if(session('success'))
            <div class="rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @php
            $currentStatus = $document->current_status->value ?? $document->current_status;
            // Ambil instance terakhir (baik finished maupun aktif) agar step tetap tampil saat rejected/completed
            $latestInstance = $document->workflowInstances->sortByDesc('started_at')->first();
            $workflowSteps = $latestInstance?->workflow?->steps?->sortBy('step_order') ?? collect();
            $approvals = $document->approvals->keyBy('step_order');
            $currentStep = $document->current_step_order;
            // Riwayat semua reject sepanjang siklus dokumen ini (bisa lebih dari satu, termasuk di step yang sama)
            $rejectHistory = $document->approvals
                ->filter(fn ($a) => ($a->status->value ?? $a->status) === 'REJECTED')
                ->sortBy('created_at')
                ->values();
            $primaryAttachment = $document->attachments->first();
            $primaryAttachmentUrl = $primaryAttachment ? route('app.documents.attachments.preview', ['id' => $document->id, 'attachmentId' => $primaryAttachment->id]) : null;
            $primaryPdfUrl = $primaryAttachment ? route('app.documents.attachments.pdf', ['id' => $document->id, 'attachmentId' => $primaryAttachment->id]) : null;
        @endphp

        {{-- Riwayat Revisi: semua catatan reject sepanjang siklus dokumen ini --}}
        @if($rejectHistory->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <h4 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Riwayat Revisi</h4>
                <div class="space-y-3">
                    @foreach($rejectHistory as $i => $rejected)
                        <div class="rounded-lg border border-error-200 bg-error-50 p-3 dark:border-error-800 dark:bg-error-500/10">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-error-700 dark:text-error-400">
                                    Revisi #{{ $i + 1 }} — Step {{ $rejected->step_order }} ({{ $rejected->workflowStep->role->name ?? $rejected->workflowStep->role->code ?? '-' }})
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Ditolak oleh {{ $rejected->approver->name ?? '-' }} pada {{ $rejected->approved_at?->format('d/m/Y H:i') ?? '-' }}
                                </span>
                            </div>
                            @if($rejected->notes)
                                <p class="mt-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <span class="font-medium">Alasan:</span> {{ $rejected->notes }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($currentStatus === 'DRAFT')
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-9">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900/50">
                            @if($primaryPdfUrl)
                                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800">
                                    <span class="truncate text-xs font-medium text-gray-600 dark:text-gray-300">{{ basename($primaryAttachment->file_path) }}</span>
                                    <a href="{{ $primaryAttachmentUrl }}" class="ml-3 shrink-0 rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700">Download PDF</a>
                                </div>
                                <iframe
                                    src="{{ $primaryPdfUrl }}"
                                    class="h-[70vh] w-full"
                                    title="Preview Dokumen"
                                ></iframe>
                            @else
                                <div class="flex h-[40vh] items-center justify-center text-sm text-gray-500">
                                    Lampiran belum tersedia.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="xl:col-span-3">
                        <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Informasi Pengajuan</h4>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Tipe Surat</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">{{ $workflowName ?? (($document->documentType->code ?? '-') . ' - ' . ($document->documentType->name ?? '-')) }}</p>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Ditujukan Kepada</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">{{ $document->organization->name ?? '-' }}</p>
                            </div>

                            <form id="submit-form" method="POST" action="{{ route('app.documents.submit', $document->id) }}" class="space-y-3">
                                @csrf

                                {{-- Tanda Tangan Pengaju --}}
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 space-y-2">
                                    <p class="text-xs font-medium uppercase text-gray-500">Tanda Tangan Pengaju <span class="text-red-500">*</span></p>
                                    <div id="submitter-sig-thumb-wrap" class="hidden">
                                        <img id="submitter-sig-thumb" src="#" alt="Tanda Tangan"
                                            class="mx-auto max-h-16 rounded border border-gray-200 bg-white">
                                    </div>
                                    <button type="button" id="open-submitter-sig-modal"
                                        class="w-full rounded-lg border-2 border-dashed border-brand-300 py-2 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                        Buka Pad Tanda Tangan
                                    </button>
                                    <input type="hidden" name="signature_value" id="submitter-signature-value-input">
                                    <p id="submitter-sig-error" class="hidden text-xs text-red-500">Tanda tangan wajib diisi sebelum mengajukan.</p>
                                </div>

                                <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Ajukan Sekarang</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($currentStatus !== 'DRAFT')
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-9">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900/50">
                            @if($primaryPdfUrl)
                                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800">
                                    <span class="truncate text-xs font-medium text-gray-600 dark:text-gray-300">{{ basename($primaryAttachment->file_path) }}</span>
                                    <a href="{{ $primaryAttachmentUrl }}" class="ml-3 shrink-0 rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700">Download PDF</a>
                                </div>
                                <iframe
                                    src="{{ $primaryPdfUrl }}"
                                    class="h-[70vh] w-full"
                                    title="Preview Dokumen"
                                ></iframe>
                            @else
                                <div class="flex h-[40vh] items-center justify-center text-sm text-gray-500">
                                    Lampiran belum tersedia.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="xl:col-span-3">
                        <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Approval Dokumen</h4>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Status Saat Ini</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">{{ $currentStatus }}</p>
                            </div>

                            @if(isset($pendingApprovalForUser) && $pendingApprovalForUser)
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                    <p class="text-xs uppercase text-gray-500">Step Anda</p>
                                    <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">
                                        {{ $pendingApprovalForUser->workflowStep->role->name ?? ($pendingApprovalForUser->workflowStep->role->code ?? '-') }}
                                        (Step {{ $pendingApprovalForUser->step_order }})
                                    </p>
                                </div>

                                <form id="approve-form" method="POST" action="{{ route('app.approvals.approve', $pendingApprovalForUser->id) }}" class="space-y-2 rounded-lg border border-success-200 p-3 dark:border-success-700/40">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('app.documents.show', $document->id) }}" />
                                    <textarea name="notes" rows="2" placeholder="Catatan approve (opsional)" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700"></textarea>

                                    @if($pendingApprovalForUser->workflowStep->is_required_signature)
                                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700 space-y-2">
                                            <p class="text-xs font-medium uppercase text-gray-500">Tanda Tangan <span class="text-red-500">*</span></p>
                                            {{-- Preview thumbnail setelah tanda tangan diisi --}}
                                            <div id="sig-thumb-wrap" class="hidden">
                                                <img id="sig-thumb" src="#" alt="Tanda Tangan"
                                                    class="mx-auto max-h-16 rounded border border-gray-200 bg-white">
                                            </div>
                                            <button type="button" id="open-sig-modal"
                                                class="w-full rounded-lg border-2 border-dashed border-brand-300 py-2 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                                Buka Pad Tanda Tangan
                                            </button>
                                            <input type="hidden" name="signature_value" id="approval-signature-value-input">
                                            <p id="sig-error" class="hidden text-xs text-red-500">Tanda tangan wajib diisi sebelum approve.</p>
                                        </div>
                                    @endif

                                    <button type="submit" class="w-full rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-700">Approve Step Ini</button>
                                </form>

                                <form method="POST" action="{{ route('app.approvals.reject', $pendingApprovalForUser->id) }}" class="space-y-2 rounded-lg border border-error-200 p-3 dark:border-error-700/40">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('app.documents.show', $document->id) }}" />
                                    <textarea name="notes" rows="2" placeholder="Alasan reject" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700" required>{{ old('notes') }}</textarea>
                                    <button type="submit" class="w-full rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-700">Reject Step Ini</button>
                                </form>
                            @elseif(isset($canPublish) && $canPublish)
                                <div class="rounded-lg border border-brand-200 bg-brand-50 p-3 dark:border-brand-700/40 dark:bg-brand-500/10">
                                    <p class="text-xs text-brand-700 dark:text-brand-400">
                                        Semua step approval sudah selesai. Sebagai approver pada step paling akhir, anda dapat mempublikasikan dokumen ini.
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('app.documents.publish', $document->id) }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Publish Dokumen</button>
                                </form>
                            @else
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                    <p class="text-xs text-gray-500">Anda tidak memiliki approval pending pada step aktif dokumen ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step Progress Indicator --}}
        @if($currentStatus !== 'DRAFT' && $workflowSteps->isNotEmpty())
            @if($currentStatus === 'REJECTED')
                @php
                    $rejectedApproval = $rejectHistory->last();
                @endphp
                @if($rejectedApproval)
                    <div class="rounded-2xl border border-error-200 bg-error-50 p-4 dark:border-error-800 dark:bg-error-500/10">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-error-600 dark:text-error-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <h5 class="text-sm font-semibold text-error-800 dark:text-error-300">Dokumen Ditolak</h5>
                                <p class="mt-1 text-sm text-error-700 dark:text-error-400">
                                    Ditolak oleh <span class="font-medium">{{ $rejectedApproval->approver->name ?? '-' }}</span> 
                                    ({{ $rejectedApproval->workflowStep->role->name ?? $rejectedApproval->workflowStep->role->code ?? '-' }})
                                    pada {{ $rejectedApproval->approved_at?->format('d/m/Y H:i') ?? '-' }}
                                </p>
                                @if($rejectedApproval->notes)
                                    <p class="mt-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <span class="font-medium">Alasan:</span> {{ $rejectedApproval->notes }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @php
                $publishStepPublished = ! is_null($document->published_at);
                $publishStepActive    = $currentStatus === 'COMPLETED' && ! $publishStepPublished;

                $totalSteps     = $workflowSteps->count() + 1;
                $completedSteps = $publishStepPublished
                    ? $totalSteps
                    : ($currentStatus === 'COMPLETED' ? $workflowSteps->count() : max(0, $currentStep - 1));
            @endphp

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Progress Approval</h4>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        @if($publishStepPublished)
                            Step {{ $totalSteps }} dari {{ $totalSteps }} — <span class="font-semibold text-success-600">Selesai</span>
                        @elseif($currentStatus === 'COMPLETED')
                            Step {{ $workflowSteps->count() }} dari {{ $totalSteps }} — <span class="font-semibold text-brand-600">Menunggu Publish</span>
                        @elseif($currentStatus === 'REJECTED')
                            Ditolak di Step {{ $currentStep }} dari {{ $totalSteps }}
                        @else
                            Step {{ $currentStep }} dari {{ $totalSteps }}
                        @endif
                    </span>
                </div>

                <div class="overflow-x-auto pb-2">
                <div class="relative" style="min-width: {{ $totalSteps * 80 }}px">
                    <div class="absolute left-0 top-5 h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                    <div class="absolute left-0 top-5 h-0.5 bg-brand-500 transition-all duration-500"
                        style="width: {{ $totalSteps > 0 ? ($completedSteps / $totalSteps * 100) : 0 }}%"></div>

                    <div class="relative flex justify-between">
                        {{-- Workflow steps --}}
                        @foreach($workflowSteps as $step)
                            @php
                                $approval = $approvals->get($step->step_order);
                                $approvalStatus = $approval?->status?->value ?? $approval?->status;
                                $isCompleted = in_array($approvalStatus, ['APPROVED', 'SKIPPED'], true);
                                $isRejected  = $approvalStatus === 'REJECTED';
                                $isCurrent   = $step->step_order === $currentStep && !$isCompleted && !$isRejected && $currentStatus !== 'COMPLETED';
                                $isPending   = !$isCompleted && !$isRejected && !$isCurrent;
                            @endphp
                            <div class="flex flex-col items-center" style="flex: 1">
                                <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 bg-white transition-all dark:bg-gray-900
                                    {{ $isCompleted ? 'border-success-500 bg-success-50 dark:bg-success-500/20' : '' }}
                                    {{ $isRejected  ? 'border-error-500 bg-error-50 dark:bg-error-500/20' : '' }}
                                    {{ $isCurrent   ? 'border-brand-500 bg-brand-50 shadow-lg shadow-brand-500/30 dark:bg-brand-500/20' : '' }}
                                    {{ $isPending   ? 'border-gray-300 dark:border-gray-700' : '' }}">
                                    @if($isCompleted)
                                        <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @elseif($isRejected)
                                        <svg class="h-5 w-5 text-error-600 dark:text-error-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @else
                                        <span class="text-sm font-semibold {{ $isCurrent ? 'text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $step->step_order }}</span>
                                    @endif
                                </div>
                                <div class="mt-2 text-center">
                                    <p class="text-xs font-medium {{ $isCurrent ? 'text-brand-600 dark:text-brand-400' : ($isCompleted ? 'text-success-600 dark:text-success-400' : ($isRejected ? 'text-error-600 dark:text-error-400' : 'text-gray-500 dark:text-gray-400')) }}">
                                        {{ $step->role->name ?? $step->role->code ?? 'Step '.$step->step_order }}
                                    </p>
                                    @if($approval)
                                        <p class="mt-0.5 text-[10px] text-gray-400">{{ $approval->approver->name ?? '-' }}</p>
                                        @if($approval->approved_at)
                                            <p class="mt-0.5 text-[10px] text-gray-400">{{ $approval->approved_at->format('d/m H:i') }}</p>
                                        @endif
                                        @if($isRejected && $approval->notes)
                                            <p class="mt-1 max-w-[120px] truncate text-[10px] text-error-500" title="{{ $approval->notes }}">{{ $approval->notes }}</p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Step Publish Dokumen --}}
                        <div class="flex flex-col items-center" style="flex: 1">
                            <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 bg-white transition-all dark:bg-gray-900
                                {{ $publishStepPublished ? 'border-success-500 bg-success-50 dark:bg-success-500/20' : '' }}
                                {{ $publishStepActive    ? 'border-brand-500 bg-brand-50 shadow-lg shadow-brand-500/30 dark:bg-brand-500/20' : '' }}
                                {{ (!$publishStepPublished && !$publishStepActive) ? 'border-gray-300 dark:border-gray-700' : '' }}">
                                @if($publishStepPublished)
                                    <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    <svg class="h-5 w-5 {{ $publishStepActive ? 'text-brand-500 dark:text-brand-400' : 'text-gray-400 dark:text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                @endif
                            </div>
                            <div class="mt-2 text-center">
                                <p class="text-xs font-medium
                                    {{ $publishStepPublished ? 'text-success-600 dark:text-success-400' : '' }}
                                    {{ $publishStepActive    ? 'text-brand-600 dark:text-brand-400' : '' }}
                                    {{ (!$publishStepPublished && !$publishStepActive) ? 'text-gray-400 dark:text-gray-600' : '' }}">
                                    Publish Dokumen
                                </p>
                                <p class="mt-0.5 text-[10px]
                                    {{ $publishStepPublished ? 'text-success-500' : '' }}
                                    {{ !$publishStepPublished ? 'text-gray-400' : '' }}">
                                    @if($publishStepPublished)
                                        {{ optional($document->published_at)->format('d/m H:i') }}
                                    @elseif($publishStepActive)
                                        Menunggu approver terakhir publish
                                    @else
                                        Menunggu
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        @endif

        {{-- Form Submit Ulang: muncul saat REJECTED dan user adalah pengaju --}}
        @if($currentStatus === 'REJECTED' && $document->created_by === auth()->id())
            @php
                $rejectedApproval = $rejectHistory->last();
            @endphp
            <div class="rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-800 dark:bg-error-500/10 sm:p-6">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-error-600 dark:text-error-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h5 class="text-sm font-semibold text-error-800 dark:text-error-300">Dokumen Ditolak — Perlu Submit Ulang</h5>
                        <p class="mt-1 text-sm text-error-700 dark:text-error-400">
                            Ditolak oleh <span class="font-medium">{{ $rejectedApproval?->approver->name ?? '-' }}</span>
                            (Step {{ $rejectedApproval?->step_order }}).
                            Jika disubmit ulang, proses akan dilanjutkan dari step {{ $rejectedApproval?->step_order }}.
                        </p>
                        @if($rejectedApproval?->notes)
                            <p class="mt-2 rounded-lg bg-white px-3 py-2 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <span class="font-medium">Alasan:</span> {{ $rejectedApproval->notes }}
                            </p>
                        @endif
                        <div class="mt-4">
                            <a href="{{ route('app.documents.resubmit.form', $document->id) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-error-600 px-5 py-2 text-sm font-medium text-white hover:bg-error-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit & Submit Ulang dari Step {{ $rejectedApproval?->step_order }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        {{ $document->title }}
                        @if($rejectHistory->isNotEmpty())
                            <span class="ml-1 inline-flex items-center rounded-full bg-warning-100 px-2.5 py-0.5 text-xs font-semibold text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">🔄 Revisi</span>
                        @endif
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Status: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $currentStatus }}</span></p>
                    @if($currentStatus === 'REJECTED' && $rejectHistory->last()?->notes)
                        <p class="mt-2 max-w-xl whitespace-pre-line rounded-lg bg-error-50 px-3 py-2 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
                            <span class="font-medium">Catatan:</span> {{ $rejectHistory->last()->notes }}
                        </p>
                    @endif
                </div>
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


                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800 md:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Riwayat Catatan</p>
                    @php
                        $approvalLog = $document->approvals->sortBy(fn ($a) => [$a->step_order, $a->created_at])->values();
                    @endphp

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
@endsection

@push('modals')
    @if($currentStatus === 'DRAFT' && $document->created_by === auth()->id())
    <div id="submitter-sig-modal" class="fixed hidden items-center justify-center bg-gray-900/40 p-4" style="inset:0; z-index:9999999; position:fixed;">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900" x-data="{ sigTab: 'draw' }">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tanda Tangan Pengaju</h3>
                <button type="button" id="close-submitter-sig-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex gap-2 px-6 pt-4">
                <button type="button" @click="sigTab='draw'"
                    :class="sigTab==='draw' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition">Gambar</button>
                <button type="button" @click="sigTab='upload'"
                    :class="sigTab==='upload' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition">Upload Gambar</button>
            </div>
            <div class="px-6 py-4">
                <div x-show="sigTab === 'draw'">
                    <canvas id="submitter-sig-canvas"
                        class="w-full rounded-xl border-2 border-dashed border-gray-300 bg-white dark:border-gray-600"
                        height="400" style="touch-action:none; cursor:crosshair; display:block;"></canvas>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Gunakan mouse atau jari untuk menggambar tanda tangan</p>
                        <button type="button" id="clear-submitter-sig" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
                    </div>
                </div>
                <div x-show="sigTab === 'upload'" class="space-y-3">
                    <div class="flex h-48 flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                        <svg class="mb-3 h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4-4m0 0l4 4m-4-4v9M20 12a8 8 0 11-16 0 8 8 0 0116 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih gambar tanda tangan</p>
                        <label class="mt-3 cursor-pointer rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                            Pilih File
                            <input type="file" id="submitter-sig-upload" accept="image/*" class="hidden">
                        </label>
                    </div>
                    <img id="submitter-sig-preview" src="#" alt="Preview"
                        class="hidden mx-auto max-h-40 rounded-xl border border-gray-200 bg-white">
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <button type="button" id="close-submitter-sig-modal-cancel"
                    class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="button" id="confirm-submitter-sig"
                    class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Gunakan Tanda Tangan
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(isset($pendingApprovalForUser) && $pendingApprovalForUser && $pendingApprovalForUser->workflowStep->is_required_signature)
    <div id="sig-modal" class="fixed hidden items-center justify-center bg-gray-900/40 p-4" style="inset:0; z-index:9999999; position:fixed;">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900" x-data="{ sigTab: 'draw' }">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tanda Tangan</h3>
                <button type="button" id="close-sig-modal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Tab switcher --}}
            <div class="flex gap-2 px-6 pt-4">
                <button type="button" @click="sigTab='draw'"
                    :class="sigTab==='draw' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition">Gambar</button>
                <button type="button" @click="sigTab='upload'"
                    :class="sigTab==='upload' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition">Upload Gambar</button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4">
                {{-- Canvas draw --}}
                <div x-show="sigTab === 'draw'">
                    <canvas id="approval-sig-canvas"
                        class="w-full rounded-xl border-2 border-dashed border-gray-300 bg-white dark:border-gray-600"
                        height="400" style="touch-action:none; cursor:crosshair; display:block;"></canvas>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Gunakan mouse atau jari untuk menggambar tanda tangan</p>
                        <button type="button" id="clear-approval-sig" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
                    </div>
                </div>

                {{-- Upload --}}
                <div x-show="sigTab === 'upload'" class="space-y-3">
                    <div class="flex h-48 flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                        <svg class="mb-3 h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4-4m0 0l4 4m-4-4v9M20 12a8 8 0 11-16 0 8 8 0 0116 0z"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pilih gambar tanda tangan</p>
                        <label class="mt-3 cursor-pointer rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                            Pilih File
                            <input type="file" id="approval-sig-upload" accept="image/*" class="hidden">
                        </label>
                    </div>
                    <img id="approval-sig-preview" src="#" alt="Preview"
                        class="hidden mx-auto max-h-40 rounded-xl border border-gray-200 bg-white">
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <button type="button" id="close-sig-modal-cancel"
                    class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="button" id="confirm-sig"
                    class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    Gunakan Tanda Tangan
                </button>
            </div>
        </div>
    </div>
    @endif
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        const LOGO_QR_ROLES  = ['KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM', 'DIREKTUR'];
        const KEMENKES_LOGO  = '{{ asset('images/logo/kemenkes-logo.png') }}';

        function overlayLogoOnCanvas(canvas, logoUrl) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function () {
                const ctx  = canvas.getContext('2d');
                const size = Math.floor(canvas.width * 0.20);
                const x    = Math.floor((canvas.width  - size) / 2);
                const y    = Math.floor((canvas.height - size) / 2);
                // White padding so logo doesn't blend with QR modules
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(x - 5, y - 5, size + 10, size + 10);
                ctx.drawImage(img, x, y, size, size);
                // Sync the img tag QRCode.js also creates
                const qrImg = canvas.parentElement ? canvas.parentElement.querySelector('img') : null;
                if (qrImg) qrImg.src = canvas.toDataURL('image/png');
            };
            img.src = logoUrl;
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.sig-qr').forEach(function (el) {
                const url      = el.dataset.url;
                const role     = el.dataset.role || '';
                if (!url) return;
                const needsLogo = LOGO_QR_ROLES.includes(role);
                new QRCode(el, {
                    text: url,
                    width: 300,
                    height: 300,
                    colorDark: '#101828',
                    colorLight: '#ffffff',
                    // Gunakan error correction H (30%) untuk role dengan logo agar tetap bisa di-scan
                    correctLevel: needsLogo ? QRCode.CorrectLevel.H : QRCode.CorrectLevel.M,
                });
                // Kecilkan tampilan di kartu tapi biarkan canvas tetap 300px untuk download
                const canvas = el.querySelector('canvas');
                const img    = el.querySelector('img');
                if (canvas) { canvas.style.width = '72px'; canvas.style.height = '72px'; }
                if (img)    { img.style.width = '72px'; img.style.height = '72px'; }
                if (needsLogo && canvas) {
                    overlayLogoOnCanvas(canvas, KEMENKES_LOGO);
                }
            });
        });

        function downloadSigQr(btn, filename) {
            const card = btn.closest('.flex.flex-col');
            const canvas = card ? card.querySelector('.sig-qr canvas') : null;
            if (!canvas) { alert('QR belum siap, coba lagi.'); return; }
            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/png');
            a.download = filename;
            a.click();
        }

        function downloadSigPng(dataUrl, filename) {
            if (!dataUrl) return;
            const parts = dataUrl.split(',');
            const mime  = (parts[0].match(/:(.*?);/) || [])[1] || 'image/png';
            const bstr  = atob(parts[1]);
            const u8arr = new Uint8Array(bstr.length);
            for (let i = 0; i < bstr.length; i++) u8arr[i] = bstr.charCodeAt(i);
            const blob = new Blob([u8arr], { type: mime });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>

    @if($currentStatus === 'DRAFT' && $document->created_by === auth()->id())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/4.1.7/signature_pad.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal        = document.getElementById('submitter-sig-modal');
            const openBtn      = document.getElementById('open-submitter-sig-modal');
            const closeBtn     = document.getElementById('close-submitter-sig-modal');
            const closeBtnCancel = document.getElementById('close-submitter-sig-modal-cancel');
            const confirmBtn   = document.getElementById('confirm-submitter-sig');
            const canvas       = document.getElementById('submitter-sig-canvas');
            const clearBtn     = document.getElementById('clear-submitter-sig');
            const uploadInput  = document.getElementById('submitter-sig-upload');
            const previewImg   = document.getElementById('submitter-sig-preview');
            const hiddenInput  = document.getElementById('submitter-signature-value-input');
            const submitForm   = document.getElementById('submit-form');
            const thumbWrap    = document.getElementById('submitter-sig-thumb-wrap');
            const thumb        = document.getElementById('submitter-sig-thumb');
            const sigError     = document.getElementById('submitter-sig-error');

            let signaturePad = null;

            function initPad() {
                if (signaturePad) return;
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255,255,255)',
                    penColor: 'rgb(0,0,0)',
                });
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width  = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad.clear();
            }

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                requestAnimationFrame(() => initPad());
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            openBtn?.addEventListener('click', openModal);
            closeBtn?.addEventListener('click', closeModal);
            closeBtnCancel?.addEventListener('click', closeModal);
            modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

            clearBtn?.addEventListener('click', () => {
                signaturePad?.clear();
                if (uploadInput) uploadInput.value = '';
                if (previewImg) { previewImg.src = '#'; previewImg.classList.add('hidden'); }
            });

            uploadInput?.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = ev => {
                    previewImg.src = ev.target.result;
                    previewImg.classList.remove('hidden');
                    signaturePad?.clear();
                };
                reader.readAsDataURL(file);
            });

            confirmBtn?.addEventListener('click', () => {
                let value = '';
                if (previewImg && !previewImg.classList.contains('hidden') && previewImg.src && previewImg.src !== '#' && previewImg.src !== window.location.href) {
                    value = previewImg.src;
                } else if (signaturePad && !signaturePad.isEmpty()) {
                    value = signaturePad.toDataURL('image/png');
                }
                if (!value) return;

                hiddenInput.value = value;
                thumb.src = value;
                thumbWrap.classList.remove('hidden');
                openBtn.textContent = 'Ubah Tanda Tangan';
                sigError?.classList.add('hidden');
                closeModal();
            });

            submitForm?.addEventListener('submit', function (e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    sigError?.classList.remove('hidden');
                    openBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    sigError?.classList.add('hidden');
                }
            });
        });
    </script>
    @endif

    @if(isset($pendingApprovalForUser) && $pendingApprovalForUser && $pendingApprovalForUser->workflowStep->is_required_signature)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/4.1.7/signature_pad.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal          = document.getElementById('sig-modal');
            const openBtn        = document.getElementById('open-sig-modal');
            const closeBtn       = document.getElementById('close-sig-modal');
            const closeBtnCancel = document.getElementById('close-sig-modal-cancel');
            const confirmBtn     = document.getElementById('confirm-sig');
            const canvas         = document.getElementById('approval-sig-canvas');
            const clearBtn       = document.getElementById('clear-approval-sig');
            const uploadInput    = document.getElementById('approval-sig-upload');
            const previewImg     = document.getElementById('approval-sig-preview');
            const hiddenInput    = document.getElementById('approval-signature-value-input');
            const approveForm    = document.getElementById('approve-form');
            const sigThumbWrap   = document.getElementById('sig-thumb-wrap');
            const sigThumb       = document.getElementById('sig-thumb');
            const sigError       = document.getElementById('sig-error');

            if (!canvas) return;

            let signaturePad = null;

            function initPad() {
                if (signaturePad) return;
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255,255,255)',
                    penColor: 'rgb(0,0,0)',
                });
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width  = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad.clear(); // isi background putih pada canvas
            }

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                requestAnimationFrame(function () { initPad(); });
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            openBtn?.addEventListener('click', openModal);
            closeBtn?.addEventListener('click', closeModal);
            closeBtnCancel?.addEventListener('click', closeModal);
            modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

            clearBtn?.addEventListener('click', function () {
                signaturePad?.clear();
                if (uploadInput) uploadInput.value = '';
                if (previewImg)  { previewImg.src = '#'; previewImg.classList.add('hidden'); }
            });

            uploadInput?.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (ev) {
                    previewImg.src = ev.target.result;
                    previewImg.classList.remove('hidden');
                    signaturePad?.clear();
                };
                reader.readAsDataURL(file);
            });

            confirmBtn?.addEventListener('click', function () {
                let value = '';
                if (previewImg && !previewImg.classList.contains('hidden') && previewImg.src && previewImg.src !== '#' && previewImg.src !== window.location.href) {
                    value = previewImg.src;
                } else if (signaturePad && !signaturePad.isEmpty()) {
                    value = signaturePad.toDataURL('image/png');
                }
                if (!value) return;

                hiddenInput.value = value;
                if (sigThumb && sigThumbWrap) {
                    sigThumb.src = value;
                    sigThumbWrap.classList.remove('hidden');
                    if (openBtn) openBtn.textContent = 'Ubah Tanda Tangan';
                }
                sigError?.classList.add('hidden');
                closeModal();
            });

            approveForm?.addEventListener('submit', function (e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    sigError?.classList.remove('hidden');
                } else {
                    sigError?.classList.add('hidden');
                }
            });
        });
    </script>
    @endif
@endpush
