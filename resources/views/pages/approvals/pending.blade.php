@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Approval Dokumen" />

    <div class="space-y-6">
        @php
            $activeApproval = $pendingApprovals->first();
            $activeAttachment = $activeApproval?->document?->attachments?->first();
            $activeAttachmentUrl = $activeApproval && $activeAttachment
                ? route('app.documents.attachments.preview', ['id' => $activeApproval->document_id, 'attachmentId' => $activeAttachment->id])
                : null;

            $suggestedApprovalSignatureValue = null;
            if ($activeApproval) {
                $approvalSignaturePayload = [
                    'issuer' => 'JAGRATAMA',
                    'version' => 1,
                    'signature_scope' => 'approval',
                    'signature_id' => hash('sha256', implode('|', [
                        $activeApproval->document_id,
                        $activeApproval->id,
                        (string) auth()->id(),
                        (string) config('app.key'),
                    ])),
                    'document_id' => $activeApproval->document_id,
                    'approval_id' => $activeApproval->id,
                    'step_order' => $activeApproval->step_order,
                    'role' => $activeApproval->workflowStep->role->code ?? '-',
                    'title' => $activeApproval->document->title ?? '-',
                    'organization' => $activeApproval->document->organization->name ?? '-',
                    'approver' => auth()->user()->name ?? '-',
                    'created_at' => now()->toIso8601String(),
                ];

                $suggestedApprovalSignatureValue = base64_encode(json_encode($approvalSignaturePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        @endphp

        @if(session('success'))
            <div class="rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Approval Aktif</h3>
                    <p class="text-sm text-gray-500">Pending: {{ $pendingApprovals->total() }} dokumen</p>
                </div>
            </div>

            @if($activeApproval)
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-9">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            @if($activeAttachmentUrl)
                                <iframe
                                    src="{{ $activeAttachmentUrl }}#toolbar=1&navpanes=0"
                                    class="h-[70vh] w-full"
                                    title="Preview Dokumen Approval"
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
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Dokumen</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">{{ $activeApproval->document->title ?? '-' }}</p>
                                <p class="mt-1 text-[11px] text-gray-500">{{ $activeApproval->document->organization->name ?? '-' }}</p>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Step Anda</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">
                                    {{ $activeApproval->workflowStep->role->name ?? '-' }} (Step {{ $activeApproval->step_order }})
                                </p>
                            </div>

                            @if($activeApproval->workflowStep->is_required_signature)
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                    <p class="text-xs uppercase text-gray-500">Signature Approval</p>
                                    <button id="generate-approval-signature-btn" type="button" class="mt-2 w-full rounded-lg border border-brand-300 px-3 py-2 text-xs font-medium text-brand-700 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-300 dark:hover:bg-brand-500/10">
                                        Generate QR Approval
                                    </button>
                                </div>
                                <div id="approval-signature-box" class="hidden rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                    <p class="text-xs uppercase text-gray-500">QR Approver (Auto)</p>
                                    <div id="approval-signature-qr" class="mt-2 flex items-center justify-center rounded-md bg-white p-2"></div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('app.approvals.approve', $activeApproval->id) }}" class="space-y-2 rounded-lg border border-success-200 p-3 dark:border-success-700/40">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ route('app.approvals.pending') }}" />
                                <textarea name="notes" rows="2" placeholder="Catatan approve (opsional)" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700"></textarea>
                                @if($activeApproval->workflowStep->is_required_signature)
                                    <input id="approval-signature-value-input" name="signature_value" type="hidden" required />
                                    <p class="text-[11px] text-gray-500">Step ini wajib QR signature approval.</p>
                                @endif
                                <button type="submit" class="w-full rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-700">Approve</button>
                            </form>

                            <form method="POST" action="{{ route('app.approvals.reject', $activeApproval->id) }}" class="space-y-2 rounded-lg border border-error-200 p-3 dark:border-error-700/40">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ route('app.approvals.pending') }}" />
                                <textarea name="notes" rows="2" placeholder="Alasan reject" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700" required></textarea>
                                <button type="submit" class="w-full rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-700">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700">
                    Tidak ada approval pending.
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Riwayat Approval</h3>
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Dokumen</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($historyApprovals as $approval)
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                <td class="px-3 py-2 text-sm text-gray-800 dark:text-white/90">{{ $approval->document->title ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    @php($approvalStatus = $approval->status->value ?? $approval->status)
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $approvalStatus === 'APPROVED' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : ($approvalStatus === 'REJECTED' ? 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300') }}">
                                        {{ $approvalStatus }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ optional($approval->approved_at)->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-8 text-center text-sm text-gray-500">Belum ada riwayat approval.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-4">{{ $historyApprovals->appends(request()->query())->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
    @if($activeApproval && $activeApproval->workflowStep->is_required_signature)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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

                generateBtn.addEventListener('click', function () {
                    signatureInput.value = suggestedValue;
                    drawQr(suggestedValue);
                });
            });
        </script>
    @endif
@endpush
