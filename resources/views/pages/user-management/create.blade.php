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
                <select name="organization_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">-</option>
                    @foreach($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected(old('organization_id') === $organization->id)>{{ $organization->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                <select id="role-select" name="role_id" required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">-- Pilih Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') === $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Lingkup Jabatan
                    <span id="role-org-hint" class="ml-1 text-xs text-gray-400">(pilih role dulu)</span>
                </label>
                <select id="role-org-select" name="role_organization_id" data-no-select2
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">-- Pilih role dulu --</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('app.users.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Batal</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Simpan</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const roleOrgMap  = @json($roleOrgMap);
    const oldOrgId    = @json(old('role_organization_id') ?? '');
    const $roleSelect = $('#role-select');
    const $orgSelect  = $('#role-org-select');
    const $hint       = $('#role-org-hint');

    function initOrgSelect2() {
        if ($orgSelect.hasClass('select2-hidden-accessible')) {
            $orgSelect.select2('destroy');
        }
        const count = $orgSelect.find('option').length;
        $orgSelect.select2({
            width: '100%',
            minimumResultsForSearch: count > 6 ? 0 : Infinity,
            placeholder: $orgSelect.find('option[value=""]').text() || '',
            allowClear: false,
            dropdownParent: $('body'),
        });
    }

    function updateOrgSelect(roleId, preselect) {
        const entry = roleOrgMap[roleId];
        $orgSelect.empty();

        if (!entry || entry.global) {
            $hint.text('(global — tidak perlu dipilih)');
            $orgSelect.append('<option value="">-- Global --</option>');
            $orgSelect.prop('disabled', true).prop('required', false);
            initOrgSelect2();
            return;
        }

        $hint.text('(wajib dipilih)');
        $orgSelect.append('<option value="">-- Pilih Organisasi --</option>');
        $.each(entry.orgs, function (_, org) {
            $orgSelect.append(new Option(org.name, org.id, false, org.id === preselect));
        });
        $orgSelect.prop('disabled', false).prop('required', true);
        initOrgSelect2();
    }

    $roleSelect.on('change', function () {
        updateOrgSelect($(this).val(), '');
    });

    updateOrgSelect($roleSelect.val(), oldOrgId);
});
</script>
@endpush
