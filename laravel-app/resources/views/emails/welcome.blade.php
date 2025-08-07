@extends('emails.layout')

@section('title', 'Welcome to AU VLP')

@section('header-subtitle', 'Welcome to the Community!')

@section('content')
<h2 style="color: #333; margin-top: 0;">Welcome {{ $user->first_name }}!</h2>

<p class="mb-4">
    Congratulations! Your account has been successfully created and verified. 
    Welcome to the {{ $app_name }} community - a platform dedicated to empowering 
    volunteer leaders across Africa.
</p>

<div class="text-center mb-4">
    <a href="{{ $dashboard_url }}" class="btn">Go to Dashboard</a>
</div>

<h3 style="color: #667eea; margin-top: 30px;">What's Next?</h3>

<div style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 20px 0;">
    <ul style="margin: 0; padding-left: 20px;">
        <li style="margin-bottom: 10px;">
            <strong>Complete Your Profile:</strong> Add your photo, bio, and interests to help others connect with you.
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Explore Organizations:</strong> Discover and join organizations that align with your values and interests.
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Browse Events:</strong> Find upcoming events, workshops, and volunteer opportunities in your area.
        </li>
        <li style="margin-bottom: 10px;">
            <strong>Access Resources:</strong> Download helpful guides, templates, and materials for your volunteer work.
        </li>
        <li style="margin: 0;">
            <strong>Connect with Others:</strong> Network with fellow volunteers and leaders across the continent.
        </li>
    </ul>
</div>

<h3 style="color: #667eea; margin-top: 30px;">Need Help Getting Started?</h3>

<p class="mb-3">
    Our platform is designed to be intuitive, but if you need any assistance:
</p>

<ul style="padding-left: 20px;">
    <li>Visit our Help Center for guides and tutorials</li>
    <li>Contact our support team for personalized assistance</li>
    <li>Join our community forum to connect with other users</li>
</ul>

<hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">

<p class="text-muted">
    Thank you for joining {{ $app_name }}. Together, we're building a stronger, more connected Africa 
    through volunteer leadership and community engagement.
</p>
@endsection