@extends('layouts.client')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Welcome back, {{ $authUser->first_name }}!
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Here's what's happening in your network
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    @if($userStats['profile_completion'] < 100)
                        <div class="text-center">
                            <div class="text-sm font-medium text-gray-900">Profile</div>
                            <div class="text-xs text-gray-500">{{ $userStats['profile_completion'] }}% complete</div>
                            <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $userStats['profile_completion'] }}%"></div>
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('client.profile') }}" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user mr-2"></i>View Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Organizations -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-building text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">My Organizations</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $userStats['organizations_count'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('client.organizations.my') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        View all
                    </a>
                </div>
            </div>
        </div>

        <!-- Events Participated -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-calendar-check text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Events Joined</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $userStats['events_participated'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('client.events.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Browse events
                    </a>
                </div>
            </div>
        </div>

        <!-- Resources Accessed -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Resources Used</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $userStats['resources_accessed'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('client.resources.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Browse resources
                    </a>
                </div>
            </div>
        </div>

        <!-- Profile Completion -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-user-check text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Profile Complete</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $userStats['profile_completion'] }}%</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('client.profile') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Complete profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Upcoming Events -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Upcoming Events</h3>
                        <a href="{{ route('client.events.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($upcomingEvents as $event)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('client.events.show', $event) }}" class="hover:text-indigo-600">
                                            {{ $event->title }}
                                        </a>
                                    </h4>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('M j, Y g:i A') : 'No date' }}
                                        @if($event->organization)
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-building mr-1"></i>
                                            {{ $event->organization->name }}
                                        @endif
                                    </div>
                                    @if($event->city && $event->country)
                                        <div class="mt-1 text-sm text-gray-500">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            {{ $event->city->name }}, {{ $event->country->nicename }}
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <a href="{{ route('client.events.show', $event) }}" 
                                       class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-medium hover:bg-indigo-200">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-calendar-alt text-gray-400 text-3xl mb-3"></i>
                            <p class="text-gray-500">No upcoming events</p>
                            <a href="{{ route('client.events.index') }}" 
                               class="mt-2 text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Browse all events
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent News -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Latest News</h3>
                        <a href="{{ route('client.news.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($recentNews as $news)
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-medium text-gray-900">
                                <a href="{{ route('client.news.show', $news) }}" class="hover:text-indigo-600">
                                    {{ $news->title }}
                                </a>
                            </h4>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ Str::limit(strip_tags($news->content), 120) }}
                            </p>
                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                <span>{{ $news->created ? \Carbon\Carbon::parse($news->created)->diffForHumans() : 'Unknown date' }}</span>
                                @if($news->organization)
                                    <span class="mx-2">•</span>
                                    <span>{{ $news->organization->name }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-newspaper text-gray-400 text-3xl mb-3"></i>
                            <p class="text-gray-500">No recent news</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-6">
            <!-- My Organizations -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">My Organizations</h3>
                        <a href="{{ route('client.organizations.my') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($userOrganizations->take(3) as $organization)
                        <div class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-8 w-8 bg-gray-300 rounded-md flex items-center justify-center flex-shrink-0">
                                    @if($organization->logo)
                                        <img src="{{ $organization->logo }}" alt="{{ $organization->name }}" class="h-8 w-8 rounded-md">
                                    @else
                                        <i class="fas fa-building text-gray-500 text-sm"></i>
                                    @endif
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('client.organizations.show', $organization) }}" class="hover:text-indigo-600">
                                            {{ $organization->name }}
                                        </a>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ ucfirst($organization->pivot->role) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-building text-gray-400 text-3xl mb-3"></i>
                            <p class="text-gray-500 mb-2">No organizations yet</p>
                            <a href="{{ route('client.organizations.index') }}" 
                               class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Browse organizations
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recommended Organizations -->
            @if($recommendedOrganizations->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Recommended for You</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($recommendedOrganizations as $organization)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 bg-gray-300 rounded-md flex items-center justify-center flex-shrink-0">
                                            @if($organization->logo)
                                                <img src="{{ $organization->logo }}" alt="{{ $organization->name }}" class="h-8 w-8 rounded-md">
                                            @else
                                                <i class="fas fa-building text-gray-500 text-sm"></i>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $organization->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $organization->country->nicename ?? 'Unknown' }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('client.organizations.show', $organization) }}" 
                                       class="text-xs text-indigo-600 hover:text-indigo-500 font-medium">
                                        View
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recent Resources -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Recent Resources</h3>
                        <a href="{{ route('client.resources.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all
                        </a>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($recentResources as $resource)
                        <div class="px-6 py-4">
                            <h4 class="text-sm font-medium text-gray-900">
                                <a href="{{ route('client.resources.show', $resource) }}" class="hover:text-indigo-600">
                                    {{ $resource->title }}
                                </a>
                            </h4>
                            <div class="mt-1 flex items-center text-xs text-gray-500">
                                <span>{{ $resource->created ? \Carbon\Carbon::parse($resource->created)->diffForHumans() : 'Unknown date' }}</span>
                                @if($resource->organization)
                                    <span class="mx-2">•</span>
                                    <span>{{ $resource->organization->name }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-file-alt text-gray-400 text-3xl mb-3"></i>
                            <p class="text-gray-500">No recent resources</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection