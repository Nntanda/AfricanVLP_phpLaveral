@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Users Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-users text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_users']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium">{{ number_format($stats['active_users']) }}</span>
                    <span class="text-gray-500">active users</span>
                </div>
            </div>
        </div>

        <!-- Organizations Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-building text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Organizations</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_organizations']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium">{{ number_format($stats['active_organizations']) }}</span>
                    <span class="text-gray-500">active</span>
                </div>
            </div>
        </div>

        <!-- Events Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Events</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_events']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-blue-600 font-medium">{{ number_format($stats['upcoming_events']) }}</span>
                    <span class="text-gray-500">upcoming</span>
                </div>
            </div>
        </div>

        <!-- Content Card -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <i class="fas fa-newspaper text-white"></i>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Content Items</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_news'] + $stats['total_blog_posts'] + $stats['total_resources']) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium">{{ number_format($stats['published_news'] + $stats['published_blog_posts'] + $stats['active_resources']) }}</span>
                    <span class="text-gray-500">published</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- User Growth Chart -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">User Growth</h3>
                <p class="text-sm text-gray-500">Monthly user registrations over the past year</p>
            </div>
            <div class="p-6">
                <canvas id="userGrowthChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Organizations by Country -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Organizations by Country</h3>
                <p class="text-sm text-gray-500">Top 10 countries with most organizations</p>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($organizationStats as $stat)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900">{{ $stat->country }}</span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($stat->count / $organizationStats->first()->count) * 100 }}%"></div>
                            </div>
                            <span class="text-sm text-gray-500">{{ $stat->count }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Users -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Users</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentUsers as $user)
                <div class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <img class="h-8 w-8 rounded-full bg-gray-300" 
                             src="{{ $user->profile_picture ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->first_name . ' ' . $user->last_name) }}" 
                             alt="{{ $user->first_name }} {{ $user->last_name }}">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $user->first_name }} {{ $user->last_name }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $user->country->nicename ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $user->created ? \Carbon\Carbon::parse($user->created)->diffForHumans() : 'Unknown' }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-4 text-sm text-gray-500">No recent users</div>
                @endforelse
            </div>
            <div class="px-6 py-3 bg-gray-50">
                <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all users →
                </a>
            </div>
        </div>

        <!-- Recent Organizations -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Organizations</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentOrganizations as $organization)
                <div class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 bg-gray-300 rounded-md flex items-center justify-center">
                            @if($organization->logo)
                                <img src="{{ $organization->logo }}" alt="{{ $organization->name }}" class="h-8 w-8 rounded-md">
                            @else
                                <i class="fas fa-building text-gray-500"></i>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $organization->name }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $organization->country->nicename ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $organization->created ? \Carbon\Carbon::parse($organization->created)->diffForHumans() : 'Unknown' }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-4 text-sm text-gray-500">No recent organizations</div>
                @endforelse
            </div>
            <div class="px-6 py-3 bg-gray-50">
                <a href="{{ route('admin.organizations.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all organizations →
                </a>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Events</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentEvents as $event)
                <div class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 bg-yellow-100 rounded-md flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-yellow-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $event->title }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $event->organization->name ?? 'Unknown Organization' }}
                            </p>
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $event->created ? \Carbon\Carbon::parse($event->created)->diffForHumans() : 'Unknown' }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-4 text-sm text-gray-500">No recent events</div>
                @endforelse
            </div>
            <div class="px-6 py-3 bg-gray-50">
                <a href="{{ route('admin.events.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all events →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// User Growth Chart
const ctx = document.getElementById('userGrowthChart').getContext('2d');
const userGrowthChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($userGrowthData['months']),
        datasets: [{
            label: 'New Users',
            data: @json($userGrowthData['user_counts']),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
@endpush