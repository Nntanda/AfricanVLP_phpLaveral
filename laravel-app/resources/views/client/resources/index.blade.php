@extends('layouts.client')

@section('title', 'Resources')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Resources</h1>
                    <p class="mt-1 text-sm text-gray-500">Discover and download useful resources and documents</p>
                </div>
                <div class="text-sm text-gray-500">
                    {{ $resources->total() }} resources found
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" id="search" 
                               value="{{ request('search') }}"
                               placeholder="Search resources..."
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <!-- Organization -->
                    <div>
                        <label for="organization_id" class="block text-sm font-medium text-gray-700">Organization</label>
                        <select name="organization_id" id="organization_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Organizations</option>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}" {{ request('organization_id') == $organization->id ? 'selected' : '' }}>
                                    {{ $organization->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Resource Type -->
                    <div>
                        <label for="resource_type_id" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="resource_type_id" id="resource_type_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            @foreach($resourceTypes as $type)
                                <option value="{{ $type->id }}" {{ request('resource_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
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
                </div>

                <div class="flex space-x-3">
                    <button type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="{{ route('client.resources.index') }}" 
                       class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Resources Grid -->
    @if($resources->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($resources as $resource)
                <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div class="h-10 w-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-file-alt text-purple-600"></i>
                                </div>
                                <div>
                                    @if($resource->resourceType)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            {{ $resource->resourceType->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $resource->created ? \Carbon\Carbon::parse($resource->created)->diffForHumans() : 'Unknown date' }}
                            </span>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <a href="{{ route('client.resources.show', $resource) }}" class="hover:text-indigo-600">
                                {{ $resource->title }}
                            </a>
                        </h3>

                        <div class="flex items-center justify-between mb-4">
                            @if($resource->organization)
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-building mr-1"></i>
                                    {{ $resource->organization->name }}
                                </span>
                            @endif
                            
                            @if($resource->hasFiles())
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-paperclip mr-1"></i>
                                    {{ $resource->file_count }} {{ Str::plural('file', $resource->file_count) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                @if($resource->categories->count() > 0)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                        <i class="fas fa-tag mr-1"></i>
                                        {{ $resource->categories->first()->name }}
                                        @if($resource->categories->count() > 1)
                                            +{{ $resource->categories->count() - 1 }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="{{ route('client.resources.show', $resource) }}" 
                                   class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                    View
                                </a>
                                @if($resource->file_link || $resource->hasFiles())
                                    <a href="{{ route('client.resources.download', $resource) }}" 
                                       class="text-green-600 hover:text-green-500 text-sm font-medium">
                                        <i class="fas fa-download mr-1"></i>Download
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 rounded-lg shadow">
            {{ $resources->links() }}
        </div>
    @else
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-12 text-center">
                <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No resources found</h3>
                <p class="text-gray-500 mb-4">
                    @if(request()->hasAny(['search', 'organization_id', 'resource_type_id', 'category_id']))
                        No resources match your current filters. Try adjusting your search criteria.
                    @else
                        There are no published resources at the moment. Check back later for updates.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'organization_id', 'resource_type_id', 'category_id']))
                    <a href="{{ route('client.resources.index') }}" 
                       class="text-indigo-600 hover:text-indigo-500 font-medium">
                        Clear filters and view all resources
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection