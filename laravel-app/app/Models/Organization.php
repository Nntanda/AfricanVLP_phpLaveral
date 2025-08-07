<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'type',
        'name',
        'about',
        'country_id',
        'city_id',
        'logo',
        'latitude',
        'longitude',
        'timezone',
        'address_components',
        'place_id',
        'institution_type_id',
        'government_affliliation',
        'category_id',
        'date_of_establishment',
        'phone_number',
        'website',
        'facebbok_url',
        'instagram_url',
        'twitter_url',
        'user_id',
        'status',
    ];

    protected $casts = [
        'date_of_establishment' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'address_components' => 'array',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users')
                    ->withPivot('role', 'status')
                    ->withTimestamps();
    }

    public function alumni()
    {
        return $this->belongsToMany(User::class, 'organization_alumni')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function offices()
    {
        return $this->hasMany(OrganizationOffice::class);
    }

    public function categories()
    {
        return $this->belongsToMany(VolunteeringCategory::class, 'organization_categories');
    }

    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function institutionType()
    {
        return $this->belongsTo(InstitutionType::class);
    }

    public function categoryOfOrganization()
    {
        return $this->belongsTo(CategoryOfOrganization::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
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