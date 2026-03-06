@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $title ?? 'Dashboard' }}" />

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('app.documents.index') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Kelola Dokumen</a>
        @if ($mode === 'pengaju')
            <a href="{{ route('app.documents.create') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Buat Pengajuan</a>
        @else
            <a href="{{ route('app.approvals.pending') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Halaman Approval</a>
        @endif
    </div>

    @if ($mode === 'pengaju')
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pengajuan Saya</h3>
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <input
                        type="text"
                        name="my_search"
                        value="{{ request('my_search') }}"
                        placeholder="Cari judul dokumen"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90"
                    />
                    <select
                        name="my_status"
                        class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90"
                    >
                        <option value="">Semua Status</option>
                        @foreach (['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'REJECTED', 'APPROVED', 'COMPLETED'] as $status)
                            <option value="{{ $status }}" @selected(request('my_status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Cari</button>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Judul</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Tipe</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Step</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($mySubmissions as $item)
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->title }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item->documentType->code ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    @php($statusValue = $item->current_status->value ?? $item->current_status)
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ in_array($statusValue, ['COMPLETED', 'APPROVED']) ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : (in_array($statusValue, ['REJECTED']) ? 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300') }}">
                                        {{ $statusValue }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item->current_step_order }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">Belum ada pengajuan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $mySubmissions->appends(request()->query())->links() }}
            </div>
        </div>
    @endif

    @if ($mode === 'approver')
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Dokumen Menunggu Approval</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <input
                            type="text"
                            name="pending_search"
                            value="{{ request('pending_search') }}"
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
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Role</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Step</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($pendingApprovals as $item)
                                <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                    <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->document->title ?? '-' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item->workflowStep->role->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item->step_order }}</td>
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
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Riwayat Approval</h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <input
                            type="text"
                            name="history_search"
                            value="{{ request('history_search') }}"
                            placeholder="Cari riwayat"
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
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($approvalHistory as $item)
                                <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                    <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->document->title ?? '-' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                        @php($approvalStatus = $item->status->value ?? $item->status)
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $approvalStatus === 'APPROVED' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : ($approvalStatus === 'REJECTED' ? 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300') }}">
                                            {{ $approvalStatus }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ optional($item->approved_at)->format('d M Y H:i') }}</td>
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

                <div class="mt-4">{{ $approvalHistory->appends(request()->query())->links() }}</div>
            </div>
        </div>
    @endif
@endsection
