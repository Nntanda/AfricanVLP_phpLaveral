<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\FileUploadService;
use App\Models\Resource;
use App\Models\ResourceFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload files for a resource
     */
    public function uploadResourceFiles(Request $request, Resource $resource): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:5',
            'files.*' => 'required|file|max:10240', // 10MB max per file
        ]);

        try {
            DB::beginTransaction();

            $uploadedFiles = [];
            $errors = [];

            foreach ($request->file('files') as $index => $file) {
                $result = $this->fileUploadService->uploadFile($file, 'resources');

                if ($result['success']) {
                    // Create ResourceFile record
                    $resourceFile = ResourceFile::create([
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
                        'is_primary' => $index === 0 && $resource->files()->count() === 0,
                        'sort_order' => $resource->files()->count() + $index,
                    ]);

                    $uploadedFiles[] = [
                        'id' => $resourceFile->id,
                        'filename' => $resourceFile->filename,
                        'original_name' => $resourceFile->original_name,
                        'url' => $resourceFile->url,
                        'thumbnail_url' => $resourceFile->thumbnail_url,
                        'size' => $resourceFile->formatted_size,
                        'is_image' => $resourceFile->isImage(),
                    ];
                } else {
                    $errors[] = "File {$index}: " . $result['error'];
                }
            }

            if (empty($uploadedFiles)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded successfully',
                    'errors' => $errors
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'files' => $uploadedFiles,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Resource file upload failed', [
                'resource_id' => $resource->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a resource file
     */
    public function deleteResourceFile(ResourceFile $resourceFile): JsonResponse
    {
        try {
            // Delete from storage
            $deleted = $this->fileUploadService->deleteFile(
                $resourceFile->storage_type === 'cloudinary' 
                    ? $resourceFile->cloudinary_public_id 
                    : $resourceFile->file_path,
                $resourceFile->storage_type
            );

            if ($deleted) {
                $resourceFile->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete file from storage'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Resource file deletion failed', [
                'file_id' => $resourceFile->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a resource file
     */
    public function downloadResourceFile(ResourceFile $resourceFile)
    {
        try {
            // Increment download count
            $resourceFile->incrementDownloadCount();

            if ($resourceFile->storage_type === 'cloudinary' && $resourceFile->cloudinary_public_id) {
                // For Cloudinary files, redirect to the URL
                return redirect($resourceFile->url);
            } elseif ($resourceFile->file_path) {
                // For local files, serve the file
                $filePath = storage_path('app/public/' . $resourceFile->file_path);
                
                if (file_exists($filePath)) {
                    return response()->download($filePath, $resourceFile->original_name);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Resource file download failed', [
                'file_id' => $resourceFile->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update file metadata
     */
    public function updateResourceFile(Request $request, ResourceFile $resourceFile): JsonResponse
    {
        $request->validate([
            'is_primary' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            // If setting as primary, unset other primary files for this resource
            if ($request->has('is_primary') && $request->is_primary) {
                ResourceFile::where('resource_id', $resourceFile->resource_id)
                    ->where('id', '!=', $resourceFile->id)
                    ->update(['is_primary' => false]);
            }

            $resourceFile->update($request->only(['is_primary', 'sort_order', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'File updated successfully',
                'file' => [
                    'id' => $resourceFile->id,
                    'is_primary' => $resourceFile->is_primary,
                    'sort_order' => $resourceFile->sort_order,
                    'is_active' => $resourceFile->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Resource file update failed', [
                'file_id' => $resourceFile->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get files for a resource
     */
    public function getResourceFiles(Resource $resource): JsonResponse
    {
        try {
            $files = $resource->files()
                ->active()
                ->ordered()
                ->get()
                ->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'filename' => $file->filename,
                        'original_name' => $file->original_name,
                        'url' => $file->url,
                        'thumbnail_url' => $file->thumbnail_url,
                        'size' => $file->formatted_size,
                        'download_count' => $file->download_count,
                        'is_image' => $file->isImage(),
                        'is_primary' => $file->is_primary,
                        'sort_order' => $file->sort_order,
                        'created' => $file->created->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'files' => $files
            ]);

        } catch (\Exception $e) {
            Log::error('Get resource files failed', [
                'resource_id' => $resource->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get resource files: ' . $e->getMessage()
            ], 500);
        }
    }
}