@extends('layouts.client')

@section('title', 'News')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Latest News</h1>
                    <p class="mt-1 text-sm text-gray-500">Stay updated with the latest news and announcements</p>
                </div>
                <div class="text-sm text-gray-500">
                    {{ $news->total() }} articles found
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
                               placeholder="Search news..."
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

                    <!-- Region -->
                    <div>
                        <label for="region_id" class="block text-sm font-medium text-gray-700">Region</label>
                        <select name="region_id" id="region_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Regions</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}" {{ request('region_id') == $region->id ? 'selected' : '' }}>
                                    {{ $region->name }}
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
                    <a href="{{ route('client.news.index') }}" 
                       class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- News Grid -->
    @if($news->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($news as $article)
                <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            @if($article->organization)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $article->organization->name }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-500">
                                {{ $article->created ? \Carbon\Carbon::parse($article->created)->diffForHumans() : 'Unknown date' }}
                            </span>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <a href="{{ route('client.news.show', $article) }}" class="hover:text-indigo-600">
                                {{ $article->title }}
                            </a>
                        </h3>

                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                            {{ Str::limit(strip_tags($article->content), 150) }}
                        </p>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                @if($article->region)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        {{ $article->region->name }}
                                    </span>
                                @endif
                                @if($article->categories->count() > 0)
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-tag mr-1"></i>
                                        {{ $article->categories->first()->name }}
                                        @if($article->categories->count() > 1)
                                            +{{ $article->categories->count() - 1 }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                            <a href="{{ route('client.news.show', $article) }}" 
                               class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Read more →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6 rounded-lg shadow">
            {{ $news->links() }}
        </div>
    @else
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-12 text-center">
                <i class="fas fa-newspaper text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No news articles found</h3>
                <p class="text-gray-500 mb-4">
                    @if(request()->hasAny(['search', 'organization_id', 'region_id', 'category_id']))
                        No articles match your current filters. Try adjusting your search criteria.
                    @else
                        There are no published news articles at the moment. Check back later for updates.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'organization_id', 'region_id', 'category_id']))
                    <a href="{{ route('client.news.index') }}" 
                       class="text-indigo-600 hover:text-indigo-500 font-medium">
                        Clear filters and view all news
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection