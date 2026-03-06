@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="User Management" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        @if(session('success'))
            <div class="mb-4 rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">{{ session('error') }}</div>
        @endif

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('app.users.index') }}" class="flex flex-wrap items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama/email"
                    class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90"
                />
                <select name="is_active" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                    <option value="">Semua Status</option>
                    <option value="1" @selected(request('is_active') === '1')>Active</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                </select>
                <select name="organization_id" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-700 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                    <option value="">Semua Organisasi</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected(request('organization_id') === $organization->id)>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Filter</button>
            </form>

            <a href="{{ route('app.users.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900">Tambah User</a>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Nama</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Email</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Role</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Organisasi</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($users as $user)
                            <tr class="bg-white transition-colors hover:bg-gray-50 dark:bg-transparent dark:hover:bg-white/5">
                                <td class="px-3 py-2 text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->userRoles as $userRole)
                                            <span class="inline-flex rounded-full bg-brand-100 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                                {{ $userRole->role->code ?? '-' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $user->organization->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->is_active ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-error-100 text-error-700 dark:bg-error-500/20 dark:text-error-300' }}">
                                        {{ $user->is_active ? 'ACTIVE' : 'INACTIVE' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    <a href="{{ route('app.users.edit', $user->id) }}" class="text-brand-600 hover:text-brand-700">Edit</a>
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
        </div>

        <div class="mt-4">{{ $users->appends(request()->query())->links() }}</div>
    </div>
@endsection
