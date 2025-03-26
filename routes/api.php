<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
    
    // Category routes
    Route::middleware(['role:merchant,customer,admin'])->group(function () {
        Route::get('category-products', [CategoryProductController::class, 'index']);
        Route::get('category-products/{id}', [CategoryProductController::class, 'show']);
    });

    Route::middleware(['role:merchant,customer,admin'])->group(function () {
        Route::post('category-products', [CategoryProductController::class, 'store']);
        Route::put('category-products/{id}', [CategoryProductController::class, 'update']);
        Route::delete('category-products/{id}', [CategoryProductController::class, 'destroy']);
    });

    // Product routes
    Route::middleware(['role:merchant,customer,admin'])->group(function () {
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{id}', [ProductController::class, 'show']);
    });

    Route::middleware(['role:merchant'])->group(function () {
        Route::post('products', [ProductController::class, 'store']);
    });

    Route::middleware(['role:merchant,admin'])->group(function () {
        Route::put('products/{id}', [ProductController::class, 'update']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
    });

    // Product rating
    Route::middleware(['role:customer,admin'])->group(function () {
        Route::post('products/{id}/rating', [ProductController::class, 'rateProduct']);
    });

    // Cart routes
    Route::middleware(['role:merchant,customer,admin'])->group(function () {
        Route::get('cart', [CartController::class, 'index']);
    });

    Route::middleware(['role:customer,admin'])->group(function () {
        Route::post('cart', [CartController::class, 'store']);
        Route::put('cart/{id}', [CartController::class, 'update']);
        Route::delete('cart/{id}', [CartController::class, 'destroy']);
    });

    // Order routes
    Route::middleware(['role:merchant,customer,admin'])->group(function () {
        Route::get('orders/my-orders', [OrderController::class, 'myOrders']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::get('orders/{id}/status', [OrderController::class, 'show']);
        Route::get('orders/list', [OrderController::class, 'index']);
    });

    Route::middleware(['role:customer'])->group(function () {
        Route::post('orders', [OrderController::class, 'store']);
    });

    Route::middleware(['role:customer,admin'])->group(function () {
        Route::post('orders/{id}/pay', [OrderController::class, 'processPayment']);
    });

    Route::middleware(['role:merchant,admin'])->group(function () {
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);
    });

    // Transaction routes
    Route::middleware(['role:merchant,customer,admin'])->group(function () {
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{id}', [TransactionController::class, 'show']);
        Route::get('transactions/{id}/points', [TransactionController::class, 'getTransactionPoints']);
        Route::get('points', [TransactionController::class, 'getUserPoints']);
        Route::get('transactions/report', [TransactionController::class, 'generateReport']);
    });

    Route::middleware(['role:customer,admin'])->group(function () {
        Route::post('transactions/payment', [OrderController::class, 'processPayment']);
        Route::post('transactions/refund', [TransactionController::class, 'refund']);
    });

    // User routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('users', [UserController::class, 'index']);
    });
});

// Rate limiter for API
Route::middleware(['throttle:api'])->group(function () {
    // You can move routes that need rate limiting here
});
