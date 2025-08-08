<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteeringRole extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'description',
        'responsibilities',
        'requirements',
        'time_commitment',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the volunteering opportunities for this role.
     */
    public function volunteeringOpportunities(): HasMany
    {
        return $this->hasMany(VolunteeringOpportunity::class);
    }

    /**
     * Check if the role is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the responsibilities as an array.
     */
    public function getResponsibilitiesArray(): array
    {
        if (empty($this->responsibilities)) {
            return [];
        }

        return array_filter(array_map('trim', explode("\n", $this->responsibilities)));
    }

    /**
     * Get the requirements as an array.
     */
    public function getRequirementsArray(): array
    {
        if (empty($this->requirements)) {
            return [];
        }

        return array_filter(array_map('trim', explode("\n", $this->requirements)));
    }

    /**
     * Get active opportunities count for this role.
     */
    public function getActiveOpportunitiesCount(): int
    {
        return $this->volunteeringOpportunities()
                    ->where('status', 'active')
                    ->where('end_date', '>', now())
                    ->count();
    }

    /**
     * Get the time commitment as a formatted string.
     */
    public function getFormattedTimeCommitment(): string
    {
        if (empty($this->time_commitment)) {
            return 'Not specified';
        }

        return $this->time_commitment;
    }

    /**
     * Scope to get active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get roles ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}