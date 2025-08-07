<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $emailService;
    protected $securityService;

    public function __construct(EmailService $emailService, SecurityService $securityService)
    {
        $this->emailService = $emailService;
        $this->securityService = $securityService;
    }
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $user = Auth::user();
            
            // Check if email is verified (matching CakePHP behavior)
            if (!$user->is_email_verified) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Email not yet verified. Follow the link from the registration welcome mail to verify your email.'
                ])->withInput();
            }

            $request->session()->regenerate();

            // Redirect based on user role
            if ($this->isAdminUser($user)) {
                return redirect()->intended('/admin/dashboard');
            }
            
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials, try again',
        ])->withInput();
    }

    /**
     * Show the registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:45',
            'last_name' => 'required|string|max:45',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone_number' => 'nullable|string|max:16',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'country_id' => $request->country_id,
                'city_id' => $request->city_id,
                'role' => 'user',
                'status' => 1, // Active status
                'is_email_verified' => false,
                'email_verification_token' => Str::random(60),
                'registration_status' => 1, // Account created
            ]);

            // Send verification email
            $this->emailService->sendVerificationEmail($user);

            return redirect()->route('login')->with('success', 'Account created successfully. Please check your email to verify your account.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration failed. Please try again.'])->withInput();
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request, $token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Invalid verification token.']);
        }

        $user->update([
            'is_email_verified' => true,
            'email_verification_token' => null,
        ]);

        return redirect()->route('login')->with('success', 'Email verified successfully. You can now login.');
    }

    /**
     * Check if user is admin
     */
    private function isAdminUser($user)
    {
        return in_array($user->role, ['admin', 'super_admin']);
    }
}