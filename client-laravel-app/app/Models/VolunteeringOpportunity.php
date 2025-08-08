<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VolunteeringOpportunity extends Model
{
    protected $table = 'volunteering_oppurtunities'; // Note: keeping original table name with typo

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'event_id',
        'volunteering_role_id',
        'volunteering_duration_id',
        'description',
        'requirements',
        'benefits',
        'location',
        'start_date',
        'end_date',
        'max_volunteers',
        'current_volunteers',
        'status',
        'contact_person',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'max_volunteers' => 'integer',
        'current_volunteers' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the event that owns the volunteering opportunity.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the volunteering role.
     */
    public function volunteeringRole(): BelongsTo
    {
        return $this->belongsTo(VolunteeringRole::class);
    }

    /**
     * Get the volunteering duration.
     */
    public function volunteeringDuration(): BelongsTo
    {
        return $this->belongsTo(VolunteeringDuration::class);
    }

    /**
     * Get the categories for this volunteering opportunity.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VolunteeringCategory::class, 'event_categories', 'volunteering_oppurtunity_id', 'volunteering_category_id');
    }

    /**
     * Get the users who have applied for this opportunity.
     */
    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'volunteering_histories', 'volunteering_oppurtunity_id', 'user_id')
                    ->withPivot('status', 'applied_date', 'notes')
                    ->withTimestamps();
    }

    /**
     * Check if the opportunity is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->end_date > now() && 
               $this->current_volunteers < $this->max_volunteers;
    }

    /**
     * Check if the opportunity has available spots.
     */
    public function hasAvailableSpots(): bool
    {
        return $this->current_volunteers < $this->max_volunteers;
    }

    /**
     * Get the remaining spots for this opportunity.
     */
    public function getRemainingSpots(): int
    {
        return max(0, $this->max_volunteers - $this->current_volunteers);
    }

    /**
     * Get the volunteering histories for this opportunity.
     */
    public function volunteeringHistories()
    {
        return $this->hasMany(VolunteeringHistory::class, 'volunteering_oppurtunity_id');
    }

    /**
     * Get the organization through the event.
     */
    public function organization()
    {
        return $this->hasOneThrough(Organization::class, Event::class, 'id', 'id', 'event_id', 'organization_id');
    }

    /**
     * Check if the opportunity is available for applications.
     */
    public function isAvailableForApplications(): bool
    {
        return $this->status === 'active' && 
               $this->end_date > now() &&
               $this->hasAvailableSpots();
    }

    /**
     * Get the number of accepted volunteers.
     */
    public function getAcceptedVolunteersCount(): int
    {
        return $this->volunteeringHistories()
            ->whereIn('status', ['accepted', 'active', 'completed'])
            ->count();
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        if (!$this->max_volunteers) {
            return 0;
        }
        
        return min(100, ($this->current_volunteers / $this->max_volunteers) * 100);
    }

    /**
     * Check if the opportunity is full.
     */
    public function isFull(): bool
    {
        return !$this->hasAvailableSpots();
    }

    /**
     * Get the opportunity status for display.
     */
    public function getDisplayStatus(): string
    {
        if ($this->end_date < now()) {
            return 'Expired';
        }
        
        if ($this->start_date > now()) {
            return 'Upcoming';
        }
        
        if ($this->isFull()) {
            return 'Full';
        }
        
        if ($this->status === 'active') {
            return 'Open';
        }
        
        return ucfirst($this->status);
    }

    /**
     * Get the opportunity urgency level.
     */
    public function getUrgencyLevel(): string
    {
        $daysUntilEnd = now()->diffInDays($this->end_date, false);
        
        if ($daysUntilEnd < 0) {
            return 'expired';
        } elseif ($daysUntilEnd <= 3) {
            return 'urgent';
        } elseif ($daysUntilEnd <= 7) {
            return 'soon';
        } else {
            return 'normal';
        }
    }

    /**
     * Scope for active opportunities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '>', now());
    }

    /**
     * Scope for available opportunities (with spots).
     */
    public function scopeAvailable($query)
    {
        return $query->active()
                    ->whereRaw('current_volunteers < max_volunteers');
    }
}