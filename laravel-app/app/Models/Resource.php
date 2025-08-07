<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'organization_id',
        'title',
        'resource_type_id',
        'file_type',
        'file_link',
        'status',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function resourceType()
    {
        return $this->belongsTo(ResourceType::class);
    }

    public function categories()
    {
        return $this->belongsToMany(CategoryOfResource::class, 'resource_categories', 'resource_id', 'category_of_resource_id');
    }

    public function files()
    {
        return $this->hasMany(ResourceFile::class);
    }

    // Helper methods
    public function getMainFileAttribute()
    {
        return $this->files()->first() ?: null;
    }

    public function hasFiles()
    {
        return $this->files()->count() > 0;
    }

    public function getFileCountAttribute()
    {
        return $this->files()->count();
    }
}