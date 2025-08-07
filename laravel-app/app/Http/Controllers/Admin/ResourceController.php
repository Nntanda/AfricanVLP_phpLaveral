<?php

namespace App\Http\Controllers\Admin;

use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\Organization;
use App\Models\ResourceType;
use App\Models\CategoryOfResource;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ResourceController extends AdminController
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        parent::__construct();
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of resources
     */
    public function index(Request $request)
    {
        $this->shareViewData();

        $query = Resource::with(['organization', 'resourceType', 'files']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
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

        // Filter by resource type
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->resource_type_id);
        }

        // Sorting
        $sortBy = $request->get('sort', 'created');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $resources = $query->paginate(20)->withQueryString();

        // Get filter options
        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $resourceTypes = ResourceType::where('status', 1)->orderBy('name')->get();

        return view('admin.resources.index', compact('resources', 'organizations', 'resourceTypes'));
    }

    /**
     * Show the form for creating a new resource
     */
    public function create()
    {
        $this->shareViewData();

        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $resourceTypes = ResourceType::where('status', 1)->orderBy('name')->get();
        $categories = CategoryOfResource::where('status', 1)->orderBy('name')->get();

        return view('admin.resources.create', compact('organizations', 'resourceTypes', 'categories'));
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'organization_id' => 'nullable|exists:organizations,id',
            'resource_type_id' => 'nullable|exists:resource_types,id',
            'status' => 'required|integer|in:0,1',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240', // 10MB max per file
            'categories' => 'nullable|array',
            'categories.*' => 'exists:category_of_resources,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        
        try {
            // Create the resource
            $resource = Resource::create([
                'title' => $request->title,
                'organization_id' => $request->organization_id,
                'resource_type_id' => $request->resource_type_id,
                'status' => $request->status,
            ]);

            // Handle file uploads
            if ($request->hasFile('files')) {
                $uploadResults = $this->fileUploadService->uploadMultipleFiles(
                    $request->file('files'),
                    'resources'
                );

                foreach ($uploadResults['results'] as $index => $result) {
                    if ($result['success']) {
                        ResourceFile::create([
                            'resource_id' => $resource->id,
                            'filename' => $result['filename'],
                            'original_name' => $result['original_name'],
                            'file_path' => $result['path'] ?? null,
                            'cloudinary_public_id' => $result['public_id'] ?? null,
                            'storage_type' => $result['storage_type'],
                            'mime_type' => $result['mime_type'],
                            'file_extension' => $result['extension'],
                            'file_size' => $result['size'],
                            'metadata' => [
                                'width' => $result['width'] ?? null,
                                'height' => $result['height'] ?? null,
                                'format' => $result['format'] ?? null,
                            ],
                            'is_primary' => $index === 0,
                            'sort_order' => $index,
                        ]);
                    }
                }

                // Update main resource with first file link for backward compatibility
                $firstSuccessfulUpload = collect($uploadResults['results'])->first(function ($result) {
                    return $result['success'];
                });

                if ($firstSuccessfulUpload) {
                    $resource->update([
                        'file_link' => $firstSuccessfulUpload['url'],
                        'file_type' => $firstSuccessfulUpload['extension'],
                    ]);
                }
            }

            // Attach categories if provided
            if ($request->filled('categories')) {
                $resource->categories()->attach($request->categories);
            }

            DB::commit();

            return redirect()->route('admin.resources.show', $resource)
                ->with('success', 'Resource created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create resource. Please try again.'])->withInput();
        }
    }

    /**
     * Display the specified resource
     */
    public function show(Resource $resource)
    {
        $this->shareViewData();

        $resource->load(['organization', 'resourceType', 'categories', 'files']);

        return view('admin.resources.show', compact('resource'));
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(Resource $resource)
    {
        $this->shareViewData();

        $organizations = Organization::where('status', 1)->orderBy('name')->get();
        $resourceTypes = ResourceType::where('status', 1)->orderBy('name')->get();
        $categories = CategoryOfResource::where('status', 1)->orderBy('name')->get();

        $resource->load(['categories', 'files']);

        return view('admin.resources.edit', compact('resource', 'organizations', 'resourceTypes', 'categories'));
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, Resource $resource)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'organization_id' => 'nullable|exists:organizations,id',
            'resource_type_id' => 'nullable|exists:resource_types,id',
            'status' => 'required|integer|in:0,1',
            'files' => 'nullable|array',
            'files.*' => 'file|max:10240', // 10MB max per file
            'categories' => 'nullable|array',
            'categories.*' => 'exists:category_of_resources,id',
            'remove_files' => 'nullable|array',
            'remove_files.*' => 'exists:resource_files,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        
        try {
            // Update the resource
            $resource->update([
                'title' => $request->title,
                'organization_id' => $request->organization_id,
                'resource_type_id' => $request->resource_type_id,
                'status' => $request->status,
            ]);

            // Remove selected files
            if ($request->filled('remove_files')) {
                $filesToRemove = ResourceFile::whereIn('id', $request->remove_files)
                    ->where('resource_id', $resource->id)
                    ->get();

                foreach ($filesToRemove as $file) {
                    // Delete physical file
                    $this->fileUploadService->deleteFile(
                        $file->storage_type === 'cloudinary' ? $file->cloudinary_public_id : $file->file_path,
                        $file->storage_type
                    );
                    // Delete database record
                    $file->delete();
                }
            }

            // Handle new file uploads
            if ($request->hasFile('files')) {
                $uploadResults = $this->fileUploadService->uploadMultipleFiles(
                    $request->file('files'),
                    'resources'
                );

                foreach ($uploadResults['results'] as $index => $result) {
                    if ($result['success']) {
                        $existingFilesCount = $resource->files()->count();
                        ResourceFile::create([
                            'resource_id' => $resource->id,
                            'filename' => $result['filename'],
                            'original_name' => $result['original_name'],
                            'file_path' => $result['path'] ?? null,
                            'cloudinary_public_id' => $result['public_id'] ?? null,
                            'storage_type' => $result['storage_type'],
                            'mime_type' => $result['mime_type'],
                            'file_extension' => $result['extension'],
                            'file_size' => $result['size'],
                            'metadata' => [
                                'width' => $result['width'] ?? null,
                                'height' => $result['height'] ?? null,
                                'format' => $result['format'] ?? null,
                            ],
                            'is_primary' => $existingFilesCount === 0 && $index === 0,
                            'sort_order' => $existingFilesCount + $index,
                        ]);
                    }
                }
            }

            // Update main resource file link if needed
            $primaryFile = $resource->files()->where('is_primary', true)->first();
            if ($primaryFile) {
                $resource->update([
                    'file_link' => $primaryFile->url,
                    'file_type' => $primaryFile->file_extension,
                ]);
            }

            // Sync categories
            if ($request->filled('categories')) {
                $resource->categories()->sync($request->categories);
            } else {
                $resource->categories()->detach();
            }

            DB::commit();

            return redirect()->route('admin.resources.show', $resource)
                ->with('success', 'Resource updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update resource. Please try again.'])->withInput();
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Resource $resource)
    {
        DB::beginTransaction();
        
        try {
            // Delete all associated files
            foreach ($resource->files as $file) {
                $this->fileUploadService->deleteFile(
                    $file->storage_type === 'cloudinary' ? $file->cloudinary_public_id : $file->file_path,
                    $file->storage_type
                );
                $file->delete();
            }

            // Delete the resource
            $resource->delete();

            DB::commit();

            return redirect()->route('admin.resources.index')
                ->with('success', 'Resource deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete resource. Please try again.']);
        }
    }

    /**
     * Toggle resource status
     */
    public function toggleStatus(Resource $resource)
    {
        try {
            $resource->update(['status' => $resource->status === 1 ? 0 : 1]);
            
            $status = $resource->status === 1 ? 'activated' : 'deactivated';
            return back()->with('success', "Resource {$status} successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update resource status.']);
        }
    }

    /**
     * Delete a specific file from a resource
     */
    public function deleteFile(Resource $resource, ResourceFile $file)
    {
        // Ensure the file belongs to the resource
        if ($file->resource_id !== $resource->id) {
            return back()->withErrors(['error' => 'File does not belong to this resource.']);
        }

        try {
            // Delete physical file
            $this->fileUploadService->deleteFile(
                $file->storage_type === 'cloudinary' ? $file->cloudinary_public_id : $file->file_path,
                $file->storage_type
            );
            
            // Delete database record
            $file->delete();

            // Update main resource file link if this was the primary file
            if ($file->is_primary) {
                $newPrimaryFile = $resource->files()->first();
                if ($newPrimaryFile) {
                    $newPrimaryFile->update(['is_primary' => true]);
                    $resource->update([
                        'file_link' => $newPrimaryFile->url,
                        'file_type' => $newPrimaryFile->file_extension,
                    ]);
                } else {
                    $resource->update([
                        'file_link' => null,
                        'file_type' => null,
                    ]);
                }
            }

            return back()->with('success', 'File deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete file. Please try again.']);
        }
    }
}