<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    /**
     * Show the password reset request form
     */
    public function showResetRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Handle password reset request
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'We can\'t find a user with that email address.']);
        }

        // Generate password reset token
        $token = Str::random(60);
        $user->update([
            'password_reset_token' => $token,
            'password_reset_expires' => Carbon::now()->addHours(1), // Token expires in 1 hour
        ]);

        // Send password reset email
        $this->emailService->sendPasswordResetEmail($user, $token);

        return back()->with('status', 'We have emailed your password reset link!');
    }

    /**
     * Show the password reset form
     */
    public function showResetForm(Request $request, $token)
    {
        return view('auth.passwords.reset', ['token' => $token, 'email' => $request->email]);
    }

    /**
     * Handle password reset
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)
                   ->where('password_reset_token', $request->token)
                   ->where('password_reset_expires', '>', Carbon::now())
                   ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'This password reset token is invalid or has expired.']);
        }

        // Reset the password
        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'password_reset_expires' => null,
        ]);

        return redirect()->route('login')->with('status', 'Your password has been reset successfully!');
    }

    /**
     * Resend email verification token
     */
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', $request->email)->first();

        if ($user->is_email_verified) {
            return back()->withErrors(['email' => 'Email is already verified.']);
        }

        // Generate new verification token if needed
        if (!$user->email_verification_token) {
            $user->update([
                'email_verification_token' => Str::random(60),
            ]);
        }

        // Send verification email
        $this->emailService->sendVerificationEmail($user);

        return back()->with('status', 'Verification email has been resent!');
    }
}