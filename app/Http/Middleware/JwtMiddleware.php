<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return ApiResponse::unauthorized('User not found');
            }
        } catch (TokenExpiredException $e) {
            return ApiResponse::error('Token expired', 401);
        } catch (TokenInvalidException $e) {
            return ApiResponse::error('Token invalid', 401);
        } catch (\Exception $e) {
            return ApiResponse::error('Authorization token not found', 401);
        }
        return $next($request);
    }
}
