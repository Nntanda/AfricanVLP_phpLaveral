<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has admin role
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized access.');
        }

        // Check if user account is active
        if ($user->status !== 1) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['error' => 'Your account is not active.']);
        }

        return $next($request);
    }
}