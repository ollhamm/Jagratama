<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UserGuide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuidePageController extends Controller
{
    // Role yang dianggap "Approval" — sama dengan daftar role menu Approval di MenuHelper, minus ADMIN.
    private const APPROVAL_ROLE_CODES = [
        'KETUA_SBH', 'KETUA_HMPS', 'KETUA_HMJ', 'KETUA_UKM', 'KETUA_BLM',
        'PRESIDEN_BEM', 'KOMISI_B_BLM', 'PJ_MAHASISWA_ALUMNI_JURUSAN',
        'PEMBINA_SBH', 'PEMBINA_UKM', 'MENTERI_MINAT_BAKAT_BEM',
        'PENANGGUNG_JAWAB_MAHASISWA', 'ADMINISTRASI_AKADEMIK',
        'KA_SUB_BAG_AKADEMIK', 'KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM',
        'KAPRODI', 'KAJUR', 'WADIR_II', 'WADIR_III', 'DIREKTUR',
    ];

    /**
     * Konten panduan untuk role user yang sedang login — dipanggil via AJAX
     * dari tombol "Panduan Penggunaan" di sidebar.
     */
    public function show(Request $request): JsonResponse
    {
        $key = $this->resolveGuideKey($request->user());

        if (! $key) {
            return response()->json(['title' => 'Panduan Penggunaan', 'content' => '<p>Belum ada panduan untuk role Anda.</p>']);
        }

        $guide = UserGuide::query()->where('key', $key)->first();

        return response()->json([
            'title'   => $guide->title ?? 'Panduan Penggunaan',
            'content' => $guide->content ?? '<p>Konten panduan belum diisi.</p>',
        ]);
    }

    /**
     * Halaman manajemen panduan (Admin) — 3 tab: Pengaju, Approval, Admin.
     */
    public function manage(): View
    {
        $guides = UserGuide::query()->get()->keyBy('key');

        return view('pages.guides.manage', [
            'title'  => 'Kelola Panduan',
            'guides' => $guides,
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $guide = UserGuide::query()->where('key', $key)->firstOrFail();

        $guide->update([
            'title'      => $validated['title'],
            'content'    => $validated['content'] ?? '',
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('app.guides.manage')->with('success', 'Panduan "'.$guide->title.'" berhasil diperbarui.');
    }

    private function resolveGuideKey(\App\Models\User $user): ?string
    {
        $roleCodes = $user->userRoles()->with('role')->get()
            ->pluck('role.code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (in_array('ADMIN', $roleCodes, true)) {
            return 'admin';
        }

        if (count(array_intersect($roleCodes, self::APPROVAL_ROLE_CODES)) > 0) {
            return 'approval';
        }

        if (in_array('PENGAJU', $roleCodes, true)) {
            return 'pengaju';
        }

        return null;
    }
}
