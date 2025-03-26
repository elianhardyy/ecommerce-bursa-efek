<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    /**
     * UserService constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->model = $user;
        $this->cacheKey = 'users';
    }

    /**
     * Create new user with role.
     *
     * @param array $data
     * @param string $role
     * @return User
     */
    public function createWithRole(array $data, string $role = 'customer')
    {
        // Hash password if it exists
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = $this->create($data);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Get authenticated user.
     *
     * @return User
     */
    public function getAuthUser()
    {
        return auth()->user();
    }

    /**
     * Get user by email.
     * 
     * @param string $email
     * @return User|null
     */
    public function getByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get user points.
     * 
     * @param int $userId
     * @return int
     */
    public function getUserPoints(int $userId)
    {
        $user = $this->getById($userId);
        return $user->points;
    }

    /**
     * Add points to user.
     * 
     * @param int $userId
     * @param int $points
     * @return User
     */
    public function addPoints(int $userId, int $points)
    {
        $user = $this->getById($userId);
        $user->points += $points;
        $user->save();
        
        return $user;
    }
}