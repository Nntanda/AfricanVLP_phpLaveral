<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileUploadService
{
    protected $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected $allowedDocumentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    protected $maxFileSize = 10 * 1024 * 1024; // 10MB
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Upload file to Cloudinary or local storage
     */
    public function uploadFile(UploadedFile $file, string $folder = 'uploads', array $options = []): array
    {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }

            // Try Cloudinary first, fallback to local storage
            if ($this->cloudinaryService->isAvailable()) {
                $cloudinaryOptions = array_merge([
                    'folder' => "au-vlp/{$folder}",
                    'resource_type' => 'auto'
                ], $options);

                $result = $this->cloudinaryService->uploadFile($file, $cloudinaryOptions);
                
                if ($result['success']) {
                    return [
                        'success' => true,
                        'storage_type' => 'cloudinary',
                        'public_id' => $result['public_id'],
                        'url' => $result['url'],
                        'filename' => basename($result['public_id']),
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $result['bytes'],
                        'mime_type' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension(),
                        'width' => $result['width'],
                        'height' => $result['height'],
                        'format' => $result['format']
                    ];
                }
            }

            // Fallback to local storage
            $filename = $this->generateUniqueFilename($file);
            $path = $file->storeAs($folder, $filename, 'public');
            
            if (!$path) {
                return [
                    'success' => false,
                    'error' => 'Failed to store file'
                ];
            }

            return [
                'success' => true,
                'storage_type' => 'local',
                'path' => $path,
                'url' => Storage::url($path),
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'error' => 'File upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles(array $files, string $folder = 'uploads', array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $errors = [];

        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $result = $this->uploadFile($file, $folder, $options);
                $results[] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errors[] = "File {$index}: " . $result['error'];
                }
            }
        }

        return [
            'success' => $successCount > 0,
            'total_files' => count($files),
            'successful_uploads' => $successCount,
            'failed_uploads' => count($files) - $successCount,
            'results' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Upload image with optimization
     */
    public function uploadImage(UploadedFile $file, string $folder = 'images', array $options = []): array
    {
        // Validate that it's an image
        if (!$this->isImage($file)) {
            return [
                'success' => false,
                'error' => 'File must be an image'
            ];
        }

        // Set image-specific options
        $options = array_merge([
            'quality' => 85,
            'format' => 'auto',
            'crop' => 'limit'
        ], $options);

        return $this->uploadFile($file, $folder, $options);
    }

    /**
     * Delete file from Cloudinary or local storage
     */
    public function deleteFile(string $identifier, string $storageType = 'local'): bool
    {
        try {
            if ($storageType === 'cloudinary') {
                return $this->cloudinaryService->deleteFile($identifier);
            } else {
                // Local storage
                if (Storage::disk('public')->exists($identifier)) {
                    return Storage::disk('public')->delete($identifier);
                }
                return true; // File doesn't exist, consider it deleted
            }
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'identifier' => $identifier,
                'storage_type' => $storageType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile(UploadedFile $file): array
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return [
                'valid' => false,
                'error' => 'Invalid file upload'
            ];
        }

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedTypes = array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
        
        if (!in_array($extension, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes)
            ];
        }

        // Check MIME type for security
        $mimeType = $file->getMimeType();
        if (!$this->isAllowedMimeType($mimeType)) {
            return [
                'valid' => false,
                'error' => 'File type not allowed based on content'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if file is an image
     */
    protected function isImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, $this->allowedImageTypes);
    }

    /**
     * Check if MIME type is allowed
     */
    protected function isAllowedMimeType(string $mimeType): bool
    {
        $allowedMimeTypes = [
            // Images
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain'
        ];

        return in_array($mimeType, $allowedMimeTypes);
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get file info
     */
    public function getFileInfo(string $path): ?array
    {
        try {
            if (!Storage::disk('public')->exists($path)) {
                return null;
            }

            $fullPath = Storage::disk('public')->path($path);
            
            return [
                'path' => $path,
                'url' => Storage::url($path),
                'size' => Storage::disk('public')->size($path),
                'last_modified' => Storage::disk('public')->lastModified($path),
                'mime_type' => mime_content_type($fullPath),
                'exists' => true
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get file info', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate thumbnail for image
     */
    public function generateThumbnail(string $identifier, string $storageType = 'local', int $width = 150, int $height = 150): ?string
    {
        try {
            if ($storageType === 'cloudinary' && $this->cloudinaryService->isAvailable()) {
                return $this->cloudinaryService->generateThumbnail($identifier, $width, $height);
            } else {
                // For local storage, return the original image URL
                // In a production environment, you might want to implement local thumbnail generation
                return Storage::url($identifier);
            }
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'identifier' => $identifier,
                'storage_type' => $storageType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate responsive image URLs
     */
    public function generateResponsiveUrls(string $identifier, string $storageType = 'local'): array
    {
        try {
            if ($storageType === 'cloudinary' && $this->cloudinaryService->isAvailable()) {
                return $this->cloudinaryService->generateResponsiveUrls($identifier);
            } else {
                // For local storage, return the same URL for all sizes
                $url = Storage::url($identifier);
                return [
                    'thumbnail' => $url,
                    'small' => $url,
                    'medium' => $url,
                    'large' => $url
                ];
            }
        } catch (\Exception $e) {
            Log::error('Responsive URLs generation failed', [
                'identifier' => $identifier,
                'storage_type' => $storageType,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}