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
}