<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Services\AuthService;
use App\Services\UserService;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * AuthController constructor.
     *
     * @param UserService $userService
     * @param AuthService $authService
     */
    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;
    }

    /**
     * Login user and create token.
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$this->authService->attemptLogin($credentials)) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        $user = auth()->user();
        $tokenLogin = JWTAuth::fromUser($user);
        $response = new LoginResponse($user, $tokenLogin);

        return ApiResponse::success($response->toArray(), 'Login successful');
    }

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated(), 'customer');
            $response = new RegisterResponse($result['user'], $result['token']);

            return ApiResponse::success($response->toArray(), 'Registration successful', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();
        $response = new UserResource($user);

        return ApiResponse::success($response->toArray($user), 'User data retrieved successfully');
    }

    /**
     * Logout user (invalidate token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->authService->logout();
        return ApiResponse::success(null, 'Successfully logged out');
    }

    /**
     * Refresh token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = $this->authService->refreshToken();
        $user = auth()->user();
        $response = new LoginResponse($user, $token);

        return ApiResponse::success($response->toArray(), 'Token refreshed successfully');
    }
}