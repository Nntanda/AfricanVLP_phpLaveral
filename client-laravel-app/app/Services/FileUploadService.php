<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileUploadService
{
    protected $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected $allowedDocumentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    protected $maxFileSize = 10 * 1024 * 1024; // 10MB
    protected $maxImageSize = 5 * 1024 * 1024; // 5MB

    /**
     * Upload a file to storage.
     */
    public function uploadFile(UploadedFile $file, string $directory = 'uploads', array $options = []): array
    {
        // Validate file
        $this->validateFile($file, $options);

        // Generate unique filename
        $filename = $this->generateFilename($file);
        $path = $directory . '/' . $filename;

        // Determine if it's an image
        $isImage = $this->isImage($file);

        if ($isImage && isset($options['resize'])) {
            // Process and resize image
            $processedFile = $this->processImage($file, $options['resize']);
            $storedPath = Storage::disk('public')->put($path, $processedFile);
        } else {
            // Store file as-is
            $storedPath = $file->storeAs($directory, $filename, 'public');
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $storedPath,
            'url' => Storage::disk('public')->url($storedPath),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'is_image' => $isImage,
        ];
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultipleFiles(array $files, string $directory = 'uploads', array $options = []): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $directory, $options);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Upload profile image with specific processing.
     */
    public function uploadProfileImage(UploadedFile $file, string $userId): array
    {
        $options = [
            'resize' => [
                'width' => 300,
                'height' => 300,
                'crop' => true
            ]
        ];

        return $this->uploadFile($file, "profiles/{$userId}", $options);
    }

    /**
     * Upload organization logo.
     */
    public function uploadOrganizationLogo(UploadedFile $file, string $organizationId): array
    {
        $options = [
            'resize' => [
                'width' => 200,
                'height' => 200,
                'crop' => true
            ]
        ];

        return $this->uploadFile($file, "organizations/{$organizationId}", $options);
    }

    /**
     * Upload news/event image.
     */
    public function uploadContentImage(UploadedFile $file, string $type = 'content'): array
    {
        $options = [
            'resize' => [
                'width' => 800,
                'height' => 600,
                'crop' => false
            ]
        ];

        return $this->uploadFile($file, "{$type}/images", $options);
    }

    /**
     * Upload resource file.
     */
    public function uploadResourceFile(UploadedFile $file): array
    {
        return $this->uploadFile($file, 'resources');
    }

    /**
     * Delete a file from storage.
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Validate uploaded file.
     */
    protected function validateFile(UploadedFile $file, array $options = []): void
    {
        // Check file size
        $maxSize = $this->isImage($file) ? $this->maxImageSize : $this->maxFileSize;
        if (isset($options['max_size'])) {
            $maxSize = $options['max_size'];
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size.');
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedTypes = array_merge($this->allowedImageTypes, $this->allowedDocumentTypes);
        
        if (isset($options['allowed_types'])) {
            $allowedTypes = $options['allowed_types'];
        }

        if (!in_array($extension, $allowedTypes)) {
            throw new \InvalidArgumentException('File type not allowed.');
        }

        // Additional validation for images
        if ($this->isImage($file)) {
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                throw new \InvalidArgumentException('Invalid image file.');
            }
        }
    }

    /**
     * Check if file is an image.
     */
    protected function isImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, $this->allowedImageTypes);
    }

    /**
     * Generate unique filename.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Process and resize image.
     */
    protected function processImage(UploadedFile $file, array $resizeOptions)
    {
        $image = Image::make($file);

        if (isset($resizeOptions['width']) && isset($resizeOptions['height'])) {
            if ($resizeOptions['crop'] ?? false) {
                // Crop to exact dimensions
                $image->fit($resizeOptions['width'], $resizeOptions['height']);
            } else {
                // Resize maintaining aspect ratio
                $image->resize($resizeOptions['width'], $resizeOptions['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
        }

        // Optimize image quality
        $quality = $resizeOptions['quality'] ?? 85;
        
        return $image->encode($file->getClientOriginalExtension(), $quality)->getEncoded();
    }

    /**
     * Get file info from path.
     */
    public function getFileInfo(string $path): ?array
    {
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        
        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'size' => Storage::disk('public')->size($path),
            'last_modified' => Storage::disk('public')->lastModified($path),
            'mime_type' => Storage::disk('public')->mimeType($path),
            'exists' => true,
        ];
    }

    /**
     * Generate thumbnail for image.
     */
    public function generateThumbnail(string $imagePath, int $width = 150, int $height = 150): ?string
    {
        if (!Storage::disk('public')->exists($imagePath)) {
            return null;
        }

        $pathInfo = pathinfo($imagePath);
        $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            $image = Image::make($fullPath);
            $image->fit($width, $height);
            
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
            $image->save($thumbnailFullPath, 80);
            
            return $thumbnailPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get human readable file size.
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}