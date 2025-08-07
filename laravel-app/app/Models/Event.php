<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'country_id',
        'city_id',
        'address',
        'latitude',
        'longitude',
        'timezone',
        'address_components',
        'place_id',
        'start_date',
        'end_date',
        'status',
        'requesting_volunteers',
        'has_remunerations',
        'region_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'requesting_volunteers' => 'boolean',
        'has_remunerations' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'address_components' => 'array',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function categories()
    {
        return $this->belongsToMany(VolunteeringCategory::class, 'event_categories', 'volunteering_oppurtunity_id');
    }

    public function comments()
    {
        return $this->hasMany(EventComment::class);
    }

    public function volunteeringOpportunities()
    {
        return $this->hasMany(VolunteeringOppurtunity::class);
    }

    // Geographic helper methods
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude
        ];
    }

    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city?->name,
            $this->country?->name
        ]);

        return implode(', ', $addressParts);
    }

    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm)
    {
        // Calculate bounding box for efficient querying
        $earthRadius = 6371; // Earth radius in kilometers
        $latRadian = deg2rad($latitude);
        $degLatKm = 110.574235;
        $degLngKm = 110.572833 * cos($latRadian);

        $deltaLat = $radiusKm / $degLatKm;
        $deltaLng = $radiusKm / $degLngKm;

        return $query->whereBetween('latitude', [$latitude - $deltaLat, $latitude + $deltaLat])
                    ->whereBetween('longitude', [$longitude - $deltaLng, $longitude + $deltaLng])
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }

    public function scopeNearby($query, float $latitude, float $longitude, float $radiusKm = 50)
    {
        return $query->withinRadius($latitude, $longitude, $radiusKm);
    }

    public function scopeHasCoordinates($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}