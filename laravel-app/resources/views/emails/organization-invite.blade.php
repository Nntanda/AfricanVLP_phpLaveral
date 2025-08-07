@extends('emails.layout')

@section('title', 'Organization Invitation')

@section('header-subtitle', 'You\'ve Been Invited!')

@section('content')
<h2 style="color: #333; margin-top: 0;">You're Invited to Join {{ $organization->name }}!</h2>

<p class="mb-4">
    {{ $invited_by->first_name }} {{ $invited_by->last_name }} has invited you to join 
    <strong>{{ $organization->name }}</strong> as a <strong>{{ ucfirst($role) }}</strong> 
    on the {{ $app_name }} platform.
</p>

<div style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 20px 0;">
    <h3 style="color: #667eea; margin-top: 0;">About {{ $organization->name }}</h3>
    @if($organization->description)
        <p style="margin-bottom: 15px;">{{ $organization->description }}</p>
    @endif
    
    <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 14px; color: #6c757d;">
        @if($organization->category)
            <div><strong>Category:</strong> {{ $organization->category->name }}</div>
        @endif
        @if($organization->country)
            <div><strong>Location:</strong> {{ $organization->country->name }}</div>
        @endif
        @if($organization->website)
            <div><strong>Website:</strong> <a href="{{ $organization->website }}" style="color: #667eea;">{{ $organization->website }}</a></div>
        @endif
    </div>
</div>

<h3 style="color: #667eea;">Your Role: {{ ucfirst($role) }}</h3>

<p class="mb-4">
    As a {{ $role }}, you'll be able to:
</p>

<ul style="padding-left: 20px; margin-bottom: 30px;">
    @if($role === 'admin')
        <li>Manage organization settings and information</li>
        <li>Invite and manage other members</li>
        <li>Create and manage events</li>
        <li>Post news and updates</li>
        <li>Access all organization resources</li>
    @elseif($role === 'moderator')
        <li>Help manage organization content</li>
        <li>Assist with event coordination</li>
        <li>Support community engagement</li>
        <li>Access organization resources</li>
    @else
        <li>Participate in organization activities</li>
        <li>Attend events and meetings</li>
        <li>Access organization resources</li>
        <li>Connect with other members</li>
    @endif
</ul>

<div class="text-center mb-4">
    <a href="{{ $invite_url }}" class="btn">Accept Invitation</a>
</div>

<p class="text-muted mb-3">
    If the button above doesn't work, you can copy and paste the following link into your browser:
</p>

<p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 14px;">
    {{ $invite_url }}
</p>

<div style="background-color: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px; margin: 20px 0;">
    <p style="margin: 0; color: #0c5460;">
        <strong>Note:</strong> This invitation will expire in 7 days. If you don't have an account yet, 
        you'll be prompted to create one when you accept the invitation.
    </p>
</div>

<hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">

<p class="text-muted">
    If you don't want to join this organization or believe you received this invitation by mistake, 
    you can safely ignore this email.
</p>
@endsection