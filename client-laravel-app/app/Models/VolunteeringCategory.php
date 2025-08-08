<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VolunteeringCategory extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the volunteering interests for this category.
     */
    public function volunteeringInterests(): HasMany
    {
        return $this->hasMany(VolunteeringInterest::class);
    }

    /**
     * Get the events associated with this category.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_categories');
    }

    /**
     * Get the news associated with this category.
     */
    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'news_categories');
    }

    /**
     * Get the organizations associated with this category.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_categories');
    }

    /**
     * Get the volunteering opportunities associated with this category.
     */
    public function volunteeringOpportunities(): BelongsToMany
    {
        return $this->belongsToMany(VolunteeringOpportunity::class, 'event_categories', 'volunteering_category_id', 'volunteering_oppurtunity_id');
    }

    /**
     * Check if the category is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the category icon URL.
     */
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? asset('storage/icons/' . $this->icon) : null;
    }

    /**
     * Get the category color with default.
     */
    public function getCategoryColor(): string
    {
        return $this->color ?: '#3B82F6'; // Default blue color
    }

    /**
     * Get active volunteering opportunities count.
     */
    public function getActiveOpportunitiesCount(): int
    {
        return $this->volunteeringOpportunities()
                    ->where('status', 'active')
                    ->where('end_date', '>', now())
                    ->count();
    }

    /**
     * Get users interested in this category count.
     */
    public function getInterestedUsersCount(): int
    {
        return $this->volunteeringInterests()
                    ->where('status', 'active')
                    ->distinct('user_id')
                    ->count();
    }

    /**
     * Scope to get active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get categories ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}