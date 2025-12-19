<?php

namespace App\Services\Users;

use App\Models\User;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * User Service
 *
 * Handles all business logic related to user management.
 *
 * @package App\Services\Users
 */
class UserService extends BaseService
{
    protected string $modelClass = User::class;

    protected array $defaultRelations = [
        'roles',
    ];

    protected array $searchableFields = [
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    protected array $filterableFields = [
        'status' => 'status',
    ];

    /**
     * Get all users with details.
     */
    public function getAllWithDetails(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        if (!empty($params['role'])) {
            $query->whereHas('roles', function ($q) use ($params) {
                $q->where('name', $params['role']);
            });
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = $params['paginate'] ?? true;
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'status' => $data['status'] ?? 1,
            ]);

            // Assign role if provided
            if (!empty($data['role'])) {
                $user->assignRole($data['role']);
            }

            return $user->load($this->defaultRelations);
        });
    }

    /**
     * Update user.
     */
    public function updateUser(int|User $user, array $data): User
    {
        if (is_int($user)) {
            $user = $this->findOrFail($user);
        }

        return DB::transaction(function () use ($user, $data) {
            $updateData = array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'status' => $data['status'] ?? null,
            ]);

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            // Update role if provided
            if (!empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            return $user->fresh($this->defaultRelations);
        });
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): Collection
    {
        return $this->query()
            ->with($this->defaultRelations)
            ->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            })
            ->get();
    }

    /**
     * Activate user.
     */
    public function activate(int|User $user): User
    {
        if (is_int($user)) {
            $user = $this->findOrFail($user);
        }

        $user->update(['status' => 1]);

        return $user->fresh($this->defaultRelations);
    }

    /**
     * Deactivate user.
     */
    public function deactivate(int|User $user): User
    {
        if (is_int($user)) {
            $user = $this->findOrFail($user);
        }

        $user->update(['status' => 0]);

        return $user->fresh($this->defaultRelations);
    }

    /**
     * Get user statistics.
     */
    public function getStatistics(): array
    {
        $total = User::count();
        $active = User::where('status', 1)->count();
        $inactive = User::where('status', 0)->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
        ];
    }
}
