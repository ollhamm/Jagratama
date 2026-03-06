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

        return view('pages.user-management.index', [
            'title' => 'User Management',
            'users' => $this->users->paginate($filters, 'user_page'),
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
}
