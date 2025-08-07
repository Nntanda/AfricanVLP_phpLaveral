<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'date_of_birth',
        'gender',
        'about',
        'profile_picture',
        'country_id',
        'city_id',
        'role',
        'status',
        'is_email_verified',
        'email_verification_token',
        'password_reset_token',
        'password_reset_expires',
        'registration_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'email_verification_token',
        'password_reset_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'is_email_verified' => 'boolean',
        'password_reset_expires' => 'datetime',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    // Relationships
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot('role', 'status')
                    ->withTimestamps();
    }

    public function alumniOrganizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_alumni')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function volunteeringInterests()
    {
        return $this->hasMany(VolunteeringInterest::class);
    }

    public function volunteeringHistories()
    {
        return $this->hasMany(VolunteeringHistory::class);
    }

    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }
}