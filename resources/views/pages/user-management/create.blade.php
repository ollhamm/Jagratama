@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Tambah User" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        @if($errors->any())
            <div class="mb-4 rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('app.users.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama <span class="text-red-500">*</span></label>
                    <input name="name" value="{{ old('name') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status <span class="text-red-500">*</span></label>
                    <select name="is_active" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                        <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Organisasi User (opsional)</label>
                <select name="organization_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90">
                    <option value="">-</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected(old('organization_id') === $organization->id)>{{ $organization->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Role + Organisasi Role: auto-select berdasarkan role yang dipilih --}}
            <div
                x-data="{
                    selectedRole: '{{ old('role_id') }}',
                    roleOrgMap: {{ Js::from($roleOrgMap) }},
                    get autoOrgId() {
                        return this.roleOrgMap[this.selectedRole] ?? '';
                    }
                }"
                class="space-y-4"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                    <select
                        name="role_id"
                        x-model="selectedRole"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90"
                        required
                    >
                        <option value="">-- Pilih Role --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Organisasi Role
                        <span class="ml-1 text-xs text-gray-400">(otomatis sesuai role)</span>
                    </label>
                    {{-- Hidden input yang dikirim ke server --}}
                    <input type="hidden" name="role_organization_id" :value="autoOrgId">
                    {{-- Select visual (disabled, hanya tampilan) --}}
                    <select
                        x-effect="$el.value = autoOrgId"
                        disabled
                        class="h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-500 cursor-not-allowed dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                    >
                        <option value="">-- Global --</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('app.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Batal</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Simpan</button>
            </div>
        </form>
    </div>
@endsection
