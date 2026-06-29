@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Selamat Datang, {{ $user->name }}!" />

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('app.documents.index') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Kelola Dokumen</a>
        @if ($mode === 'pengaju')
            <a href="{{ route('app.documents.create') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300"> + Buat Pengajuan</a>
        @else
            <a href="{{ route('app.approvals.pending') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Halaman Approval</a>
        @endif
        <a href="{{ route('app.documents.published') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Dokumen Resmi</a>
    </div>

    {{-- ===== SUMMARY CARDS ===== --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($mode === 'pengaju')
            <x-dashboard.stat-card label="Total Pengajuan" :value="$summary['total']" icon="file-text" color="brand" />
            <x-dashboard.stat-card label="Disetujui" :value="$summary['completed']" icon="check-circle" color="success" />
            <x-dashboard.stat-card label="Ditolak" :value="$summary['rejected']" icon="x-circle" color="error" />
            <x-dashboard.stat-card label="Dalam Proses" :value="$summary['in_review']" icon="clock" color="warning" />
        @elseif ($mode === 'approver')
            <x-dashboard.stat-card label="Menunggu Approval Saya" :value="$summary['pending']" icon="clock" color="warning" />
            <x-dashboard.stat-card label="Sudah Disetujui" :value="$summary['approved']" icon="check-circle" color="success" />
            <x-dashboard.stat-card label="Sudah Ditolak" :value="$summary['rejected']" icon="x-circle" color="error" />
            <x-dashboard.stat-card label="Notifikasi Belum Dibaca" :value="\App\Models\SystemNotification::where('user_id', $user->id)->whereNull('read_at')->count()" icon="bell" color="brand" />
        @else
            <x-dashboard.stat-card label="Total Dokumen" :value="$summary['total_documents']" icon="file-text" color="brand" />
            <x-dashboard.stat-card label="User Aktif" :value="$summary['active_users']" icon="users" color="success" />
            <x-dashboard.stat-card label="Menunggu Approval" :value="$summary['pending_approvals']" icon="clock" color="warning" />
            <x-dashboard.stat-card label="Sudah Dipublikasikan" :value="$summary['published']" icon="check-circle" color="success" />
        @endif
    </div>

    {{-- ===== AKTIVITAS TERBARU ===== --}}
    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- Notifikasi Terbaru --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Notifikasi Terbaru</h3>
            <div class="space-y-3">
                @forelse($recentNotifications as $notif)
                    <a href="{{ $notif->document_id ? route('app.documents.show', $notif->document_id) : '#' }}"
                        class="block rounded-lg border border-gray-100 p-2 text-xs hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                        <p class="text-gray-700 dark:text-gray-300">{{ $notif->message }}</p>
                        <p class="mt-1 text-[10px] text-gray-400">{{ $notif->created_at?->diffForHumans() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-500">Belum ada notifikasi.</p>
                @endforelse
            </div>
        </div>

        @if ($mode === 'pengaju')
            {{-- 5 Pengajuan Terakhir --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-2">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">5 Pengajuan Terakhir</h3>
                <div class="space-y-2">
                    @forelse($recentSubmissions as $doc)
                        @php($statusValue = $doc->current_status->value ?? $doc->current_status)
                        <a href="{{ route('app.documents.show', $doc->id) }}" class="flex items-center justify-between rounded-lg border border-gray-100 p-2 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</span>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ in_array($statusValue, ['COMPLETED', 'APPROVED']) ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : (in_array($statusValue, ['REJECTED']) ? 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300') }}">{{ $statusValue }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada pengajuan.</p>
                    @endforelse
                </div>
            </div>
        @else
            {{-- Approve/Reject Terbaru (oleh saya) --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-2">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Approve / Reject Terbaru</h3>
                <div class="space-y-2">
                    @forelse($recentApprovals as $approval)
                        @php($approvalStatus = $approval->status->value ?? $approval->status)
                        <a href="{{ route('app.documents.show', $approval->document_id) }}" class="flex items-center justify-between rounded-lg border border-gray-100 p-2 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ $approval->document->title ?? '-' }}</span>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $approvalStatus === 'APPROVED' ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' }}">{{ $approvalStatus }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada riwayat approval.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    @if ($mode === 'admin')
        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- 5 Pengajuan Terakhir (system-wide) --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">5 Pengajuan Terakhir</h3>
                <div class="space-y-2">
                    @forelse($recentSubmissions as $doc)
                        <a href="{{ route('app.documents.show', $doc->id) }}" class="flex items-center justify-between rounded-lg border border-gray-100 p-2 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                            <span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $doc->creator->name ?? '-' }}</span>
                            </span>
                            <span class="text-[11px] text-gray-400">{{ optional($doc->created_at)->format('d/m H:i') }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada pengajuan.</p>
                    @endforelse
                </div>
            </div>

            {{-- 5 Dokumen Dipublikasikan Terakhir --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Publish Terbaru</h3>
                <div class="space-y-2">
                    @forelse($recentPublished as $doc)
                        <a href="{{ route('public.document.show', $doc->id) }}" target="_blank" class="flex items-center justify-between rounded-lg border border-gray-100 p-2 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                            <span>
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $doc->title }}</span>
                                <span class="block text-[11px] text-gray-400">{{ $doc->creator->name ?? '-' }}</span>
                            </span>
                            <span class="text-[11px] text-gray-400">{{ optional($doc->published_at)->format('d/m H:i') }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada dokumen yang dipublikasikan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection
