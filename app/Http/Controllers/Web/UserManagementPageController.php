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
                ->filter(fn ($user) => ((int) ($user->created_documents_count ?? 0) + (int) ($user->approvals_count ?? 0)) === 0)
                ->pluck('id')
                ->all(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('pages.user-management.create', [
            'title' => 'Tambah User',
            'roles' => Role::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserManagementRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->validated());

        return redirect()->route('app.users.edit', $user->id)
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(string $id): View|RedirectResponse
    {
        $user = $this->users->findById($id);
        if (! $user) {
            return redirect()->route('app.users.index')->with('error', 'User tidak ditemukan.');
        }

        return view('pages.user-management.edit', [
            'title' => 'Edit User',
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'assignedRoleIds' => $user->userRoles->pluck('role_id')->all(),
            'roleOrganizationId' => $user->userRoles->first()?->organization_id,
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

        if ($result === 'not_found') {
            return redirect()->route('app.users.index')->with('error', 'User tidak ditemukan.');
        }

        if ($result === 'has_activity') {
            return redirect()->route('app.users.index')->with('error', 'User tidak bisa dihapus karena sudah memiliki aktivitas.');
        }

        return redirect()->route('app.users.index')->with('success', 'User berhasil dihapus.');
    }
}
