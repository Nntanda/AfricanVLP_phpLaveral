@extends('layouts.admin')

@section('title', 'Organizations')
@section('page-title', 'Organization Management')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-medium text-gray-900">All Organizations</h3>
            <p class="text-sm text-gray-500">Manage organizations and their settings</p>
        </div>
        <a href="{{ route('admin.organizations.create') }}" 
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            <i class="fas fa-plus mr-2"></i>Add Organization
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h4 class="text-sm font-medium text-gray-900">Filters</h4>
        </div>
        <form method="GET" class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" id="search" 
                           value="{{ request('search') }}"
                           placeholder="Name or website..."
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category_id" id="category_id" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Country -->
                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country</label>
                    <select name="country_id" id="country_id" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                {{ $country->nicename }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex space-x-3">
                <button type="submit" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-search mr-2"></i>Filter
                </button>
                <a href="{{ route('admin.organizations.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Organizations Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-medium text-gray-900">
                    {{ $organizations->total() }} Organizations Found
                </h4>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <span>Sort by:</span>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'created', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-indigo-600 hover:text-indigo-500">
                        Date {{ request('sort') === 'created' && request('direction') === 'asc' ? '↑' : '↓' }}
                    </a>
                    <span>|</span>
                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}" 
                       class="text-indigo-600 hover:text-indigo-500">
                        Name {{ request('sort') === 'name' && request('direction') === 'asc' ? '↑' : '↓' }}
                    </a>
                </div>
            </div>
        </div>

        @if($organizations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Organization
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($organizations as $organization)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        @if($organization->logo)
                                            <img class="h-10 w-10 rounded-lg object-cover" 
                                                 src="{{ $organization->logo }}" 
                                                 alt="{{ $organization->name }}">
                                        @else
                                            <div class="h-10 w-10 bg-gray-300 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-building text-gray-500"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $organization->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            @if($organization->website)
                                                <a href="{{ $organization->website }}" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                                    {{ $organization->website }}
                                                </a>
                                            @else
                                                No website
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($organization->categoryOfOrganization)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $organization->categoryOfOrganization->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm">Uncategorized</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $organization->status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $organization->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $organization->country->nicename ?? 'Unknown' }}
                                @if($organization->city)
                                    <br><span class="text-xs text-gray-400">{{ $organization->city->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $organization->created ? \Carbon\Carbon::parse($organization->created)->format('M j, Y') : 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.organizations.show', $organization) }}" 
                                       class="text-indigo-600 hover:text-indigo-900" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.organizations.members', $organization) }}" 
                                       class="text-blue-600 hover:text-blue-900" title="Members">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="{{ route('admin.organizations.edit', $organization) }}" 
                                       class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.organizations.toggle-status', $organization) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="text-{{ $organization->status === 1 ? 'red' : 'green' }}-600 hover:text-{{ $organization->status === 1 ? 'red' : 'green' }}-900"
                                                title="{{ $organization->status === 1 ? 'Deactivate' : 'Activate' }}"
                                                onclick="return confirm('Are you sure you want to {{ $organization->status === 1 ? 'deactivate' : 'activate' }} this organization?')">
                                            <i class="fas fa-{{ $organization->status === 1 ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $organizations->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <i class="fas fa-building text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No organizations found</h3>
                <p class="text-gray-500 mb-4">No organizations match your current filters.</p>
                <a href="{{ route('admin.organizations.create') }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Add First Organization
                </a>
            </div>
        @endif
    </div>
</div>
@endsection