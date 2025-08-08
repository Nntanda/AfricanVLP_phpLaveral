<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ResourceFile;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->middleware('auth');
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display file management interface.
     */
    public function index(Request $request)
    {
        $query = ResourceFile::with('uploadedBy')
            ->where('uploaded_by', Auth::id());

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by file type
        if ($request->filled('type')) {
            if ($request->type === 'images') {
                $query->where('is_image', true);
            } elseif ($request->type === 'documents') {
                $query->where('is_image', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $files = $query->orderBy('created', 'desc')
                      ->paginate(20)
                      ->withQueryString();

        // Get categories for filter
        $categories = ResourceFile::where('uploaded_by', Auth::id())
                                 ->whereNotNull('category')
                                 ->distinct()
                                 ->pluck('category');

        // Get file statistics
        $stats = [
            'total_files' => ResourceFile::where('uploaded_by', Auth::id())->count(),
            'total_size' => ResourceFile::where('uploaded_by', Auth::id())->sum('size'),
            'images_count' => ResourceFile::where('uploaded_by', Auth::id())->where('is_image', true)->count(),
            'documents_count' => ResourceFile::where('uploaded_by', Auth::id())->where('is_image', false)->count(),
        ];

        return view('client.files.index', compact('files', 'categories', 'stats'));
    }

    /**
     * Show file upload form.
     */
    public function create()
    {
        return view('client.files.upload');
    }

    /**
     * Handle file upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:10240', // 10MB max
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        $uploadedFiles = [];
        $errors = [];

        DB::transaction(function () use ($request, &$uploadedFiles, &$errors) {
            foreach ($request->file('files') as $file) {
                try {
                    // Upload file
                    $fileInfo = $this->fileUploadService->uploadFile($file, 'user-files');

                    // Create database record
                    $resourceFile = ResourceFile::create([
                        'original_name' => $fileInfo['original_name'],
                        'filename' => $fileInfo['filename'],
                        'path' => $fileInfo['path'],
                        'size' => $fileInfo['size'],
                        'mime_type' => $fileInfo['mime_type'],
                        'extension' => $fileInfo['extension'],
                        'is_image' => $fileInfo['is_image'],
                        'uploaded_by' => Auth::id(),
                        'category' => $request->category,
                        'description' => $request->description,
                        'is_public' => $request->boolean('is_public', true),
                    ]);

                    $uploadedFiles[] = $resourceFile;

                    // Generate thumbnail for images
                    if ($fileInfo['is_image']) {
                        $this->fileUploadService->generateThumbnail($fileInfo['path']);
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to upload {$file->getClientOriginalName()}: " . $e->getMessage();
                }
            }
        });

        if (count($uploadedFiles) > 0) {
            $message = count($uploadedFiles) . ' file(s) uploaded successfully.';
            if (count($errors) > 0) {
                $message .= ' However, some files failed to upload.';
            }
            
            return redirect()->route('files.index')->with('success', $message);
        } else {
            return back()->withErrors($errors)->withInput();
        }
    }

    /**
     * Display the specified file.
     */
    public function show(ResourceFile $file)
    {
        // Check if user has access to this file
        if ($file->uploaded_by !== Auth::id() && !$file->is_public) {
            abort(403, 'You do not have access to this file.');
        }

        return view('client.files.show', compact('file'));
    }

    /**
     * Download the specified file.
     */
    public function download(ResourceFile $file)
    {
        // Check if user has access to this file
        if ($file->uploaded_by !== Auth::id() && !$file->is_public) {
            abort(403, 'You do not have access to this file.');
        }

        if (!$file->exists()) {
            abort(404, 'File not found.');
        }

        // Increment download count
        $file->incrementDownloadCount();

        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    /**
     * Show the form for editing the specified file.
     */
    public function edit(ResourceFile $file)
    {
        // Check if user owns this file
        if ($file->uploaded_by !== Auth::id()) {
            abort(403, 'You can only edit your own files.');
        }

        return view('client.files.edit', compact('file'));
    }

    /**
     * Update the specified file.
     */
    public function update(Request $request, ResourceFile $file)
    {
        // Check if user owns this file
        if ($file->uploaded_by !== Auth::id()) {
            abort(403, 'You can only edit your own files.');
        }

        $request->validate([
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        $file->update([
            'category' => $request->category,
            'description' => $request->description,
            'is_public' => $request->boolean('is_public'),
            'modified' => now(),
        ]);

        return redirect()->route('files.show', $file)
                        ->with('success', 'File updated successfully.');
    }

    /**
     * Remove the specified file.
     */
    public function destroy(ResourceFile $file)
    {
        // Check if user owns this file
        if ($file->uploaded_by !== Auth::id()) {
            abort(403, 'You can only delete your own files.');
        }

        $file->deleteFile();

        return redirect()->route('files.index')
                        ->with('success', 'File deleted successfully.');
    }

    /**
     * Upload profile image.
     */
    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $fileInfo = $this->fileUploadService->uploadProfileImage(
                $request->file('image'),
                Auth::id()
            );

            // Update user profile image
            Auth::user()->update([
                'profile_image' => $fileInfo['filename'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile image updated successfully.',
                'image_url' => $fileInfo['url'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload organization logo.
     */
    public function uploadOrganizationLogo(Request $request, $organizationId)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Check if user has permission to update this organization
        $organization = Auth::user()->organizations()
                           ->where('organization_id', $organizationId)
                           ->wherePivot('role', 'admin')
                           ->first();

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this organization.',
            ], 403);
        }

        try {
            $fileInfo = $this->fileUploadService->uploadOrganizationLogo(
                $request->file('logo'),
                $organizationId
            );

            // Update organization logo
            $organization->update([
                'logo' => $fileInfo['filename'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Organization logo updated successfully.',
                'logo_url' => $fileInfo['url'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get file upload progress (for AJAX uploads).
     */
    public function uploadProgress(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        // This would typically integrate with a progress tracking system
        // For now, return a simple response
        return response()->json([
            'progress' => 100,
            'status' => 'complete',
        ]);
    }
}