<?php

namespace App\Http\Controllers\Web;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublishPageController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $isAdmin = $user->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();

        $isKomisiB = $isAdmin || $user->userRoles()
            ->whereHas('role', fn ($q) => $q->where('code', 'KOMISI_B_BLM'))
            ->exists();

        $isPengaju = $isAdmin || $user->userRoles()
            ->whereHas('role', fn ($q) => $q->where('code', 'PENGAJU'))
            ->exists();

        $data = [
            'title'     => 'Publish Dokumen',
            'isKomisiB' => $isKomisiB,
            'isPengaju' => $isPengaju,
            // default empty
            'readyToUpload'  => collect(),
            'rejected'       => collect(),
            'pendingReview'  => collect(),
            'history'        => collect(),
            'reviewQueue'    => collect(),
            'reviewHistory'  => collect(),
        ];

        // ── Data untuk Pengaju ──────────────────────────────────────────────
        if ($isPengaju) {
            $base = fn () => Document::query()
                ->with(['documentType', 'organization'])
                ->where('current_status', DocumentStatus::COMPLETED)
                ->when(! $isAdmin, fn ($q) => $q->where('created_by', $user->id));

            // Siap upload (belum pernah submit ke publish)
            $data['readyToUpload'] = $base()
                ->whereNull('public_file_path')
                ->whereNull('publish_status')
                ->latest('completed_at')
                ->get();

            // Ditolak Komisi B → bisa upload ulang
            $data['rejected'] = $base()
                ->where('publish_status', 'REJECTED')
                ->latest('created_at')
                ->get();

            // Sedang menunggu review Komisi B
            $data['pendingReview'] = $base()
                ->where('publish_status', 'PENDING_REVIEW')
                ->latest('created_at')
                ->get();

            // Sudah publish
            $data['history'] = Document::query()
                ->with(['documentType', 'organization'])
                ->whereNotNull('published_at')
                ->when(! $isAdmin, fn ($q) => $q->where('created_by', $user->id))
                ->latest('published_at')
                ->get();
        }

        // ── Data untuk Komisi B ─────────────────────────────────────────────
        if ($isKomisiB) {
            $data['reviewQueue'] = Document::query()
                ->with([
                    'documentType',
                    'organization',
                    'creator',
                    'approvals' => fn ($q) => $q->where('status', 'APPROVED')->with('workflowStep.role', 'approver'),
                ])
                ->where('publish_status', 'PENDING_REVIEW')
                ->latest('created_at')
                ->get();

            $data['reviewHistory'] = Document::query()
                ->with(['documentType', 'organization', 'creator'])
                ->where(fn ($q) => $q
                    ->whereNotNull('published_at')
                    ->orWhere('publish_status', 'REJECTED'))
                ->latest('created_at')
                ->get();
        }

        return view('pages.publish.index', $data);
    }

    /** Pengaju submit PDF untuk review Komisi B */
    public function publish(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'attachment' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $document = Document::query()
            ->where('id', $id)
            ->where('created_by', auth()->id())
            ->where('current_status', DocumentStatus::COMPLETED)
            ->where(fn ($q) => $q->whereNull('publish_status')->orWhere('publish_status', 'REJECTED'))
            ->firstOrFail();

        // Hapus file lama jika ada (re-submit setelah reject)
        if ($document->public_file_path && Storage::exists($document->public_file_path)) {
            Storage::delete($document->public_file_path);
        }

        $path = $request->file('attachment')->storeAs('published', $document->id . '.pdf');

        $document->update([
            'public_file_path' => $path,
            'publish_status'   => 'PENDING_REVIEW',
            'publish_notes'    => null,
            'published_at'     => null,
        ]);

        return redirect()->route('app.publish.index')
            ->with('success', 'Dokumen berhasil diajukan untuk review. Menunggu persetujuan Komisi B.');
    }

    /** Komisi B approve → dokumen langsung publik */
    public function approve(string $id): RedirectResponse
    {
        $document = Document::query()
            ->where('id', $id)
            ->where('publish_status', 'PENDING_REVIEW')
            ->firstOrFail();

        $document->update([
            'publish_status' => null,
            'publish_notes'  => null,
            'published_at'   => now(),
        ]);

        return redirect()->route('app.publish.index')
            ->with('success', 'Dokumen "' . $document->title . '" telah disetujui dan dipublikasikan.');
    }

    /** Komisi B reject → file dihapus, pengaju bisa upload ulang */
    public function reject(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $document = Document::query()
            ->where('id', $id)
            ->where('publish_status', 'PENDING_REVIEW')
            ->firstOrFail();

        if ($document->public_file_path && Storage::exists($document->public_file_path)) {
            Storage::delete($document->public_file_path);
        }

        $document->update([
            'public_file_path' => null,
            'publish_status'   => 'REJECTED',
            'publish_notes'    => $request->input('notes'),
            'published_at'     => null,
        ]);

        return redirect()->route('app.publish.index')
            ->with('success', 'Dokumen "' . $document->title . '" telah ditolak.');
    }

    /** Preview PDF yang diajukan (Komisi B & Admin) */
    public function previewPdf(string $id): Response
    {
        $user      = auth()->user();
        $isAdmin   = $user->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'ADMIN'))->exists();
        $isKomisiB = $isAdmin || $user->userRoles()->whereHas('role', fn ($q) => $q->where('code', 'KOMISI_B_BLM'))->exists();

        $document = Document::query()
            ->where('id', $id)
            ->where('publish_status', 'PENDING_REVIEW')
            ->when(! $isKomisiB, fn ($q) => $q->where('created_by', $user->id))
            ->firstOrFail();

        abort_unless($document->public_file_path && Storage::exists($document->public_file_path), 404);

        return response(Storage::get($document->public_file_path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-' . $document->id . '.pdf"',
        ]);
    }
}
