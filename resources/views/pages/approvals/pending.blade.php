@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Approval Dokumen" />

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        {{-- ===== APPROVAL AKTIF ===== --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Approval Aktif</h3>
                    <p class="text-sm text-gray-500">Pending: {{ $pendingApprovals->total() }} dokumen</p>
                </div>
            </div>

            @if($activeApproval)
                @php
                    $doc          = $activeApproval->document;
                    $attachment   = $doc->attachments->first();
                    $pdfUrl       = $attachment ? route('app.documents.attachments.pdf', ['id' => $doc->id, 'attachmentId' => $attachment->id]) : null;
                    $downloadUrl  = $attachment ? route('app.documents.attachments.preview', ['id' => $doc->id, 'attachmentId' => $attachment->id]) : null;

                    $latestInstance  = $doc->workflowInstances->sortByDesc('started_at')->first();
                    $workflowSteps   = $latestInstance?->workflow?->steps?->sortBy('step_order') ?? collect();
                    $approvals       = $doc->approvals->keyBy('step_order');
                    $currentStep     = $doc->current_step_order;
                    $currentStatus   = $doc->current_status->value ?? $doc->current_status;
                @endphp

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    {{-- PDF Preview --}}
                    <div class="xl:col-span-9">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900/50">
                            @if($pdfUrl)
                                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800">
                                    <span class="truncate text-xs font-medium text-gray-600 dark:text-gray-300">{{ basename($attachment->file_path) }}</span>
                                    @if($downloadUrl)
                                        <a href="{{ $downloadUrl }}" class="ml-3 shrink-0 rounded bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700">Download .docx</a>
                                    @endif
                                </div>
                                <iframe src="{{ $pdfUrl }}" class="h-[70vh] w-full" title="Preview Dokumen"></iframe>
                            @else
                                <div class="flex h-[40vh] items-center justify-center text-sm text-gray-500">
                                    Lampiran belum tersedia.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Panel kanan: info + approve/reject --}}
                    <div class="xl:col-span-3">
                        <div class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.02]">
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Dokumen</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</p>
                                <p class="mt-0.5 text-[11px] text-gray-500">{{ $doc->organization->name ?? '-' }}</p>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <p class="text-xs uppercase text-gray-500">Step Anda</p>
                                <p class="mt-1 text-xs font-medium text-gray-800 dark:text-white/90">
                                    {{ $activeApproval->workflowStep->role->name ?? '-' }} (Step {{ $activeApproval->step_order }})
                                </p>
                            </div>

                            {{-- Form Approve --}}
                            <form id="approve-form" method="POST" action="{{ route('app.approvals.approve', $activeApproval->id) }}" class="space-y-2 rounded-lg border border-success-200 p-3 dark:border-success-700/40">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ route('app.approvals.pending') }}" />
                                <textarea name="notes" rows="2" placeholder="Catatan approve (opsional)" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700"></textarea>

                                @if($activeApproval->workflowStep->is_required_signature)
                                    <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                        <p class="text-xs font-medium uppercase text-gray-500">Tanda Tangan <span class="text-red-500">*</span></p>
                                        <div id="sig-thumb-wrap" class="hidden">
                                            <img id="sig-thumb" src="#" alt="TTD" class="mx-auto max-h-14 rounded border border-gray-200 bg-white">
                                        </div>
                                        <button type="button" id="open-sig-modal"
                                            class="w-full rounded-lg border-2 border-dashed border-brand-300 py-2 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                            Buka Pad Tanda Tangan
                                        </button>
                                        <input type="hidden" name="signature_value" id="approval-signature-value-input">
                                        <p id="sig-error" class="hidden text-xs text-red-500">Tanda tangan wajib diisi.</p>
                                    </div>
                                @endif

                                <button type="submit" class="w-full rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-700">Approve</button>
                            </form>

                            {{-- Form Reject --}}
                            <form method="POST" action="{{ route('app.approvals.reject', $activeApproval->id) }}" class="space-y-2 rounded-lg border border-error-200 p-3 dark:border-error-700/40">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ route('app.approvals.pending') }}" />
                                <textarea name="notes" rows="2" placeholder="Alasan reject" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs dark:border-gray-700" required></textarea>
                                <button type="submit" class="w-full rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-700">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Step Progress (sama persis seperti show.blade.php) --}}
                @if($workflowSteps->isNotEmpty())
                    <div class="mt-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Progress Approval</h4>
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                @if($currentStatus === 'COMPLETED')
                                    Step {{ $workflowSteps->count() }} dari {{ $workflowSteps->count() }} — <span class="font-semibold text-success-600">Selesai</span>
                                @else
                                    Step {{ $currentStep }} dari {{ $workflowSteps->count() }}
                                @endif
                            </span>
                        </div>

                        <div class="relative">
                            <div class="absolute left-0 top-5 h-0.5 w-full bg-gray-200 dark:bg-gray-700"></div>
                            <div class="absolute left-0 top-5 h-0.5 bg-brand-500 transition-all duration-500"
                                style="width: {{ $workflowSteps->count() > 0 ? (($currentStatus === 'COMPLETED' ? $workflowSteps->count() : max(0, $currentStep - 1)) / $workflowSteps->count() * 100) : 0 }}%">
                            </div>

                            <div class="relative flex justify-between">
                                @foreach($workflowSteps as $step)
                                    @php
                                        $approval       = $approvals->get($step->step_order);
                                        $approvalStatus = $approval?->status?->value ?? $approval?->status;
                                        $isCompleted    = in_array($approvalStatus, ['APPROVED', 'SKIPPED'], true);
                                        $isRejected     = $approvalStatus === 'REJECTED';
                                        $isCurrent      = $step->step_order === $currentStep && !$isCompleted && !$isRejected && $currentStatus !== 'COMPLETED';
                                        $isPending      = !$isCompleted && !$isRejected && !$isCurrent;
                                    @endphp
                                    <div class="flex flex-col items-center" style="flex:1">
                                        <div class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 bg-white transition-all dark:bg-gray-900
                                            {{ $isCompleted ? 'border-success-500 bg-success-50 dark:bg-success-500/20' : '' }}
                                            {{ $isRejected  ? 'border-error-500 bg-error-50 dark:bg-error-500/20' : '' }}
                                            {{ $isCurrent   ? 'border-brand-500 bg-brand-50 shadow-lg shadow-brand-500/30 dark:bg-brand-500/20' : '' }}
                                            {{ $isPending   ? 'border-gray-300 dark:border-gray-700' : '' }}">
                                            @if($isCompleted)
                                                <svg class="h-5 w-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @elseif($isRejected)
                                                <svg class="h-5 w-5 text-error-600 dark:text-error-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            @else
                                                <span class="text-sm font-semibold {{ $isCurrent ? 'text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $step->step_order }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-2 text-center">
                                            <p class="text-xs font-medium {{ $isCurrent ? 'text-brand-600 dark:text-brand-400' : ($isCompleted ? 'text-success-600 dark:text-success-400' : ($isRejected ? 'text-error-600 dark:text-error-400' : 'text-gray-500 dark:text-gray-400')) }}">
                                                {{ $step->role->name ?? $step->role->code ?? 'Step '.$step->step_order }}
                                            </p>
                                            @if($approval?->approved_at)
                                                <p class="mt-0.5 text-[10px] text-gray-400">{{ $approval->approved_at->format('d/m H:i') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            @else
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-700">
                    Tidak ada approval pending.
                </div>
            @endif
        </div>

        {{-- ===== RIWAYAT APPROVAL ===== --}}
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
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($historyApprovals as $approval)
                                <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                    <td class="px-3 py-2 text-sm text-gray-800 dark:text-white/90">
                                        {{ $approval->document->title ?? '-' }}
                                        <p class="text-[11px] text-gray-400">{{ $approval->document->organization->name ?? '' }}</p>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                        @php($approvalStatus = $approval->status->value ?? $approval->status)
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                                            {{ $approvalStatus === 'APPROVED' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : ($approvalStatus === 'REJECTED' ? 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300') }}">
                                            {{ $approvalStatus }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ optional($approval->approved_at)->format('d M Y H:i') }}</td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('app.documents.show', $approval->document_id) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-brand-300 px-3 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50 dark:border-brand-500/40 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500">Belum ada riwayat approval.</td>
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

@push('modals')
    @if(isset($activeApproval) && $activeApproval && $activeApproval->workflowStep->is_required_signature)
    <div id="sig-modal" class="fixed hidden items-center justify-center bg-gray-900/60 p-4" style="inset:0; z-index:9999999; position:fixed;">
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900" x-data="{ sigTab: 'draw' }">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tanda Tangan</h3>
                <button type="button" id="close-sig-modal" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex gap-2 px-6 pt-4">
                <button type="button" @click="sigTab='draw'"
                    :class="sigTab==='draw' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600'"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition">Gambar</button>
                <button type="button" @click="sigTab='upload'"
                    :class="sigTab==='upload' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600'"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition">Upload Gambar</button>
            </div>

            <div class="px-6 py-4">
                <div x-show="sigTab === 'draw'">
                    <canvas id="approval-sig-canvas"
                        class="w-full rounded-xl border-2 border-dashed border-gray-300 bg-white dark:border-gray-600"
                        height="300" style="touch-action:none; cursor:crosshair; display:block;"></canvas>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Gunakan mouse atau jari untuk menggambar tanda tangan</p>
                        <button type="button" id="clear-approval-sig" class="text-xs text-red-400 hover:text-red-600">Hapus</button>
                    </div>
                </div>

                <div x-show="sigTab === 'upload'" class="space-y-3">
                    <div class="flex h-48 flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800">
                        <p class="text-sm text-gray-500">Pilih gambar tanda tangan</p>
                        <label class="mt-3 cursor-pointer rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                            Pilih File
                            <input type="file" id="approval-sig-upload" accept="image/*" class="hidden">
                        </label>
                    </div>
                    <img id="approval-sig-preview" src="#" alt="Preview" class="hidden mx-auto max-h-40 rounded-xl border border-gray-200 bg-white">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <button type="button" id="close-sig-modal-cancel"
                    class="rounded-lg border border-gray-300 px-5 py-2 text-sm text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="button" id="confirm-sig"
                    class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">Gunakan Tanda Tangan</button>
            </div>
        </div>
    </div>
    @endif
@endpush

@push('scripts')
    @if(isset($activeApproval) && $activeApproval && $activeApproval->workflowStep->is_required_signature)
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
                signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'rgb(0,0,0)' });
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width  = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad.clear();
            }

            function openModal()  { modal.classList.remove('hidden'); modal.classList.add('flex'); requestAnimationFrame(initPad); }
            function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

            openBtn?.addEventListener('click', openModal);
            closeBtn?.addEventListener('click', closeModal);
            closeBtnCancel?.addEventListener('click', closeModal);
            modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

            clearBtn?.addEventListener('click', () => {
                signaturePad?.clear();
                if (uploadInput) uploadInput.value = '';
                if (previewImg)  { previewImg.src = '#'; previewImg.classList.add('hidden'); }
            });

            uploadInput?.addEventListener('change', e => {
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
                if (sigThumb && sigThumbWrap) {
                    sigThumb.src = value;
                    sigThumbWrap.classList.remove('hidden');
                    if (openBtn) openBtn.textContent = 'Ubah Tanda Tangan';
                }
                sigError?.classList.add('hidden');
                closeModal();
            });

            approveForm?.addEventListener('submit', e => {
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
