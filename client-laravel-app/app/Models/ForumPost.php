<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumPost extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'forum_thread_id',
        'user_id',
        'content',
        'status',
        'is_edited',
        'edited_at',
        'edited_by',
        'likes_count',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'likes_count' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the thread this post belongs to.
     */
    public function forumThread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class);
    }

    /**
     * Get the user who created this post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who edited this post.
     */
    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Check if the post is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the post has been edited.
     */
    public function isEdited(): bool
    {
        return $this->is_edited === true;
    }

    /**
     * Mark the post as edited.
     */
    public function markAsEdited(int $editedBy): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
            'edited_by' => $editedBy,
        ]);
    }

    /**
     * Increment the likes count.
     */
    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    /**
     * Decrement the likes count.
     */
    public function decrementLikes(): void
    {
        $this->decrement('likes_count');
    }

    /**
     * Get the post content with basic HTML formatting.
     */
    public function getFormattedContentAttribute(): string
    {
        // Basic formatting: convert line breaks to <br> tags
        return nl2br(e($this->content));
    }

    /**
     * Get the post excerpt (first 150 characters).
     */
    public function getExcerptAttribute(): string
    {
        return strlen($this->content) > 150 
            ? substr($this->content, 0, 150) . '...' 
            : $this->content;
    }

    /**
     * Check if user can edit this post.
     */
    public function canUserEdit(User $user): bool
    {
        // User can edit their own posts or if they're admin
        return $this->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Check if user can delete this post.
     */
    public function canUserDelete(User $user): bool
    {
        // User can delete their own posts or if they're admin
        return $this->user_id === $user->id || $user->isAdmin();
    }
}