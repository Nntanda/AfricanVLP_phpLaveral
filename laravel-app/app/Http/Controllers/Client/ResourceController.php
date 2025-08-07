<?php

namespace App\Http\Controllers\Client;

use App\Models\Resource;
use App\Models\Organization;
use App\Models\ResourceType;
use App\Models\CategoryOfResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ResourceController extends ClientController
{
    /**
     * Display a listing of resources
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = Resource::with(['organization', 'resourceType', 'files'])
            ->where('status', 1)
            ->orderBy('created', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        // Filter by organization
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        // Filter by resource type
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('category_of_resources.id', $request->category_id);
            });
        }

        $resources = $query->paginate(12)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 1)
            ->whereHas('resources', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        $resourceTypes = ResourceType::where('status', 1)
            ->whereHas('resources', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        $categories = CategoryOfResource::where('status', 1)
            ->whereHas('resources', function ($q) {
                $q->where('status', 1);
            })
            ->orderBy('name')
            ->get();

        return view('client.resources.index', compact('resources', 'organizations', 'resourceTypes', 'categories'));
    }

    /**
     * Display the specified resource
     */
    public function show(Resource $resource)
    {
        $this->shareViewData();

        // Check if resource is published
        if ($resource->status !== 1) {
            abort(404);
        }

        $resource->load(['organization', 'resourceType', 'categories', 'files']);

        // Get related resources
        $relatedResources = Resource::where('status', 1)
            ->where('id', '!=', $resource->id)
            ->when($resource->organization_id, function ($query) use ($resource) {
                $query->where('organization_id', $resource->organization_id);
            })
            ->when($resource->resource_type_id, function ($query) use ($resource) {
                $query->orWhere('resource_type_id', $resource->resource_type_id);
            })
            ->orderBy('created', 'desc')
            ->limit(3)
            ->get();

        return view('client.resources.show', compact('resource', 'relatedResources'));
    }

    /**
     * Download a resource file
     */
    public function download(Resource $resource, Request $request)
    {
        // Check if resource is published
        if ($resource->status !== 1) {
            abort(404);
        }

        // Get the file to download
        $fileId = $request->get('file_id');
        
        if ($fileId) {
            // Download specific file
            $file = $resource->files()->find($fileId);
            if (!$file) {
                abort(404, 'File not found');
            }
            $fileUrl = $file->file_link;
            $fileName = $resource->title . '.' . $file->file_type;
        } else {
            // Download main file
            if (!$resource->file_link) {
                abort(404, 'No file available for download');
            }
            $fileUrl = $resource->file_link;
            $fileName = $resource->title . '.' . ($resource->file_type ?? 'file');
        }

        try {
            // If it's a local storage URL, serve the file
            if (str_starts_with($fileUrl, '/storage/')) {
                $filePath = str_replace('/storage/', '', $fileUrl);
                
                if (!Storage::disk('public')->exists($filePath)) {
                    abort(404, 'File not found');
                }

                $fileContent = Storage::disk('public')->get($filePath);
                $mimeType = Storage::disk('public')->mimeType($filePath);

                return Response::make($fileContent, 200, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Content-Length' => strlen($fileContent),
                ]);
            }

            // For external URLs (like Cloudinary), redirect to the URL
            return redirect($fileUrl);

        } catch (\Exception $e) {
            abort(500, 'Error downloading file');
        }
    }

    /**
     * Track resource access (for future analytics)
     */
    protected function trackResourceAccess(Resource $resource, string $action = 'view')
    {
        // This can be implemented later for analytics
        // For now, we'll just log the access
        \Log::info('Resource accessed', [
            'resource_id' => $resource->id,
            'user_id' => $this->getAuthUser()->id,
            'action' => $action,
            'timestamp' => now()
        ]);
    }
}