<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * User Controller
 *
 * Handles HTTP requests for user management.
 */
class UserController extends ApiController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/users",
     *     operationId="getUsers",
     *     tags={"Users"},
     *     summary="Get list of users",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="role", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'search', 'role', 'status', 'paginate', 'per_page', 'page',
            ]);

            $result = $this->service->getAllWithDetails($params);

            return $this->success($result, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     operationId="createUser",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"first_name", "last_name", "email"},
     *         @OA\Property(property="first_name", type="string"),
     *         @OA\Property(property="last_name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="role", type="string")
     *     )),
     *     @OA\Response(response=201, description="User created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'gender' => 'nullable|integer|in:1,2',
                'dob' => 'nullable|date',
                'blood_group' => 'nullable|string|max:10',
                'role' => 'nullable|string|exists:roles,name',
                'status' => 'nullable|integer|in:0,1',
            ]);

            $user = $this->service->createUser($validated);

            return $this->created($user, 'User created successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     operationId="getUser",
     *     tags={"Users"},
     *     summary="Get user details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->service->findOrFail($id);

            return $this->success($user, 'User retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('User not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve user: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     summary="Update user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="first_name", type="string"),
     *         @OA\Property(property="last_name", type="string"),
     *         @OA\Property(property="phone", type="string"),
     *         @OA\Property(property="role", type="string")
     *     )),
     *     @OA\Response(response=200, description="User updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'gender' => 'nullable|integer|in:1,2',
                'dob' => 'nullable|date',
                'blood_group' => 'nullable|string|max:10',
                'role' => 'nullable|string|exists:roles,name',
                'status' => 'nullable|integer|in:0,1',
            ]);

            $user = $this->service->updateUser($id, $validated);

            return $this->success($user, 'User updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('User not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     summary="Delete user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('User deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('User not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/users/{id}/activate",
     *     operationId="activateUser",
     *     tags={"Users"},
     *     summary="Activate user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User activated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $user = $this->service->activate($id);

            return $this->success($user, 'User activated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('User not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to activate user: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/users/{id}/deactivate",
     *     operationId="deactivateUser",
     *     tags={"Users"},
     *     summary="Deactivate user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deactivated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $user = $this->service->deactivate($id);

            return $this->success($user, 'User deactivated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('User not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to deactivate user: ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/users/statistics",
     *     operationId="getUserStatistics",
     *     tags={"Users"},
     *     summary="Get user statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();

            return $this->success($stats, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}
