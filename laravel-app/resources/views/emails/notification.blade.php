@extends('emails.layout')

@section('title', $subject)

@section('header-subtitle', 'Notification')

@section('content')
<h2 style="color: #333; margin-top: 0;">Hello {{ $user->first_name }}!</h2>

<div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
    <h3 style="color: #667eea; margin-top: 0;">{{ $subject }}</h3>
    <div style="margin: 15px 0;">
        {!! nl2br(e($message)) !!}
    </div>
</div>

@if(isset($action_url) && isset($action_text))
    <div class="text-center mb-4">
        <a href="{{ $action_url }}" class="btn">{{ $action_text }}</a>
    </div>
@endif

@if(isset($additional_info))
    <div style="margin: 30px 0;">
        {!! $additional_info !!}
    </div>
@endif

<hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">

<p class="text-muted">
    This is an automated notification from {{ config('app.name') }}. 
    If you have any questions, please contact our support team.
</p>
@endsection