<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RememberToken;
use App\Models\User;

class CheckRememberToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('user_id') && $request->cookie('remember_token')) {
            $token = $request->cookie('remember_token');
            
            if (str_contains($token, ':')) {
                [$tokenUserId, $tokenValue] = explode(':', $token, 2);
                
                $rememberToken = RememberToken::query()
                    ->where('user_id', $tokenUserId)
                    ->where('token', hash('sha256', $tokenValue))
                    ->where('expiry', '>', time())
                    ->first();
                    
                if ($rememberToken) {
                    $user = User::find($tokenUserId);
                    if ($user && $user->status === 'active') {
                        $request->session()->put([
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'user_role' => $user->role,
                            'user_hall' => $user->hall,
                            'login_time' => time(),
                        ]);
                        // Don't call regenerate here as it might conflict with other session operations in middleware
                        // Let the framework handle it naturally
                    }
                }
            }
        }

        return $next($request);
    }
}
