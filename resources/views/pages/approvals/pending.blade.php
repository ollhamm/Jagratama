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

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Dokumen Menunggu Approval</h3>
                <form method="GET" action="{{ route('app.approvals.pending') }}" class="flex items-center gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari dokumen"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90"
                    />
                    <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Cari</button>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Dokumen</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Step</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($pendingApprovals as $approval)
                            <tr class="bg-white align-top transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                <td class="px-3 py-2 text-sm text-gray-800 dark:text-white/90">
                                    {{ $approval->document->title ?? '-' }}
                                    <div class="text-xs text-gray-500">{{ $approval->document->organization->name ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $approval->workflowStep->role->name ?? '-' }} (Step {{ $approval->step_order }})
                                </td>
                                <td class="px-3 py-2">
                                    <div class="grid gap-2 md:grid-cols-2">
                                        <form method="POST" action="{{ route('app.approvals.approve', $approval->id) }}" class="space-y-2 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                            @csrf
                                            <input type="text" name="notes" placeholder="Catatan approve (opsional)" class="h-9 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-xs dark:border-gray-700" />
                                            @if($approval->workflowStep->is_required_signature)
                                                <input type="text" name="signature_value" placeholder="Signature barcode value" class="h-9 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-xs dark:border-gray-700" required />
                                            @endif
                                            <button type="submit" class="w-full rounded-lg bg-success-600 px-3 py-2 text-xs font-medium text-white hover:bg-success-700">Approve</button>
                                        </form>

                                        <form method="POST" action="{{ route('app.approvals.reject', $approval->id) }}" class="space-y-2 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                            @csrf
                                            <input type="text" name="notes" placeholder="Alasan reject" class="h-9 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-xs dark:border-gray-700" required />
                                            <button type="submit" class="w-full rounded-lg bg-error-600 px-3 py-2 text-xs font-medium text-white hover:bg-error-700">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-8 text-center text-sm text-gray-500">Tidak ada approval pending.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            <div class="mt-4">{{ $pendingApprovals->appends(request()->query())->links() }}</div>
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
