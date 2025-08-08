<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'title',
        'description',
        'user_id',
        'organization_id',
        'category',
        'status',
        'is_pinned',
        'is_locked',
        'views_count',
        'last_post_at',
        'last_post_user_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'views_count' => 'integer',
        'last_post_at' => 'datetime',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the user who created this thread.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization this thread belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who made the last post.
     */
    public function lastPostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_post_user_id');
    }

    /**
     * Get the posts in this thread.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    /**
     * Get the latest post in this thread.
     */
    public function latestPost(): HasMany
    {
        return $this->hasMany(ForumPost::class)->latest();
    }

    /**
     * Check if the thread is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the thread is pinned.
     */
    public function isPinned(): bool
    {
        return $this->is_pinned === true;
    }

    /**
     * Check if the thread is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked === true;
    }

    /**
     * Increment the views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Update the last post information.
     */
    public function updateLastPost(ForumPost $post): void
    {
        $this->update([
            'last_post_at' => $post->created,
            'last_post_user_id' => $post->user_id,
        ]);
    }

    /**
     * Get the posts count.
     */
    public function getPostsCount(): int
    {
        return $this->posts()->count();
    }

    /**
     * Get the thread's category as a formatted string.
     */
    public function getCategoryText(): string
    {
        return match($this->category) {
            'general' => 'General Discussion',
            'announcements' => 'Announcements',
            'events' => 'Events',
            'volunteering' => 'Volunteering',
            'alumni' => 'Alumni',
            'support' => 'Support',
            default => 'General'
        };
    }

    /**
     * Check if user can post in this thread.
     */
    public function canUserPost(User $user): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        return $user->belongsToOrganization($this->organization_id);
    }
}