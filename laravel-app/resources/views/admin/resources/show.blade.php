@extends('layouts.admin')

@section('title', 'Resource Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $resource->title }}</h1>
                <p class="text-gray-600">Resource details and files</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.resources.edit', $resource) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Resource
                </a>
                <a href="{{ route('admin.resources.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Resources
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Resource Information -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Resource Information</h2>
                
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Title</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $resource->title }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $resource->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $resource->status ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    
                    @if($resource->organization)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Organization</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('admin.organizations.show', $resource->organization) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    {{ $resource->organization->name }}
                                </a>
                            </dd>
                        </div>
                    @endif
                    
                    @if($resource->resourceType)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Resource Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $resource->resourceType->name }}</dd>
                        </div>
                    @endif
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $resource->created->format('M j, Y g:i A') }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Modified</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $resource->modified->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Categories -->
            @if($resource->categories->count() > 0)
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Categories</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($resource->categories as $category)
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Files -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Files ({{ $resource->files->count() }})</h2>
                    <a href="{{ route('admin.resources.edit', $resource) }}" 
                       class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i>
                        Add Files
                    </a>
                </div>
                
                @if($resource->files->count() > 0)
                    <div class="space-y-3">
                        @foreach($resource->files->sortBy('sort_order') as $file)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <div class="flex items-center space-x-4">
                                    @if($file->isImage())
                                        <img src="{{ $file->thumbnail_url }}" 
                                             alt="{{ $file->original_name }}" 
                                             class="w-12 h-12 object-cover rounded-lg">
                                    @else
                                        <div class="w-12 h-12 flex items-center justify-center bg-gray-100 rounded-lg">
                                            <i class="{{ $file->getFileTypeIconUrl() }} text-xl"></i>
                                        </div>
                                    @endif
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $file->original_name }}
                                            @if($file->is_primary)
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    Primary
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $file->formatted_size }} • 
                                            {{ $file->download_count }} downloads • 
                                            {{ $file->created->format('M j, Y') }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('api.resource-files.download', $file) }}" 
                                       class="p-2 text-gray-400 hover:text-blue-600"
                                       title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form action="{{ route('admin.resources.files.delete', [$resource, $file]) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 text-gray-400 hover:text-red-600"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No files uploaded yet</p>
                        <a href="{{ route('admin.resources.edit', $resource) }}" 
                           class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i>
                            Add Files
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('admin.resources.edit', $resource) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Resource
                    </a>
                    
                    <form action="{{ route('admin.resources.toggle-status', $resource) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-{{ $resource->status ? 'eye-slash' : 'eye' }} mr-2"></i>
                            {{ $resource->status ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    
                    <form action="{{ route('admin.resources.destroy', $resource) }}" 
                          method="POST" 
                          onsubmit="return confirm('Are you sure you want to delete this resource? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Resource
                        </button>
                    </form>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Statistics</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Files</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $resource->files->count() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Downloads</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $resource->files->sum('download_count') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Categories</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $resource->categories->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection