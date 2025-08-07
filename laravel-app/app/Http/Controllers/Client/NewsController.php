<?php

namespace App\Http\Controllers\Client;

use App\Models\News;
use App\Models\Organization;
use App\Models\Region;
use App\Models\VolunteeringCategory;
use Illuminate\Http\Request;

class NewsController extends ClientController
{
    /**
     * Display a listing of news
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = News::with(['organization', 'region', 'categories'])
            ->where('status', 1)
            ->orderBy('created', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filter by organization
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filter by region
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('volunteering_categories.id', $request->category_id);
            });
        }

        $news = $query->paginate(12)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 1)
            ->whereHas('news', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        $regions = Region::where('status', 1)
            ->whereHas('news', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        $categories = VolunteeringCategory::where('status', 1)
            ->whereHas('news', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        return view('client.news.index', compact('news', 'organizations', 'regions', 'categories'));
    }

    /**
     * Display the specified news
     */
    public function show(News $news)
    {
        $this->shareViewData();

        // Check if news is published
        if ($news->status !== 1) {
            abort(404);
        }

        $news->load(['organization', 'region', 'categories', 'publishingCategories']);

        // Get related news
        $relatedNews = News::where('status', 1)
            ->where('id', '!=', $news->id)
            ->when($news->organization_id, function ($query) use ($news) {
                $query->where('organization_id', $news->organization_id);
            })
            ->when($news->region_id, function ($query) use ($news) {
                $query->orWhere('region_id', $news->region_id);
            })
            ->orderBy('created', 'desc')
            ->limit(3)
            ->get();

        return view('client.news.show', compact('news', 'relatedNews'));
    }
}