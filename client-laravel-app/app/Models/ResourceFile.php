<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class ResourceFile extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'original_name',
        'filename',
        'path',
        'size',
        'mime_type',
        'extension',
        'is_image',
        'uploaded_by',
        'fileable_type',
        'fileable_id',
        'category',
        'description',
        'is_public',
        'download_count',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_image' => 'boolean',
        'is_public' => 'boolean',
        'download_count' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user who uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the owning fileable model.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Get the full file path.
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('public')->path($this->path);
    }

    /**
     * Get human readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($this->size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Check if file exists on disk.
     */
    public function exists(): bool
    {
        return Storage::disk('public')->exists($this->path);
    }

    /**
     * Get file icon based on extension.
     */
    public function getIconAttribute(): string
    {
        if ($this->is_image) {
            return 'image';
        }

        $icons = [
            'pdf' => 'file-pdf',
            'doc' => 'file-word',
            'docx' => 'file-word',
            'xls' => 'file-excel',
            'xlsx' => 'file-excel',
            'ppt' => 'file-powerpoint',
            'pptx' => 'file-powerpoint',
            'txt' => 'file-text',
            'zip' => 'file-archive',
            'rar' => 'file-archive',
        ];

        return $icons[$this->extension] ?? 'file';
    }

    /**
     * Get file color based on type.
     */
    public function getColorAttribute(): string
    {
        if ($this->is_image) {
            return 'green';
        }

        $colors = [
            'pdf' => 'red',
            'doc' => 'blue',
            'docx' => 'blue',
            'xls' => 'green',
            'xlsx' => 'green',
            'ppt' => 'orange',
            'pptx' => 'orange',
            'txt' => 'gray',
            'zip' => 'purple',
            'rar' => 'purple',
        ];

        return $colors[$this->extension] ?? 'gray';
    }

    /**
     * Increment download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Delete the file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            Storage::disk('public')->delete($this->path);
            
            // Delete thumbnail if it exists
            $pathInfo = pathinfo($this->path);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }

        return $this->delete();
    }

    /**
     * Get thumbnail URL for images.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->is_image) {
            return null;
        }

        $pathInfo = pathinfo($this->path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->url($thumbnailPath);
        }

        return $this->url;
    }

    /**
     * Scope for public files.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for private files.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope for images.
     */
    public function scopeImages($query)
    {
        return $query->where('is_image', true);
    }

    /**
     * Scope for documents.
     */
    public function scopeDocuments($query)
    {
        return $query->where('is_image', false);
    }

    /**
     * Scope by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by file type.
     */
    public function scopeByExtension($query, string $extension)
    {
        return $query->where('extension', $extension);
    }

    /**
     * Get files uploaded by a specific user.
     */
    public function scopeUploadedBy($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }
}