<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\FileUploadService;
use App\Services\CloudinaryService;

class ResourceFile extends Model
{
    use HasFactory;

    protected $table = 'resource_files';
    
    // Use CakePHP timestamp column names
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'resource_id',
        'filename',
        'original_name',
        'file_path',
        'cloudinary_public_id',
        'storage_type',
        'mime_type',
        'file_extension',
        'file_size',
        'download_count',
        'metadata',
        'is_primary',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'created' => 'datetime',
        'modified' => 'datetime'
    ];

    /**
     * Get the resource that owns this file
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get the file URL
     */
    public function getUrlAttribute(): string
    {
        if ($this->storage_type === 'cloudinary' && $this->cloudinary_public_id) {
            $cloudinaryService = app(CloudinaryService::class);
            return $cloudinaryService->generateUrl($this->cloudinary_public_id);
        } elseif ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        
        return '';
    }

    /**
     * Get thumbnail URL for images
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (!$this->isImage()) {
            return $this->getFileTypeIconUrl();
        }

        $fileUploadService = app(FileUploadService::class);
        
        if ($this->storage_type === 'cloudinary' && $this->cloudinary_public_id) {
            return $fileUploadService->generateThumbnail($this->cloudinary_public_id, 'cloudinary');
        } elseif ($this->file_path) {
            return $fileUploadService->generateThumbnail($this->file_path, 'local');
        }
        
        return $this->getFileTypeIconUrl();
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        return in_array(strtolower($this->file_extension), $imageTypes);
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        $documentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        return in_array(strtolower($this->file_extension), $documentTypes);
    }

    /**
     * Get file type icon URL
     */
    public function getFileTypeIconUrl(): string
    {
        $iconMap = [
            'pdf' => 'fas fa-file-pdf text-red-500',
            'doc' => 'fas fa-file-word text-blue-500',
            'docx' => 'fas fa-file-word text-blue-500',
            'xls' => 'fas fa-file-excel text-green-500',
            'xlsx' => 'fas fa-file-excel text-green-500',
            'ppt' => 'fas fa-file-powerpoint text-orange-500',
            'pptx' => 'fas fa-file-powerpoint text-orange-500',
            'txt' => 'fas fa-file-alt text-gray-500',
            'zip' => 'fas fa-file-archive text-yellow-500',
            'rar' => 'fas fa-file-archive text-yellow-500',
        ];

        return $iconMap[strtolower($this->file_extension)] ?? 'fas fa-file text-gray-500';
    }

    /**
     * Get human readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Scope for active files
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary files
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for images
     */
    public function scopeImages($query)
    {
        return $query->whereIn('file_extension', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Scope for documents
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('file_extension', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created');
    }
}