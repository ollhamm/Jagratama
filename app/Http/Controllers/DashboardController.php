<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const APPROVAL_ROLE_CODES = [
        'KETUA_SBH', 'PEMBINA_SBH', 'KAPRODI', 'KAJUR', 'WADIR_II', 'WADIR_III', 'DIREKTUR',
        'KETUA_HMPS', 'KETUA_HMJ', 'KETUA_UKM', 'KETUA_BLM', 'PRESIDEN_BEM', 'KOMISI_B_BLM',
        'PJ_MAHASISWA_ALUMNI_JURUSAN', 'PEMBINA_UKM', 'MENTERI_MINAT_BAKAT_BEM',
        'PENANGGUNG_JAWAB_MAHASISWA', 'ADMINISTRASI_AKADEMIK', 'KA_SUB_BAG_AKADEMIK',
        'KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM',
    ];

    public function __construct(
        private readonly DocumentService $documents,
        private readonly ApprovalService $approvals,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $roleCodes = $user->userRoles()->with('role')->get()
            ->pluck('role.code')->filter()->unique()->values()->all();

        $isAdmin = in_array('ADMIN', $roleCodes, true);
        $isApprover = $isAdmin || count(array_intersect($roleCodes, self::APPROVAL_ROLE_CODES)) > 0;

        $recentNotifications = SystemNotification::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        if ($isAdmin) {
            return $this->adminDashboard($request, $user, $recentNotifications);
        }

        if ($isApprover) {
            return $this->approverDashboard($request, $user, $recentNotifications);
        }

        return $this->pengajuDashboard($request, $user, $recentNotifications);
    }

    private function pengajuDashboard(Request $request, User $user, $recentNotifications)
    {
        $filters = [
            'search' => $request->query('my_search'),
            'status' => $request->query('my_status'),
            'per_page' => $request->query('my_per_page', 10),
        ];

        $mySubmissions = $this->documents->mySubmissions($user, $filters, 'my_page');

        $own = Document::query()->where('created_by', $user->id);

        $summary = [
            'total' => (clone $own)->count(),
            'completed' => (clone $own)->where('current_status', DocumentStatus::COMPLETED)->count(),
            'rejected' => (clone $own)->where('current_status', DocumentStatus::REJECTED)->count(),
            'in_review' => (clone $own)->whereIn('current_status', [DocumentStatus::SUBMITTED, DocumentStatus::IN_REVIEW])->count(),
        ];

        $recentSubmissions = Document::query()
            ->where('created_by', $user->id)
            ->with('documentType')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('pages.dashboard.role-dashboard', [
            'title' => 'Dashboard',
            'mode' => 'pengaju',
            'user' => $user,
            'summary' => $summary,
            'recentNotifications' => $recentNotifications,
            'recentSubmissions' => $recentSubmissions,
            'mySubmissions' => $mySubmissions,
        ]);
    }

    private function approverDashboard(Request $request, User $user, $recentNotifications)
    {
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

        $summary = [
            'pending' => $pendingApprovals->total(),
            'approved' => DocumentApproval::query()->where('approved_by', $user->id)->where('status', ApprovalStatus::APPROVED)->count(),
            'rejected' => DocumentApproval::query()->where('approved_by', $user->id)->where('status', ApprovalStatus::REJECTED)->count(),
        ];

        $recentApprovals = DocumentApproval::query()
            ->where('approved_by', $user->id)
            ->whereIn('status', [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])
            ->with('document')
            ->latest('approved_at')
            ->limit(5)
            ->get();

        return view('pages.dashboard.role-dashboard', [
            'title' => 'Dashboard',
            'mode' => 'approver',
            'user' => $user,
            'summary' => $summary,
            'recentNotifications' => $recentNotifications,
            'recentApprovals' => $recentApprovals,
            'pendingApprovals' => $pendingApprovals,
            'approvalHistory' => $approvalHistory,
        ]);
    }

    private function adminDashboard(Request $request, User $user, $recentNotifications)
    {
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

        $summary = [
            'total_documents' => Document::query()->count(),
            'active_users' => User::query()->where('is_active', true)->count(),
            'pending_approvals' => DocumentApproval::query()->where('status', ApprovalStatus::PENDING)->count(),
            'published' => Document::query()->whereNotNull('published_at')->count(),
        ];

        $recentSubmissions = Document::query()
            ->with(['documentType', 'creator'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentApprovals = DocumentApproval::query()
            ->whereIn('status', [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])
            ->with(['document', 'approver'])
            ->latest('approved_at')
            ->limit(5)
            ->get();

        $recentPublished = Document::query()
            ->whereNotNull('published_at')
            ->with('creator')
            ->latest('published_at')
            ->limit(5)
            ->get();

        return view('pages.dashboard.role-dashboard', [
            'title' => 'Dashboard',
            'mode' => 'admin',
            'user' => $user,
            'summary' => $summary,
            'recentNotifications' => $recentNotifications,
            'recentSubmissions' => $recentSubmissions,
            'recentApprovals' => $recentApprovals,
            'recentPublished' => $recentPublished,
            'pendingApprovals' => $pendingApprovals,
            'approvalHistory' => $approvalHistory,
        ]);
    }
}
