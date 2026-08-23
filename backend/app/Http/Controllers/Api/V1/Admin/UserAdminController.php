<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserAdminController extends BaseApiController
{
    public function __construct(protected AuditLoggerService $auditLogger)
    {
    }

    /**
     * List all users
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')->latest()->paginate(25);
        return $this->paginated(UserResource::collection($users));
    }

    /**
     * Create admin user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $user->assignRole($validated['role']);

        $this->auditLogger->log('user.create', $user, null, ['name' => $user->name, 'email' => $user->email, 'role' => $validated['role']]);

        return $this->success(new UserResource($user->load('roles')), 'User created successfully.', 201);
    }

    /**
     * Update user role or status
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $old = ['roles' => $user->roles->pluck('name')->toArray(), 'is_active' => $user->is_active];

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['is_active'])) {
            $user->is_active = $validated['is_active'];
        }
        $user->save();

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        $this->auditLogger->log('user.update', $user, $old, ['roles' => $user->roles->pluck('name')->toArray(), 'is_active' => $user->is_active]);

        return $this->success(new UserResource($user->fresh('roles')), 'User updated successfully.');
    }
}
