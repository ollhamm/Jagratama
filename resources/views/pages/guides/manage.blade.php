@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Kelola Panduan" />

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6"
        x-data="{ activeTab: 'pengaju' }">
        @if(session('success'))
            <div class="mb-4 rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-lg bg-error-50 px-4 py-3 text-sm text-error-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Tab switcher --}}
        <div class="mb-5 flex gap-2 border-b border-gray-200 dark:border-gray-800">
            @foreach(['pengaju' => 'Pengaju', 'approval' => 'Approval', 'admin' => 'Admin'] as $key => $label)
                <button type="button" @click="activeTab = '{{ $key }}'"
                    :class="activeTab === '{{ $key }}' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                    class="-mb-px border-b-2 px-4 py-2 text-sm font-medium transition">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Tab content --}}
        @foreach(['pengaju', 'approval', 'admin'] as $key)
            @php($guide = $guides->get($key))
            <div x-show="activeTab === '{{ $key }}'" x-cloak>
                <form method="POST" action="{{ route('app.guides.update', $key) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Panduan <span class="text-red-500">*</span></label>
                        <input name="title" value="{{ old('title', $guide->title ?? '') }}" required
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Konten Panduan</label>
                        <div id="editor-{{ $key }}" class="quill-editor-container rounded-lg border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900" style="min-height: 320px;">{!! old('content', $guide->content ?? '') !!}</div>
                        <textarea name="content" id="content-input-{{ $key }}" class="hidden"></textarea>
                    </div>

                    @if($guide?->updated_at)
                        <p class="text-xs text-gray-400">Terakhir diperbarui: {{ $guide->updated_at->format('d/m/Y H:i') }}@if($guide->updater) oleh {{ $guide->updater->name }}@endif</p>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600">
                            Simpan Panduan {{ ucfirst($key) }}
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
    .quill-editor-container .ql-editor { min-height: 280px; }
    .dark .ql-toolbar { background: #1f2937; border-color: #374151 !important; }
    .dark .ql-toolbar .ql-stroke { stroke: #d1d5db; }
    .dark .ql-toolbar .ql-fill { fill: #d1d5db; }
    .dark .ql-toolbar .ql-picker-label { color: #d1d5db; }
    .dark .ql-container { border-color: #374151 !important; color: #fff; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const keys = ['pengaju', 'approval', 'admin'];
        keys.forEach(function (key) {
            const editorEl = document.getElementById('editor-' + key);
            const input = document.getElementById('content-input-' + key);
            if (!editorEl || !input) return;

            const initialHtml = editorEl.innerHTML;
            editorEl.innerHTML = '';

            const quill = new Quill(editorEl, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link'],
                        ['clean'],
                    ],
                },
            });

            quill.clipboard.dangerouslyPasteHTML(initialHtml);
            input.value = initialHtml;

            quill.on('text-change', function () {
                input.value = quill.root.innerHTML;
            });

            // Pastikan konten ter-sync persis sebelum form submit
            const form = editorEl.closest('form');
            form?.addEventListener('submit', function () {
                input.value = quill.root.innerHTML;
            });
        });
    });
</script>
@endpush
