<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly ApprovalService $approvals,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $isApprover = $user->userRoles()
            ->whereHas('role', fn ($query) => $query->whereIn('code', [
                'KETUA_SBH',
                'PEMBINA_SBH',
                'KAPRODI',
                'KAJUR',
                'WADIR_II',
                'WADIR_III',
                'DIREKTUR',
                'ADMIN',
                'KETUA_HMPS',
                'KETUA_HMJ',
                'KETUA_UKM',
                'KETUA_BLM',
                'PRESIDEN_BEM',
                'KOMISI_B_BLM',
                'PJ_MAHASISWA_ALUMNI_JURUSAN',
                'PEMBINA_UKM',
                'MENTERI_MINAT_BAKAT_BEM',
                'PENANGGUNG_JAWAB_MAHASISWA',
                'ADMINISTRASI_AKADEMIK',
                'KA_SUB_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK',
                'KA_BAG_AKADEMIK_UMUM',
            ]))
            ->exists();

        if (! $isApprover) {
            $filters = [
                'search' => $request->query('my_search'),
                'status' => $request->query('my_status'),
                'per_page' => $request->query('my_per_page', 10),
            ];

            $mySubmissions = $this->documents->mySubmissions($user, $filters, 'my_page');

            return view('pages.dashboard.role-dashboard', [
                'title' => 'Dashboard Pengaju',
                'mode' => 'pengaju',
                'mySubmissions' => $mySubmissions,
            ]);
        }

        $pendingFilters = [
            'search' => $request->query('pending_search'),
            'per_page' => $request->query('pending_per_page', 10),
        ];

        $historyFilters = [
            'search' => $request->query('history_search'),
            'per_page' => $request->query('history_per_page', 10),
        ];

        $pendingApprovals = $this->approvals->pending($user, $pendingFilters, 'pending_page');
        $approvalHistory = $this->approvals->history($user, $historyFilters, 'history_page');

        return view('pages.dashboard.role-dashboard', [
            'title' => 'Dashboard Approver',
            'mode' => 'approver',
            'pendingApprovals' => $pendingApprovals,
            'approvalHistory' => $approvalHistory,
        ]);
    }
}
