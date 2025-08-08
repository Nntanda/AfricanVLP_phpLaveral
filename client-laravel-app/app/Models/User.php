<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'address',
        'city_id',
        'country_id',
        'profile_image',
        'date_of_birth',
        'gender',
        'status',
        'is_admin',
        'email_verified_at',
        'email_verification_token',
        'password_reset_token',
        'last_login',
        'login_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'last_login' => 'datetime',
        'login_count' => 'integer',
        'is_admin' => 'boolean',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the city that the user belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country that the user belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the organizations that the user belongs to.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
                    ->withPivot('role', 'status', 'joined_date')
                    ->withTimestamps();
    }

    /**
     * Get the organizations where the user is an alumni.
     */
    public function alumniOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_alumni')
                    ->withPivot('status', 'graduation_year')
                    ->withTimestamps();
    }

    /**
     * Get the user's volunteering interests.
     */
    public function volunteeringInterests(): HasMany
    {
        return $this->hasMany(VolunteeringInterest::class);
    }

    /**
     * Get the user's volunteering history.
     */
    public function volunteeringHistory(): HasMany
    {
        return $this->hasMany(VolunteeringHistory::class);
    }

    /**
     * Get the user's conversations.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot('joined_at', 'left_at')
                    ->withTimestamps();
    }

    /**
     * Get the user's forum posts.
     */
    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    /**
     * Get the user's notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user belongs to a specific organization.
     */
    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()
                    ->where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->exists();
    }

    /**
     * Get user's active volunteering opportunities.
     */
    public function getActiveVolunteeringOpportunities()
    {
        return $this->volunteeringHistory()
                    ->where('status', 'active')
                    ->with('volunteeringOpportunity')
                    ->get();
    }
}