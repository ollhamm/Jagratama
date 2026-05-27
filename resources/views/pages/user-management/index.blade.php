@extends('layouts.app')

@section('content')
    <div x-data="{ deleteAction: '', deleteUserName: '' }">
    <x-common.page-breadcrumb pageTitle="User Management" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        @if(session('success'))
            <div class="mb-4 rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        {{-- Filter + Tambah --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('app.users.index') }}" class="flex flex-col gap-2 sm:flex-row sm:flex-nowrap sm:items-center">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama/email"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90 sm:w-44"
                />
                <div class="w-full sm:w-32">
                    <select name="is_active" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua Status</option>
                        <option value="1" @selected(request('is_active') === '1')>Active</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="w-full sm:w-44">
                    <select name="organization_id" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="">Semua Organisasi</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected(request('organization_id') === $organization->id)>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="h-10 w-full rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600 sm:w-auto">Filter</button>
                @if(request()->hasAny(['search', 'is_active', 'organization_id']))
                    <a href="{{ route('app.users.index') }}" class="h-10 w-full rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5 flex items-center justify-center sm:w-auto">Reset</a>
                @endif
            </form>

            <a href="{{ route('app.users.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 sm:w-auto w-full">
                + Tambah User
            </a>
        </div>

        {{-- Tabel --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Nama</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Email</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Role</th>
                            <th class="hidden px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500 md:table-cell">Organisasi</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($users as $user)
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90 whitespace-nowrap">
                                    {{ $user->name }}
                                    <p class="text-[11px] text-gray-400 md:hidden">{{ $user->organization->name ?? '-' }}</p>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $user->email }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->userRoles as $userRole)
                                            <span class="inline-flex rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300 whitespace-nowrap">
                                                {{ $userRole->role->code ?? '-' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="hidden px-3 py-2 text-sm text-gray-600 dark:text-gray-300 md:table-cell whitespace-nowrap">{{ $user->organization->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->is_active ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' }}">
                                        {{ $user->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('app.users.edit', $user->id) }}" class="text-brand-600 hover:text-brand-700">Edit</a>

                                        @if(in_array($user->id, $deletableUserIds ?? [], true))
                                            <button
                                                type="button"
                                                class="text-error-600 hover:text-error-700"
                                                data-action="{{ route('app.users.destroy', $user->id) }}"
                                                data-name="{{ $user->name }}"
                                                @click="deleteAction = $el.dataset.action; deleteUserName = $el.dataset.name; $dispatch('open-user-delete-modal')"
                                            >
                                                Hapus
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">Belum ada user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>

        <div class="mt-4">{{ $users->appends(request()->query())->links() }}</div>
    </div>

    <x-ui.modal x-data="{ open: false }" @open-user-delete-modal.window="open = true" :isOpen="false" class="max-w-md">
        <div class="p-6 sm:p-7">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Hapus User</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Yakin ingin menghapus user
                <span class="font-semibold text-gray-800 dark:text-white/90" x-text="deleteUserName"></span>?
                Tindakan ini tidak bisa dibatalkan.
            </p>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    @click="open = false"
                >
                    Batal
                </button>

                <form method="POST" :action="deleteAction" @submit="open = false">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-error-600 px-4 py-2 text-sm font-medium text-white hover:bg-error-700">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </x-ui.modal>
    </div>
@endsection
