<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;
use Cloudinary\Transformation\Resize;
use Cloudinary\Transformation\Quality;
use Cloudinary\Transformation\Format;
use Cloudinary\Transformation\Delivery;

class CloudinaryService
{
    protected $cloudinary;
    protected $isConfigured = false;

    public function __construct()
    {
        // Check if Cloudinary is configured
        $this->isConfigured = !empty(config('services.cloudinary.cloud_name')) &&
                             !empty(config('services.cloudinary.api_key')) &&
                             !empty(config('services.cloudinary.api_secret'));

        if ($this->isConfigured) {
            // Initialize Cloudinary
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => config('services.cloudinary.cloud_name'),
                    'api_key' => config('services.cloudinary.api_key'),
                    'api_secret' => config('services.cloudinary.api_secret'),
                ]
            ]);
        }
    }

    /**
     * Check if Cloudinary is configured and available
     */
    public function isAvailable(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Upload file to Cloudinary
     */
    public function uploadFile(UploadedFile $file, array $options = []): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Cloudinary is not configured'
            ];
        }

        try {
            // Default options
            $defaultOptions = [
                'folder' => 'au-vlp',
                'resource_type' => 'auto',
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ];

            $uploadOptions = array_merge($defaultOptions, $options);

            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $uploadOptions
            );

            return [
                'success' => true,
                'public_id' => $result['public_id'],
                'url' => $result['secure_url'],
                'width' => $result['width'] ?? null,
                'height' => $result['height'] ?? null,
                'format' => $result['format'],
                'resource_type' => $result['resource_type'],
                'bytes' => $result['bytes'],
                'created_at' => $result['created_at']
            ];

        } catch (\Exception $e) {
            Log::error('Cloudinary upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            return [
                'success' => false,
                'error' => 'Cloudinary upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload image with transformations
     */
    public function uploadImage(UploadedFile $file, array $transformations = [], array $options = []): array
    {
        $options['transformation'] = $transformations;
        return $this->uploadFile($file, $options);
    }

    /**
     * Delete file from Cloudinary
     */
    public function deleteFile(string $publicId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            Log::error('Cloudinary deletion failed', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate URL with transformations
     */
    public function generateUrl(string $publicId, array $transformations = []): string
    {
        if (!$this->isAvailable()) {
            return '';
        }

        try {
            $imageTag = $this->cloudinary->image($publicId);
            
            // Apply transformations if provided
            if (!empty($transformations)) {
                foreach ($transformations as $key => $value) {
                    switch ($key) {
                        case 'width':
                            $imageTag = $imageTag->resize(Resize::scale()->width($value));
                            break;
                        case 'height':
                            $imageTag = $imageTag->resize(Resize::scale()->height($value));
                            break;
                        case 'crop':
                            if ($value === 'fill' && isset($transformations['width']) && isset($transformations['height'])) {
                                $imageTag = $imageTag->resize(Resize::fill($transformations['width'], $transformations['height']));
                            } elseif ($value === 'limit' && isset($transformations['width']) && isset($transformations['height'])) {
                                $imageTag = $imageTag->resize(Resize::limitFit($transformations['width'], $transformations['height']));
                            }
                            break;
                        case 'quality':
                            if ($value === 'auto') {
                                $imageTag = $imageTag->quality(Quality::auto());
                            } else {
                                $imageTag = $imageTag->quality(Quality::level($value));
                            }
                            break;
                        case 'fetch_format':
                            if ($value === 'auto') {
                                $imageTag = $imageTag->format(Format::auto());
                            }
                            break;
                    }
                }
            }
            
            return $imageTag->toUrl();
        } catch (\Exception $e) {
            Log::error('Cloudinary URL generation failed', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Generate thumbnail URL
     */
    public function generateThumbnail(string $publicId, int $width = 150, int $height = 150): string
    {
        return $this->generateUrl($publicId, [
            'width' => $width,
            'height' => $height,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ]);
    }

    /**
     * Generate responsive image URLs
     */
    public function generateResponsiveUrls(string $publicId): array
    {
        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200]
        ];

        $urls = [];
        foreach ($sizes as $size => $dimensions) {
            $urls[$size] = $this->generateUrl($publicId, [
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'crop' => 'limit',
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]);
        }

        return $urls;
    }
}