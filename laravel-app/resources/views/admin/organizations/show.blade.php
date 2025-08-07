@extends('layouts.admin')

@section('title', $organization->name)
@section('page-title', 'Organization Details')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-start">
        <div class="flex items-center space-x-4">
            <div class="h-16 w-16 flex-shrink-0">
                @if($organization->logo)
                    <img class="h-16 w-16 rounded-lg object-cover" 
                         src="{{ $organization->logo }}" 
                         alt="{{ $organization->name }}">
                @else
                    <div class="h-16 w-16 bg-gray-300 rounded-lg flex items-center justify-center">
                        <i class="fas fa-building text-gray-500 text-2xl"></i>
                    </div>
                @endif
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">{{ $organization->name }}</h3>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $organization->status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $organization->status === 1 ? 'Active' : 'Inactive' }}
                    </span>
                    @if($organization->categoryOfOrganization)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $organization->categoryOfOrganization->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.organizations.members', $organization) }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-users mr-2"></i>Manage Members
            </a>
            <a href="{{ route('admin.organizations.edit', $organization) }}" 
               class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <form method="POST" action="{{ route('admin.organizations.toggle-status', $organization) }}" class="inline">
                @csrf
                <button type="submit" 
                        class="bg-{{ $organization->status === 1 ? 'red' : 'green' }}-600 hover:bg-{{ $organization->status === 1 ? 'red' : 'green' }}-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                        onclick="return confirm('Are you sure you want to {{ $organization->status === 1 ? 'deactivate' : 'activate' }} this organization?')">
                    <i class="fas fa-{{ $organization->status === 1 ? 'ban' : 'check' }} mr-2"></i>
                    {{ $organization->status === 1 ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Organization Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">Basic Information</h4>
                </div>
                <div class="px-6 py-4 space-y-4">
                    @if($organization->about)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">About</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $organization->about }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($organization->type)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $organization->type }}</p>
                            </div>
                        @endif

                        @if($organization->date_of_establishment)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Established</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($organization->date_of_establishment)->format('F j, Y') }}
                                </p>
                            </div>
                        @endif

                        @if($organization->institutionType)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Institution Type</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $organization->institutionType->name }}</p>
                            </div>
                        @endif

                        @if($organization->government_affliliation)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Government Affiliation</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $organization->government_affliliation }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">Contact Information</h4>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($organization->phone_number)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $organization->phone_number }}</p>
                            </div>
                        @endif

                        @if($organization->website)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Website</label>
                                <p class="mt-1 text-sm">
                                    <a href="{{ $organization->website }}" target="_blank" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $organization->website }}
                                    </a>
                                </p>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($organization->city)
                                    {{ $organization->city->name }}, 
                                @endif
                                {{ $organization->country->nicename ?? 'Unknown Country' }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created By</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($organization->creator)
                                    <a href="{{ route('admin.users.show', $organization->creator) }}" 
                                       class="text-indigo-600 hover:text-indigo-500">
                                        {{ $organization->creator->first_name }} {{ $organization->creator->last_name }}
                                    </a>
                                @else
                                    Unknown
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Social Media Links -->
                    @if($organization->facebbok_url || $organization->instagram_url || $organization->twitter_url)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Social Media</label>
                            <div class="flex space-x-4">
                                @if($organization->facebbok_url)
                                    <a href="{{ $organization->facebbok_url }}" target="_blank" 
                                       class="text-blue-600 hover:text-blue-500">
                                        <i class="fab fa-facebook text-xl"></i>
                                    </a>
                                @endif
                                @if($organization->instagram_url)
                                    <a href="{{ $organization->instagram_url }}" target="_blank" 
                                       class="text-pink-600 hover:text-pink-500">
                                        <i class="fab fa-instagram text-xl"></i>
                                    </a>
                                @endif
                                @if($organization->twitter_url)
                                    <a href="{{ $organization->twitter_url }}" target="_blank" 
                                       class="text-blue-400 hover:text-blue-300">
                                        <i class="fab fa-twitter text-xl"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Member Statistics -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">Member Statistics</h4>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Total Members</span>
                        <span class="text-sm font-medium text-gray-900">{{ $memberStats['total_members'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Active Members</span>
                        <span class="text-sm font-medium text-green-600">{{ $memberStats['active_members'] }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500">Owners</span>
                            <span class="text-xs font-medium text-purple-600">{{ $memberStats['owners'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500">Admins</span>
                            <span class="text-xs font-medium text-blue-600">{{ $memberStats['admins'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500">Moderators</span>
                            <span class="text-xs font-medium text-yellow-600">{{ $memberStats['moderators'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gray-500">Members</span>
                            <span class="text-xs font-medium text-gray-600">{{ $memberStats['members'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">Quick Actions</h4>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <a href="{{ route('admin.organizations.members', $organization) }}" 
                       class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        <i class="fas fa-users mr-2 text-blue-500"></i>Manage Members
                    </a>
                    <a href="{{ route('admin.events.index', ['organization_id' => $organization->id]) }}" 
                       class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        <i class="fas fa-calendar-alt mr-2 text-green-500"></i>View Events
                    </a>
                    <a href="{{ route('admin.news.index', ['organization_id' => $organization->id]) }}" 
                       class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        <i class="fas fa-newspaper mr-2 text-yellow-500"></i>View News
                    </a>
                    <a href="{{ route('admin.resources.index', ['organization_id' => $organization->id]) }}" 
                       class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        <i class="fas fa-file-alt mr-2 text-purple-500"></i>View Resources
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Events -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h4 class="text-lg font-medium text-gray-900">Recent Events</h4>
                    <a href="{{ route('admin.events.index', ['organization_id' => $organization->id]) }}" 
                       class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
                </div>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($organization->events as $event)
                    <div class="px-6 py-4">
                        <h5 class="text-sm font-medium text-gray-900 truncate">{{ $event->title }}</h5>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $event->start_date ? \Carbon\Carbon::parse($event->start_date)->format('M j, Y') : 'No date' }}
                        </p>
                    </div>
                @empty
                    <div class="px-6 py-4 text-sm text-gray-500">No events yet</div>
                @endforelse
            </div>
        </div>

        <!-- Recent News -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h4 class="text-lg font-medium text-gray-900">Recent News</h4>
                    <a href="{{ route('admin.news.index', ['organization_id' => $organization->id]) }}" 
                       class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
                </div>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($organization->news as $news)
                    <div class="px-6 py-4">
                        <h5 class="text-sm font-medium text-gray-900 truncate">{{ $news->title }}</h5>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $news->created ? \Carbon\Carbon::parse($news->created)->format('M j, Y') : 'Unknown date' }}
                        </p>
                    </div>
                @empty
                    <div class="px-6 py-4 text-sm text-gray-500">No news yet</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Resources -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h4 class="text-lg font-medium text-gray-900">Recent Resources</h4>
                    <a href="{{ route('admin.resources.index', ['organization_id' => $organization->id]) }}" 
                       class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
                </div>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($organization->resources as $resource)
                    <div class="px-6 py-4">
                        <h5 class="text-sm font-medium text-gray-900 truncate">{{ $resource->title }}</h5>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $resource->created ? \Carbon\Carbon::parse($resource->created)->format('M j, Y') : 'Unknown date' }}
                        </p>
                    </div>
                @empty
                    <div class="px-6 py-4 text-sm text-gray-500">No resources yet</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection