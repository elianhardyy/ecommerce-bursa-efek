<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * AuthService constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Attempt to authenticate a user and return token.
     *
     * @param array $credentials
     * @return string|false
     */
    public function attemptLogin(array $credentials)
    {
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            return JWTAuth::fromUser($user);
        }
        
        return false;
    }

    /**
     * Register a new user and return token.
     *
     * @param array $userData
     * @param string $role
     * @return array
     */
    public function register(array $userData, string $role = 'customer')
    {
        $user = $this->userService->createWithRole($userData, $role);
        $token = JWTAuth::fromUser($user);
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Refresh authentication token.
     *
     * @return string
     */
    public function refreshToken()
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }

    /**
     * Invalidate current token (logout).
     *
     * @return bool
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return true;
    }
}