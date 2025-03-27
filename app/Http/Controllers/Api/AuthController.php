<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Services\AuthService;
use App\Services\UserService;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Info(
 *     title="Ecommerce API Documentation",
 *     version="1.0.0",
 *     description="ecommerce api",
 *     @OA\Contact(
 *         email="elianhardiawan00@gmail.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8000/api/",
 *     description="API dengan prefix"
 * )
 * @OA\SecurityScheme(
 *    type="http",
 * description="Login with email and password to get the authentication token",
 * name="Token based",
 * in="header",
 * scheme="bearer",
 * bearerFormat="JWT",
 * securityScheme="bearerAuth"
 * )
 */
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

     /**
 * Login user and create token.
 *
 * @OA\Post(
 *     path="/auth/login",
 *     summary="Login user",
 *     description="Login user and return JWT token",
 *     operationId="login",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="user", type="object"),
 *                 @OA\Property(property="access_token", type="string"),
 *                 @OA\Property(property="token_type", type="string", example="bearer"),
 *                 @OA\Property(property="expires_in", type="integer", example=3600)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
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

    /**
 * Register a new user.
 *
 * @OA\Post(
 *     path="/auth/register",
 *     summary="Register new user",
 *     description="Register new user and return JWT token",
 *     operationId="register",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"username", "email", "password", "password_confirmation"},
 *             @OA\Property(property="username", type="string", example="johndoe"),
 *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password"),
 *             @OA\Property(property="password_confirmation", type="string", example="password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
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

    /**
 * Get authenticated user.
 *
 * @OA\Get(
 *     path="/auth/me",
 *     summary="Get user profile",
 *     description="Get authenticated user profile",
 *     operationId="me",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
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

    /**
 * Logout user (invalidate token).
 *
 * @OA\Post(
 *     path="/auth/logout",
 *     summary="Logout user",
 *     description="Invalidate user token",
 *     operationId="logout",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
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

     /**
 * Refresh token.
 *
 * @OA\Post(
 *     path="/auth/refresh",
 *     summary="Refresh token",
 *     description="Refresh user token",
 *     operationId="refresh",
 *     tags={"Authentication"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
    public function refresh()
    {
        $token = $this->authService->refreshToken();
        $user = auth()->user();
        $response = new LoginResponse($user, $token);

        return ApiResponse::success($response->toArray(), 'Token refreshed successfully');
    }
}