<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublishPageController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $isAdmin = $user->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();

        $pendingQuery = Document::query()
            ->with(['documentType', 'organization'])
            ->where('current_status', DocumentStatus::COMPLETED)
            ->whereNull('public_file_path');

        $historyQuery = Document::query()
            ->with(['documentType', 'organization'])
            ->whereNotNull('public_file_path');

        if (! $isAdmin) {
            $pendingQuery->where('created_by', $user->id);
            $historyQuery->where('created_by', $user->id);
        }

        $pending = $pendingQuery->latest('completed_at')->get();
        $history = $historyQuery->latest('published_at')->get();

        return view('pages.publish.index', [
            'title' => 'Publish Dokumen',
            'pending' => $pending,
            'history' => $history,
        ]);
    }

    public function publish(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'attachment' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $document = Document::query()
            ->where('id', $id)
            ->where('created_by', auth()->id())
            ->where('current_status', DocumentStatus::COMPLETED)
            ->whereNull('public_file_path')
            ->firstOrFail();

        $path = $request->file('attachment')->storeAs(
            'published',
            $document->id . '.pdf',
        );

        $document->update([
            'public_file_path' => $path,
            'published_at' => now(),
        ]);

        return redirect()->route('app.publish.index')
            ->with('success', 'Dokumen berhasil dipublish dan dapat diakses publik.');
    }
}
