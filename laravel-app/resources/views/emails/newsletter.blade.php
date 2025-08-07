@extends('emails.layout')

@section('title', 'Newsletter')

@section('content')
@if(isset($recipient_name) && !empty($recipient_name))
    <h2 style="color: #333; margin-top: 0;">Hello {{ $recipient_name }}!</h2>
@else
    <h2 style="color: #333; margin-top: 0;">Hello!</h2>
@endif

<div style="margin: 30px 0;">
    {!! $content !!}
</div>

<hr style="border: none; border-top: 1px solid #e9ecef; margin: 30px 0;">

<p class="text-muted">
    You're receiving this newsletter because you're subscribed to updates from {{ config('app.name') }}. 
    If you no longer wish to receive these emails, you can 
    <a href="#" style="color: #667eea;">unsubscribe here</a>.
</p>
@endsection