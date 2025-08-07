<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Country;
use App\Models\City;
use App\Models\Region;
use App\Models\VolunteeringCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends AdminController
{
    /**
     * Display a listing of events
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = Event::with(['organization', 'country', 'city', 'region']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $events = $query->paginate(20)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $countries = Country::where('status', 1)->orderBy('nicename')->get();

        return view('admin.events.index', compact('events', 'organizations', 'countries'));
    }

    /**
     * Display the specified event
     */
    public function show(Event $event)
    {
        $this->shareViewData();

        $event->load(['organization', 'country', 'city', 'region', 'categories']);

        return view('admin.events.show', compact('event'));
    }

    /**
     * Toggle event status
     */
    public function toggleStatus(Event $event)
    {
        try {
            $event->update(['status' => $event->status === 1 ? 0 : 1]);
            
            $status = $event->status === 1 ? 'activated' : 'deactivated';
            return back()->with('success', "Event {$status} successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update event status.']);
        }
    }
}