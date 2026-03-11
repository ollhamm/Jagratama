@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Daftar Pengajuan" />

    {{-- Modal Hapus Draft --}}
    

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        @if(session('success'))
            <div class="mb-4 rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('app.documents.index') }}" class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari judul dokumen"
                    class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90"
                />
                <select name="status" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Cari</button>
            </form>

            <a href="{{ route('app.documents.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">Buat Pengajuan</a>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-800/70">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Judul</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Tipe</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Organisasi</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($documents as $document)
                        <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                            <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ $document->title }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $document->documentType->code ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $document->organization->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                @php($statusValue = $document->current_status->value ?? $document->current_status)
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ in_array($statusValue, ['COMPLETED', 'APPROVED']) ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : (in_array($statusValue, ['REJECTED']) ? 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300') }}">
                                    {{ $statusValue }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-sm">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('app.documents.show', $document->id) }}" class="text-brand-600 hover:text-brand-700">Detail</a>
                                    @if(($document->current_status->value ?? $document->current_status) === 'DRAFT' && $document->created_by === auth()->id())
                                        <button
                                            type="button"
                                            class="text-error-600 hover:text-error-700"
                                            @click="$dispatch('open-delete-modal', { action: '{{ route('app.documents.destroy', $document->id) }}', title: '{{ addslashes($document->title) }}' })"
                                        >Hapus</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">Tidak ada data dokumen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-4">{{ $documents->appends(request()->query())->links() }}</div>
    </div>
@endsection

<div
    x-data="deleteModal()"
    x-show="open"
    x-cloak
    @keydown.escape.window="close()"
    class="fixed inset-0 z-50 flex items-center justify-center"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="close()"></div>

    {{-- Dialog --}}
    <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-error-100 dark:bg-error-500/20">
                <svg class="h-5 w-5 text-error-600 dark:text-error-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Hapus Draft Pengajuan</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus draft <strong x-text="docTitle" class="text-gray-800 dark:text-white"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <button @click="close()" type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Batal</button>
            <form :action="formAction" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-700">Hapus</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function deleteModal() {
        return {
            open: false,
            formAction: '',
            docTitle: '',
            init() {
                window.addEventListener('open-delete-modal', (e) => {
                    this.formAction = e.detail.action;
                    this.docTitle = e.detail.title;
                    this.open = true;
                });
            },
            close() {
                this.open = false;
            }
        };
    }
</script>
@endpush
