<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientMiddleware
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

        // Check if user account is active
        if ($user->status !== 1) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['error' => 'Your account is not active.']);
        }

        // Check if email is verified
        if (!$user->is_email_verified) {
            return redirect()->route('email.verification.notice');
        }

        return $next($request);
    }
}