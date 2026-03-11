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

        @php
            $currentStatus = $document->current_status->value ?? $document->current_status;
            // Ambil instance terakhir (baik finished maupun aktif) agar step tetap tampil saat rejected/completed
            $latestInstance = $document->workflowInstances->sortByDesc('started_at')->first();
            $workflowSteps = $latestInstance?->workflow?->steps?->sortBy('step_order') ?? collect();
            $approvals = $document->approvals->keyBy('step_order');
            $currentStep = $document->current_step_order;
            $primaryAttachment = $document->attachments->first();
            $primaryAttachmentUrl = $primaryAttachment ? route('app.documents.attachments.preview', ['id' => $document->id, 'attachmentId' => $primaryAttachment->id]) : null;
        @endphp

        @if($currentStatus === 'DRAFT')
            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:p-5">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-9">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            @if($primaryAttachmentUrl)
                                <iframe
                                    src="{{ $primaryAttachmentUrl }}#toolbar=1&navpanes=0"
                                    class="h-[70vh] w-full"
                                    title="Preview Dokumen"
                                ></iframe>
                            @else
                                <div class="flex h-[40vh] items-center justify-center text-sm text-gray-500">
                                    Lampiran belum tersedia untuk dipreview.
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

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Action</p>
                                <button id="generate-signature-btn" type="button" class="mt-2 w-full rounded-lg border border-brand-300 px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-300 dark:hover:bg-brand-500/10">
                                    Masukan Tanda Tangan
                                </button>
                            </div>

                            <div id="submitter-signature-box" class="hidden rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">QR Pengaju (Auto)</p>
                                <div id="submitter-signature-qr" class="mt-2 flex items-center justify-center rounded-md bg-white p-2"></div>
                                <p class="mt-2 text-[11px] text-gray-500">QR ini unik per dokumen dan memuat judul, tipe surat, organisasi, serta nama pengaju.</p>
                            </div>

                            <form method="POST" action="{{ route('app.documents.submit', $document->id) }}" class="space-y-2">
                                @csrf
                                <input id="signature-value-input" name="signature_value" value="{{ old('signature_value', '') }}" type="hidden" required />
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
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            @if($primaryAttachmentUrl)
                                <iframe
                                    src="{{ $primaryAttachmentUrl }}#toolbar=1&navpanes=0"
                                    class="h-[70vh] w-full"
                                    title="Preview Dokumen"
                                ></iframe>
                            @else
                                <div class="flex h-[40vh] items-center justify-center text-sm text-gray-500">
                                    Lampiran belum tersedia untuk dipreview.
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

                                @if($pendingApprovalForUser->workflowStep->is_required_signature)
                                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                        <p class="text-xs uppercase text-gray-500">Signature Approval</p>
                                        <button id="generate-approval-signature-btn" type="button" class="mt-2 w-full rounded-lg border border-brand-300 px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-300 dark:hover:bg-brand-500/10">
                                            Generate QR Approval
                                        </button>
                                    </div>

                                    <div id="approval-signature-box" class="hidden rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                        <p class="text-xs uppercase text-gray-500">QR Approver (Auto)</p>
                                        <div id="approval-signature-qr" class="mt-2 flex items-center justify-center rounded-md bg-white p-2"></div>
                                        <p class="mt-2 text-[11px] text-gray-500">QR ini dipakai sebagai signature_value saat approve step ini.</p>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('app.approvals.approve', $pendingApprovalForUser->id) }}" class="space-y-2 rounded-lg border border-success-200 p-3 dark:border-success-700/40">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('app.documents.show', $document->id) }}" />
                                    <textarea name="notes" rows="2" placeholder="Catatan approve (opsional)" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700"></textarea>
                                    @if($pendingApprovalForUser->workflowStep->is_required_signature)
                                        <input id="approval-signature-value-input" name="signature_value" type="hidden" value="{{ old('signature_value', '') }}" required />
                                        <p class="text-[11px] text-gray-500">Step ini wajib QR signature approval.</p>
                                    @endif
                                    <button type="submit" class="w-full rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-700">Approve Step Ini</button>
                                </form>

                                <form method="POST" action="{{ route('app.approvals.reject', $pendingApprovalForUser->id) }}" class="space-y-2 rounded-lg border border-error-200 p-3 dark:border-error-700/40">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('app.documents.show', $document->id) }}" />
                                    <textarea name="notes" rows="2" placeholder="Alasan reject" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700" required>{{ old('notes') }}</textarea>
                                    <button type="submit" class="w-full rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-700">Reject Step Ini</button>
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
                    $rejectedApproval = $approvals->first(fn($a) => ($a->status->value ?? $a->status) === 'REJECTED');
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

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Progress Approval</h4>
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        @if($currentStatus === 'COMPLETED')
                            Step {{ $workflowSteps->count() }} dari {{ $workflowSteps->count() }} - <span class="font-semibold text-success-600">Selesai</span>
                        @elseif($currentStatus === 'REJECTED')
                            Ditolak di Step {{ $currentStep }} dari {{ $workflowSteps->count() }}
                        @else
                            Step {{ $currentStep }} dari {{ $workflowSteps->count() }}
                        @endif
                    </span>
                </div>

                <div class="relative">
                    {{-- Progress Line --}}
                    <div class="absolute left-0 top-5 h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                    <div 
                        class="absolute left-0 top-5 h-0.5 bg-brand-500 transition-all duration-500"
                        style="width: {{ $workflowSteps->count() > 0 ? (($currentStatus === 'COMPLETED' ? $workflowSteps->count() : max(0, $currentStep - 1)) / $workflowSteps->count() * 100) : 0 }}%"
                    ></div>

                    {{-- Steps --}}
                    <div class="relative flex justify-between">
                        @foreach($workflowSteps as $step)
                            @php
                                $approval = $approvals->get($step->step_order);
                                $approvalStatus = $approval?->status?->value ?? $approval?->status;
                                
                                $isCompleted = in_array($approvalStatus, ['APPROVED', 'SKIPPED'], true);
                                $isRejected = $approvalStatus === 'REJECTED';
                                $isCurrent = $step->step_order === $currentStep && !$isCompleted && !$isRejected && $currentStatus !== 'COMPLETED';
                                $isPending = !$isCompleted && !$isRejected && !$isCurrent;
                            @endphp

                            <div class="flex flex-col items-center" style="flex: 1">
                                {{-- Step Circle --}}
                                <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 bg-white transition-all dark:bg-gray-900
                                    {{ $isCompleted ? 'border-success-500 bg-success-50 dark:bg-success-500/20' : '' }}
                                    {{ $isRejected ? 'border-error-500 bg-error-50 dark:bg-error-500/20' : '' }}
                                    {{ $isCurrent ? 'border-brand-500 bg-brand-50 shadow-lg shadow-brand-500/30 dark:bg-brand-500/20' : '' }}
                                    {{ $isPending ? 'border-gray-300 dark:border-gray-700' : '' }}
                                ">
                                    @if($isCompleted)
                                        <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($isRejected)
                                        <svg class="h-5 w-5 text-error-600 dark:text-error-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @else
                                        <span class="text-sm font-semibold {{ $isCurrent ? 'text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $step->step_order }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Step Label --}}
                                <div class="mt-2 text-center">
                                    <p class="text-xs font-medium {{ $isCurrent ? 'text-brand-600 dark:text-brand-400' : ($isCompleted ? 'text-success-600 dark:text-success-400' : ($isRejected ? 'text-error-600 dark:text-error-400' : 'text-gray-500 dark:text-gray-400')) }}">
                                        {{ $step->role->name ?? $step->role->code ?? 'Step '.$step->step_order }}
                                    </p>
                                    @if($approval)
                                        <p class="mt-0.5 text-[10px] text-gray-400">
                                            {{ $approval->approver->name ?? '-' }}
                                        </p>
                                        @if($approval->approved_at)
                                            <p class="mt-0.5 text-[10px] text-gray-400">
                                                {{ $approval->approved_at->format('d/m H:i') }}
                                            </p>
                                        @endif
                                        @if($isRejected && $approval->notes)
                                            <p class="mt-1 max-w-[120px] truncate text-[10px] text-error-500" title="{{ $approval->notes }}">
                                                {{ $approval->notes }}
                                            </p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $document->title }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Status: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $currentStatus }}</span></p>
                </div>

                @if($currentStatus === 'COMPLETED')
                    <a href="{{ route('app.documents.download', $document->id) }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">Download Dokumen Final</a>
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
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800 md:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Tanda Tangan Pengaju</p>
                    @if($document->submitter_signature)
                        <div id="submitter-signature-display" class="mt-2 flex items-center justify-center rounded-md bg-white p-2 dark:bg-white"></div>
                    @else
                        <p class="mt-1 text-sm text-gray-500">-</p>
                    @endif
                </div>

                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800 md:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Tanda Tangan Approval</p>
                    @php
                        $approvalSignatures = $document->approvals
                            ->sortBy('step_order')
                            ->flatMap(function ($approval) {
                                return $approval->signatures->map(function ($signature) use ($approval) {
                                    return [
                                        'signature_value' => $signature->signature_value,
                                        'signature_type' => $signature->signature_type->value ?? $signature->signature_type,
                                        'role_name' => $approval->workflowStep->role->name ?? ($approval->workflowStep->role->code ?? '-'),
                                        'approver_name' => $approval->approver->name ?? '-',
                                        'signed_at' => optional($signature->signed_at)->format('d/m/Y H:i') ?? '-',
                                    ];
                                });
                            });
                    @endphp

                    @if($approvalSignatures->isNotEmpty())
                        <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach($approvalSignatures as $item)
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $item['role_name'] }}</p>
                                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">{{ $item['signature_type'] }}</span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-500">{{ $item['approver_name'] }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $item['signed_at'] }}</p>
                                    <div
                                        class="approval-signature-display mt-2 flex items-center justify-center rounded-md bg-white p-2"
                                        data-approval-signature-value="{{ $item['signature_value'] }}"
                                    ></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-1 text-sm text-gray-500">Belum ada tanda tangan approval.</p>
                    @endif
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
                        <a href="{{ route('app.documents.attachments.preview', ['id' => $document->id, 'attachmentId' => $attachment->id]) }}" target="_blank" class="text-sm text-brand-600 hover:text-brand-700">Lihat</a>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">Belum ada lampiran.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    @if($currentStatus === 'DRAFT')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const generateBtn = document.getElementById('generate-signature-btn');
                const signatureInput = document.getElementById('signature-value-input');
                const signatureBox = document.getElementById('submitter-signature-box');
                const qrContainer = document.getElementById('submitter-signature-qr');
                const suggestedValue = @json($suggestedSignatureValue);

                if (!generateBtn || !signatureInput || !signatureBox || !qrContainer) {
                    return;
                }

                const drawQr = function (value) {
                    qrContainer.innerHTML = '';
                    new QRCode(qrContainer, {
                        text: value,
                        width: 140,
                        height: 140,
                        correctLevel: QRCode.CorrectLevel.M,
                    });
                    signatureBox.classList.remove('hidden');
                };

                if (signatureInput.value) {
                    drawQr(signatureInput.value);
                }

                generateBtn.addEventListener('click', function () {
                    signatureInput.value = suggestedValue;
                    drawQr(suggestedValue);
                });
            });
        </script>
    @endif

    @if($document->submitter_signature)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const displayContainer = document.getElementById('submitter-signature-display');
                if (!displayContainer) return;
                new QRCode(displayContainer, {
                    text: @json($document->submitter_signature),
                    width: 120,
                    height: 120,
                    correctLevel: QRCode.CorrectLevel.M,
                });
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const approvalQrs = document.querySelectorAll('.approval-signature-display[data-approval-signature-value]');
            if (!approvalQrs.length) return;

            approvalQrs.forEach(function (el) {
                const value = el.getAttribute('data-approval-signature-value');
                if (!value) return;

                el.innerHTML = '';
                new QRCode(el, {
                    text: value,
                    width: 110,
                    height: 110,
                    correctLevel: QRCode.CorrectLevel.M,
                });
            });
        });
    </script>

    @if(isset($pendingApprovalForUser) && $pendingApprovalForUser && $pendingApprovalForUser->workflowStep->is_required_signature)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const generateBtn = document.getElementById('generate-approval-signature-btn');
                const signatureInput = document.getElementById('approval-signature-value-input');
                const signatureBox = document.getElementById('approval-signature-box');
                const qrContainer = document.getElementById('approval-signature-qr');
                const suggestedValue = @json($suggestedApprovalSignatureValue);

                if (!generateBtn || !signatureInput || !signatureBox || !qrContainer || !suggestedValue) {
                    return;
                }

                const drawQr = function (value) {
                    qrContainer.innerHTML = '';
                    new QRCode(qrContainer, {
                        text: value,
                        width: 140,
                        height: 140,
                        correctLevel: QRCode.CorrectLevel.M,
                    });
                    signatureBox.classList.remove('hidden');
                };

                if (signatureInput.value) {
                    drawQr(signatureInput.value);
                }

                generateBtn.addEventListener('click', function () {
                    signatureInput.value = suggestedValue;
                    drawQr(suggestedValue);
                });
            });
        </script>
    @endif
@endpush
