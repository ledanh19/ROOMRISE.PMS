<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Sanctum
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();
        if (!empty($bearer)) {
            if ($token =
                DB::table('personal_access_tokens')
                    ->where('name', 'access_token')
                    ->where('token', hash('sha256', substr($bearer, strpos($bearer, '|') + 1)))
                    ->first()) {
                if (Carbon::parse($token->expires_at) >= Carbon::now()) {
                    $user = User::findOrFail($token->tokenable_id);
                    app()->instance('user', $user);
                    return $next($request);
                }
            }
        }
        return response()->json([
            'message' => 'Access denied.',
            'code' => '401',
        ], 401);
    }
}
