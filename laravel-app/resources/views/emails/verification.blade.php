@extends('emails.layout')

@section('title', 'Verify Your Email Address')

@section('header-subtitle', 'Email Verification Required')

@section('content')
<h2 style="color: #333; margin-top: 0;">Hello {{ $user->first_name }}!</h2>

<p class="mb-4">
    Thank you for registering with {{ $app_name }}. To complete your registration and start using your account, 
    please verify your email address by clicking the button below.
</p>

<div class="text-center mb-4">
    <a href="{{ $verification_url }}" class="btn">Verify Email Address</a>
</div>

<p class="text-muted mb-3">
    If the button above doesn't work, you can copy and paste the following link into your browser:
</p>

<p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 14px;">
    {{ $verification_url }}
</p>

<p class="text-muted" style="margin-top: 30px;">
    <strong>Note:</strong> This verification link will expire in 24 hours for security reasons. 
    If you didn't create an account with {{ $app_name }}, you can safely ignore this email.
</p>

<hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">

<p class="text-muted">
    Need help? Contact our support team or visit our help center for assistance.
</p>
@endsection