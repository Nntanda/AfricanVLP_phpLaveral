<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;
use App\Services\GeographicService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    protected $geographicService;

    public function __construct(GeographicService $geographicService)
    {
        $this->geographicService = $geographicService;
    }

    /**
     * Display map view with events and organizations
     */
    public function index(Request $request)
    {
        $filters = $request->only(['type', 'country_id', 'city_id', 'category_id', 'search']);
        
        // Get events and organizations with coordinates
        $events = Event::with(['organization', 'country', 'city'])
            ->hasCoordinates()
            ->where('status', 1)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->where('country_id', $request->country_id);
            })
            ->limit(100)
            ->get();

        $organizations = Organization::with(['country', 'city', 'category'])
            ->hasCoordinates()
            ->where('status', 1)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->where('country_id', $request->country_id);
            })
            ->limit(100)
            ->get();

        // Calculate map bounds
        $allCoordinates = collect();
        
        foreach ($events as $event) {
            if ($event->hasCoordinates()) {
                $allCoordinates->push($event->coordinates);
            }
        }
        
        foreach ($organizations as $organization) {
            if ($organization->hasCoordinates()) {
                $allCoordinates->push($organization->coordinates);
            }
        }

        $bounds = $this->geographicService->getBounds($allCoordinates->toArray());

        return view('map.index', compact('events', 'organizations', 'bounds', 'filters'));
    }

    /**
     * Get nearby events and organizations via API
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'type' => 'nullable|in:events,organizations,both'
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->get('radius', 25); // Default 25km
        $type = $request->get('type', 'both');

        $results = [];

        if (in_array($type, ['events', 'both'])) {
            $nearbyEvents = Event::with(['organization', 'country', 'city'])
                ->nearby($latitude, $longitude, $radius)
                ->where('status', 1)
                ->get()
                ->map(function ($event) use ($latitude, $longitude) {
                    if ($event->hasCoordinates()) {
                        $event->distance = $this->geographicService->calculateDistance(
                            $latitude,
                            $longitude,
                            $event->latitude,
                            $event->longitude
                        );
                    }
                    return $event;
                })
                ->sortBy('distance');

            $results['events'] = $nearbyEvents->values();
        }

        if (in_array($type, ['organizations', 'both'])) {
            $nearbyOrganizations = Organization::with(['country', 'city', 'category'])
                ->nearby($latitude, $longitude, $radius)
                ->where('status', 1)
                ->get()
                ->map(function ($organization) use ($latitude, $longitude) {
                    if ($organization->hasCoordinates()) {
                        $organization->distance = $this->geographicService->calculateDistance(
                            $latitude,
                            $longitude,
                            $organization->latitude,
                            $organization->longitude
                        );
                    }
                    return $organization;
                })
                ->sortBy('distance');

            $results['organizations'] = $nearbyOrganizations->values();
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'center' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'radius' => $radius
        ]);
    }

    /**
     * Geocode an address
     */
    public function geocode(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|max:255'
        ]);

        $result = $this->geographicService->geocodeAddress($request->address);

        return response()->json($result);
    }

    /**
     * Reverse geocode coordinates
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $result = $this->geographicService->reverseGeocode(
            $request->latitude,
            $request->longitude
        );

        return response()->json($result);
    }

    /**
     * Get static map URL
     */
    public function staticMap(Request $request): JsonResponse
    {
        $request->validate([
            'center.latitude' => 'required|numeric|between:-90,90',
            'center.longitude' => 'required|numeric|between:-180,180',
            'zoom' => 'nullable|integer|between:1,20',
            'size' => 'nullable|string',
            'markers' => 'nullable|array',
            'markers.*.latitude' => 'required_with:markers|numeric|between:-90,90',
            'markers.*.longitude' => 'required_with:markers|numeric|between:-180,180',
            'markers.*.color' => 'nullable|string',
            'markers.*.size' => 'nullable|string'
        ]);

        $options = [
            'center' => $request->center,
            'zoom' => $request->get('zoom', 10),
            'size' => $request->get('size', '600x400'),
            'markers' => $request->get('markers', [])
        ];

        $mapUrl = $this->geographicService->generateStaticMapUrl($options);

        return response()->json([
            'success' => true,
            'map_url' => $mapUrl
        ]);
    }

    /**
     * Get events map data for JavaScript
     */
    public function eventsMapData(Request $request): JsonResponse
    {
        $events = Event::with(['organization', 'country', 'city'])
            ->hasCoordinates()
            ->where('status', 1)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->where('country_id', $request->country_id);
            })
            ->when($request->filled('upcoming'), function ($query) {
                $query->where('start_date', '>', now());
            })
            ->limit(200)
            ->get();

        $mapData = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => substr($event->description, 0, 150) . '...',
                'latitude' => (float) $event->latitude,
                'longitude' => (float) $event->longitude,
                'start_date' => $event->start_date->format('Y-m-d H:i'),
                'end_date' => $event->end_date->format('Y-m-d H:i'),
                'location' => $event->full_address,
                'organization' => [
                    'id' => $event->organization->id,
                    'name' => $event->organization->name,
                    'logo' => $event->organization->logo
                ],
                'url' => route('client.events.show', $event->id),
                'type' => 'event'
            ];
        });

        return response()->json([
            'success' => true,
            'events' => $mapData,
            'total' => $mapData->count()
        ]);
    }

    /**
     * Get organizations map data for JavaScript
     */
    public function organizationsMapData(Request $request): JsonResponse
    {
        $organizations = Organization::with(['country', 'city', 'category'])
            ->hasCoordinates()
            ->where('status', 1)
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->where('country_id', $request->country_id);
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->category_id);
            })
            ->limit(200)
            ->get();

        $mapData = $organizations->map(function ($organization) {
            return [
                'id' => $organization->id,
                'name' => $organization->name,
                'about' => substr($organization->about, 0, 150) . '...',
                'latitude' => (float) $organization->latitude,
                'longitude' => (float) $organization->longitude,
                'address' => $organization->full_address,
                'website' => $organization->website,
                'phone' => $organization->phone_number,
                'email' => $organization->email,
                'logo' => $organization->logo,
                'category' => $organization->categoryOfOrganization?->name,
                'url' => route('client.organizations.show', $organization->id),
                'type' => 'organization'
            ];
        });

        return response()->json([
            'success' => true,
            'organizations' => $mapData,
            'total' => $mapData->count()
        ]);
    }

    /**
     * Update coordinates for an event
     */
    public function updateEventCoordinates(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        try {
            // Get timezone for the coordinates
            $timezoneResult = $this->geographicService->getTimezone(
                $request->latitude,
                $request->longitude
            );

            $updateData = [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ];

            if ($timezoneResult['success']) {
                $updateData['timezone'] = $timezoneResult['timezone_id'];
            }

            $event->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Event coordinates updated successfully',
                'coordinates' => $event->coordinates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event coordinates'
            ], 500);
        }
    }

    /**
     * Update coordinates for an organization
     */
    public function updateOrganizationCoordinates(Request $request, Organization $organization): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        try {
            // Get timezone for the coordinates
            $timezoneResult = $this->geographicService->getTimezone(
                $request->latitude,
                $request->longitude
            );

            $updateData = [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ];

            if ($timezoneResult['success']) {
                $updateData['timezone'] = $timezoneResult['timezone_id'];
            }

            $organization->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Organization coordinates updated successfully',
                'coordinates' => $organization->coordinates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update organization coordinates'
            ], 500);
        }
    }
}