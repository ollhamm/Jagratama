@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Buat Pengajuan" />

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

        <form method="POST" action="{{ route('app.documents.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            {{-- Admin: pilih pengaju --}}
            @if($isAdmin)
            <div
                x-data="{
                    selectedPengaju: '{{ old('on_behalf_of') }}',
                    orgsByUser: {{ Js::from($allOrgsByUser) }},
                    selectedOrgId: '{{ old('organization_id') }}',
                    get orgOptions() { return this.orgsByUser[this.selectedPengaju] ?? []; },
                    onPengajuChange() { this.selectedOrgId = ''; },
                }"
                class="rounded-lg border border-warning-200 bg-warning-50 p-4 space-y-3 dark:border-warning-700/40 dark:bg-warning-500/10"
            >
                <div>
                    <label class="mb-1 block text-sm font-semibold text-warning-700 dark:text-warning-400">
                        Dibuat atas nama (Pengaju) <span class="text-red-500">*</span>
                    </label>
                    <p class="mb-2 text-xs text-warning-600 dark:text-warning-400">Pilih user Pengaju yang akan menjadi pemilik dokumen ini.</p>
                    <select name="on_behalf_of" x-model="selectedPengaju" @change="onPengajuChange()" required
                        class="h-11 w-full rounded-lg border border-warning-300 bg-white px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-warning-600 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Pilih Pengaju --</option>
                        @foreach($pengajuUsers as $pUser)
                            <option value="{{ $pUser->id }}" @selected(old('on_behalf_of') === $pUser->id)>
                                {{ $pUser->name }} ({{ $pUser->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-warning-700 dark:text-warning-400">
                        Organisasi <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="organization_id" x-model="selectedOrgId">
                    <select
                        x-model="selectedOrgId"
                        :disabled="!selectedPengaju"
                        :required="!!selectedPengaju"
                        class="h-11 w-full rounded-lg border border-warning-300 bg-white px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-warning-600 dark:bg-gray-900 dark:text-white/90 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    >
                        <option value="" x-text="selectedPengaju ? '-- Pilih Organisasi --' : '-- Pilih pengaju dulu --'"></option>
                        <template x-for="org in orgOptions" :key="org.id">
                            <option :value="org.id" x-text="org.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            @else
            {{-- Pengaju biasa: organisasi dari user_roles, tidak bisa diubah --}}
            @if($pengajuOrganizations->count() === 1)
                <input type="hidden" name="organization_id" value="{{ $pengajuOrganizations->first()->id }}">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Organisasi</label>
                    <div class="h-11 flex items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        {{ $pengajuOrganizations->first()->name }}
                    </div>
                </div>
            @elseif($pengajuOrganizations->count() > 1)
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Organisasi <span class="text-red-500">*</span></label>
                    <select name="organization_id" required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Pilih Organisasi --</option>
                        @foreach($pengajuOrganizations as $org)
                            <option value="{{ $org->id }}" @selected(old('organization_id') === $org->id)>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700">
                    Akun Anda belum dikaitkan ke organisasi manapun. Hubungi administrator.
                </div>
            @endif
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Dokumen <span class="text-red-500">*</span></label>
                <input name="title" value="{{ old('title') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required />
            </div>

            @php
                $kakLpjCodes   = ['KAK', 'LPJ'];
                $docTypeMap    = $documentTypes->keyBy('id');
                $oldWorkflowId = old('workflow_id');

                $workflowGroups = ['KAK_LPJ' => [], 'SURAT' => []];
                foreach ($workflows as $wf) {
                    $code = strtoupper($docTypeMap->get($wf->document_type_id)?->code ?? '');
                    $cat  = in_array($code, $kakLpjCodes) ? 'KAK_LPJ' : ($code === 'SURAT' ? 'SURAT' : null);
                    if ($cat) {
                        $workflowGroups[$cat][] = ['id' => $wf->id, 'name' => $wf->name];
                    }
                }

                $oldCategory = '';
                if ($oldWorkflowId) {
                    foreach ($workflowGroups as $cat => $items) {
                        foreach ($items as $item) {
                            if ($item['id'] === $oldWorkflowId) { $oldCategory = $cat; break 2; }
                        }
                    }
                }
            @endphp

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Kategori Dokumen <span class="text-red-500">*</span>
                    </label>
                    <select id="category-select"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Pilih kategori --</option>
                        <option value="KAK_LPJ" @selected($oldCategory === 'KAK_LPJ')>KAK / LPJ</option>
                        <option value="SURAT"   @selected($oldCategory === 'SURAT')>Persuratan (Surat)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Tipe Dokumen <span class="text-red-500">*</span>
                    </label>
                    <select id="workflow-select" name="workflow_id" required data-no-select2
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">-- Pilih kategori dulu --</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lampiran Dokumen (wajib PDF, hanya satu file) <span class="text-red-500">*</span></label>
                <input type="file" name="attachment" accept=".pdf,application/pdf" required class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300" />
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('app.documents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Batal</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Berikutnya</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        const workflowData  = @json($workflowGroups);
        const oldWorkflowId = @json($oldWorkflowId ?? '');

        const $cat      = $('#category-select');
        const $workflow = $('#workflow-select');

        function initWorkflowSelect2() {
            if ($workflow.hasClass('select2-hidden-accessible')) {
                $workflow.select2('destroy');
            }
            const count = $workflow.find('option').length;
            $workflow.select2({
                width: '100%',
                minimumResultsForSearch: count > 6 ? 0 : Infinity,
                placeholder: $workflow.find('option[value=""]').text() || '',
                allowClear: false,
                dropdownParent: $('body'),
            });
        }

        function populateWorkflows(category) {
            $workflow.empty();

            const placeholderText = category ? '-- Pilih tipe surat --' : '-- Pilih kategori dulu --';
            $workflow.append(new Option(placeholderText, '', true, true));

            if (category && workflowData[category]) {
                workflowData[category].forEach(function (wf) {
                    const selected = wf.id === oldWorkflowId;
                    $workflow.append(new Option(wf.name, wf.id, selected, selected));
                });
            }

            initWorkflowSelect2();
        }

        $cat.on('change', function () {
            populateWorkflows($(this).val());
        });

        populateWorkflows($cat.val());
    });
</script>
@endpush
