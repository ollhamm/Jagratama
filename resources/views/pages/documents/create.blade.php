@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Buat Dokumen" />

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
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Judul Dokumen</label>
                <input name="title" value="{{ old('title') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe Dokumen</label>
                    <select name="document_type_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required>
                        <option value="">Pilih tipe</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('document_type_id') === $type->id)>{{ $type->code }} - {{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Organisasi</label>
                    <select name="organization_id" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 outline-hidden focus:border-brand-500 dark:border-gray-700 dark:text-white/90" required>
                        <option value="">Pilih organisasi</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected(old('organization_id') === $organization->id)>{{ $organization->name }} ({{ $organization->type->value ?? $organization->type }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Lampiran Dokumen (boleh lebih dari satu)</label>
                <input type="file" name="attachments[]" multiple class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-300" />
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('app.documents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">Batal</a>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Simpan</button>
            </div>
        </form>
    </div>
@endsection
