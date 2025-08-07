<?php

namespace App\Http\Controllers\Admin;

use App\Models\News;
use App\Models\Organization;
use App\Models\Region;
use App\Models\VolunteeringCategory;
use App\Models\PublishingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsController extends AdminController
{
    /**
     * Display a listing of news
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = News::with(['organization', 'region']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by organization
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filter by region
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $news = $query->paginate(20)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $regions = Region::where('status', 1)->orderBy('name')->get();

        return view('admin.news.index', compact('news', 'organizations', 'regions'));
    }

    /**
     * Show the form for creating new news
     */
    public function create()
    {
        $this->shareViewData();

        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $regions = Region::where('status', 1)->orderBy('name')->get();
        $categories = VolunteeringCategory::where('status', 1)->orderBy('name')->get();
        $publishingCategories = PublishingCategory::where('status', 1)->orderBy('name')->get();

        return view('admin.news.create', compact('organizations', 'regions', 'categories', 'publishingCategories'));
    }

    /**
     * Store newly created news
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'organization_id' => 'nullable|exists:organizations,id',
            'region_id' => 'nullable|exists:regions,id',
            'status' => 'required|integer|in:0,1',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:volunteering_categories,id',
            'publishing_categories' => 'nullable|array',
            'publishing_categories.*' => 'exists:publishing_categories,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $news = News::create([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'content' => $request->content,
                'organization_id' => $request->organization_id,
                'region_id' => $request->region_id,
                'status' => $request->status,
            ]);

            // Attach categories if provided
            if ($request->filled('categories')) {
                $news->categories()->attach($request->categories);
            }

            // Attach publishing categories if provided
            if ($request->filled('publishing_categories')) {
                $news->publishingCategories()->attach($request->publishing_categories);
            }

            return redirect()->route('admin.news.show', $news)
                ->with('success', 'News article created successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create news article. Please try again.'])->withInput();
        }
    }

    /**
     * Display the specified news
     */
    public function show(News $news)
    {
        $this->shareViewData();

        $news->load(['organization', 'region', 'categories', 'publishingCategories']);

        return view('admin.news.show', compact('news'));
    }

    /**
     * Show the form for editing the specified news
     */
    public function edit(News $news)
    {
        $this->shareViewData();

        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $regions = Region::where('status', 1)->orderBy('name')->get();
        $categories = VolunteeringCategory::where('status', 1)->orderBy('name')->get();
        $publishingCategories = PublishingCategory::where('status', 1)->orderBy('name')->get();

        $news->load(['categories', 'publishingCategories']);

        return view('admin.news.edit', compact('news', 'organizations', 'regions', 'categories', 'publishingCategories'));
    }

    /**
     * Update the specified news
     */
    public function update(Request $request, News $news)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'organization_id' => 'nullable|exists:organizations,id',
            'region_id' => 'nullable|exists:regions,id',
            'status' => 'required|integer|in:0,1',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:volunteering_categories,id',
            'publishing_categories' => 'nullable|array',
            'publishing_categories.*' => 'exists:publishing_categories,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $news->update([
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'content' => $request->content,
                'organization_id' => $request->organization_id,
                'region_id' => $request->region_id,
                'status' => $request->status,
            ]);

            // Sync categories
            if ($request->filled('categories')) {
                $news->categories()->sync($request->categories);
            } else {
                $news->categories()->detach();
            }

            // Sync publishing categories
            if ($request->filled('publishing_categories')) {
                $news->publishingCategories()->sync($request->publishing_categories);
            } else {
                $news->publishingCategories()->detach();
            }

            return redirect()->route('admin.news.show', $news)
                ->with('success', 'News article updated successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update news article. Please try again.'])->withInput();
        }
    }

    /**
     * Remove the specified news
     */
    public function destroy(News $news)
    {
        try {
            $news->delete();
            return redirect()->route('admin.news.index')
                ->with('success', 'News article deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete news article. Please try again.']);
        }
    }

    /**
     * Toggle news status
     */
    public function toggleStatus(News $news)
    {
        try {
            $news->update(['status' => $news->status === 1 ? 0 : 1]);
            
            $status = $news->status === 1 ? 'published' : 'unpublished';
            return back()->with('success', "News article {$status} successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update news status.']);
        }
    }

    /**
     * Publish news article
     */
    public function publish(News $news)
    {
        try {
            $news->update(['status' => 1]);
            return back()->with('success', 'News article published successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to publish news article.']);
        }
    }
}