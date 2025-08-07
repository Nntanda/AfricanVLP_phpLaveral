<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteeringCategory extends Model
{
    use HasFactory;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    // Relationships
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_categories');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_categories', 'volunteering_category_id', 'volunteering_oppurtunity_id');
    }

    public function news()
    {
        return $this->belongsToMany(News::class, 'news_categories');
    }
}