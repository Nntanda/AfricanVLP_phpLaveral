@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('header-subtitle', 'Password Reset Request')

@section('content')
<h2 style="color: #333; margin-top: 0;">Hello {{ $user->first_name }}!</h2>

<p class="mb-4">
    We received a request to reset your password for your {{ $app_name }} account. 
    If you made this request, click the button below to reset your password.
</p>

<div class="text-center mb-4">
    <a href="{{ $reset_url }}" class="btn">Reset Password</a>
</div>

<p class="text-muted mb-3">
    If the button above doesn't work, you can copy and paste the following link into your browser:
</p>

<p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 14px;">
    {{ $reset_url }}
</p>

<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 15px; margin: 20px 0;">
    <p style="margin: 0; color: #856404;">
        <strong>Security Notice:</strong> This password reset link will expire in 1 hour for security reasons. 
        If you didn't request a password reset, you can safely ignore this email - your password will remain unchanged.
    </p>
</div>

<hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">

<p class="text-muted">
    For security reasons, if you continue to receive password reset emails that you didn't request, 
    please contact our support team immediately.
</p>
@endsection