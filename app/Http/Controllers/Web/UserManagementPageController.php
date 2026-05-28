<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserManagementRequest;
use App\Http\Requests\UserManagement\UpdateUserManagementRequest;
use App\Http\Requests\UserManagement\UserIndexRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserManagementPageController extends Controller
{
    public function __construct(private readonly UserManagementService $users)
    {
    }

    public function index(UserIndexRequest $request): View
    {
        $filters = [
            'search' => $request->validated('search'),
            'is_active' => $request->validated('is_active'),
            'organization_id' => $request->validated('organization_id'),
            'per_page' => $request->validated('per_page') ?? 10,
        ];

        $users = $this->users->paginate($filters, 'user_page');

        return view('pages.user-management.index', [
            'title' => 'User Management',
            'users' => $users,
            'deletableUserIds' => $users->getCollection()
                ->filter(fn ($user) => $user->id !== auth()->id()
                    && ((int) ($user->created_documents_count ?? 0) + (int) ($user->approvals_count ?? 0)) === 0)
                ->pluck('id')
                ->all(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $organizations = Organization::query()->orderBy('name')->get();

        return view('pages.user-management.create', [
            'title' => 'Tambah User',
            'roles' => $roles,
            'organizations' => $organizations,
            'roleOrgMap' => $this->buildRoleOrgMap($roles, $organizations),
        ]);
    }

    public function store(StoreUserManagementRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->validated());

        return redirect()->route('app.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(string $id): View|RedirectResponse
    {
        $user = $this->users->findById($id);
        if (! $user) {
            return redirect()->route('app.users.index')->with('error', 'User tidak ditemukan.');
        }

        $roles = Role::query()->orderBy('name')->get();
        $organizations = Organization::query()->orderBy('name')->get();

        return view('pages.user-management.edit', [
            'title' => 'Edit User',
            'user' => $user,
            'isSelf' => $user->id === auth()->id(),
            'roles' => $roles,
            'organizations' => $organizations,
            'assignedRoleId' => $user->userRoles->first()?->role_id,
            'roleOrganizationId' => $user->userRoles->first()?->organization_id,
            'roleOrgMap' => $this->buildRoleOrgMap($roles, $organizations),
        ]);
    }

    public function update(UpdateUserManagementRequest $request, string $id): RedirectResponse
    {
        $updated = $this->users->update($id, $request->validated());
        if (! $updated) {
            return redirect()->route('app.users.index')->with('error', 'User tidak ditemukan.');
        }

        return redirect()->route('app.users.edit', $id)->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $result = $this->users->delete($id);

        if ($result === 'self_delete') {
            return redirect()->route('app.users.index')->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($result === 'not_found') {
            return redirect()->route('app.users.index')->with('error', 'User tidak ditemukan.');
        }

        if ($result === 'has_activity') {
            return redirect()->route('app.users.index')->with('error', 'User tidak bisa dihapus karena sudah memiliki aktivitas.');
        }

        return redirect()->route('app.users.index')->with('success', 'User berhasil dihapus.');
    }

    /**
     * Mapping role_code → daftar org yang relevan untuk dropdown "Organisasi Role".
     * Role yang tidak ada di sini dianggap global (org_id = null, field disabled).
     */
    private function buildRoleOrgMap(Collection $roles, Collection $organizations): array
    {
        // Role dengan org_id null di user_roles = global
        $globalRoles = [
            'ADMIN', 'DIREKTUR', 'WADIR_II', 'WADIR_III',
            'PRESIDEN_BEM', 'MENTERI_MINAT_BAKAT_BEM',
            'KETUA_BLM', 'KOMISI_B_BLM',
            'PENANGGUNG_JAWAB_MAHASISWA',
            'KA_SUB_BAG_AKADEMIK', 'KA_BAG_AKADEMIK', 'KA_BAG_AKADEMIK_UMUM',
            'KETUA_PANITIA',
        ];

        // Role org-scoped: dipetakan ke tipe org yang relevan
        $roleCodeToOrgTypes = [
            'KETUA_SBH'                   => ['SBH'],
            'PEMBINA_SBH'                 => ['SBH'],
            'KAJUR'                       => ['JURUSAN'],
            'PJ_MAHASISWA_ALUMNI_JURUSAN' => ['JURUSAN'],
            'KETUA_HMJ'                   => ['HMJ'],
            'KETUA_HMPS'                  => ['HMPS'],
            'KAPRODI'                     => ['HMPS'],
            'PEMBINA_UKM'                 => ['UKM'],
            'KETUA_UKM'                   => ['UKM'],
            // Pengaju bisa dari org tipe apapun kecuali JURUSAN dan SBH
            'PENGAJU'                     => ['HMJ', 'HMPS', 'UKM', 'BEM', 'BLM'],
        ];

        $orgByType = $organizations->groupBy(fn ($org) => $org->type instanceof \BackedEnum
            ? $org->type->value
            : (string) $org->type
        );

        return $roles->mapWithKeys(function ($role) use ($globalRoles, $roleCodeToOrgTypes, $orgByType) {
            if (in_array($role->code, $globalRoles)) {
                return [$role->id => ['global' => true, 'orgs' => []]];
            }

            $orgTypes = $roleCodeToOrgTypes[$role->code] ?? null;

            if (! $orgTypes) {
                return [$role->id => ['global' => true, 'orgs' => []]];
            }

            $orgs = collect();
            foreach ($orgTypes as $type) {
                $orgs = $orgs->merge($orgByType->get($type) ?? []);
            }

            return [$role->id => [
                'global' => false,
                'orgs'   => $orgs->map(fn ($o) => ['id' => $o->id, 'name' => $o->name])->values()->all(),
            ]];
        })->all();
    }
}
