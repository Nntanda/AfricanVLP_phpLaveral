<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'action_url',
        'read_at',
        'data',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // CakePHP timestamp compatibility
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $dates = ['created', 'modified', 'read_at'];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if notification is read.
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Get notification icon based on type.
     */
    public function getIconAttribute()
    {
        $icons = [
            'message' => 'mail',
            'forum' => 'chat-bubble-left-right',
            'volunteer' => 'hand-raised',
            'event' => 'calendar',
            'organization' => 'building-office',
            'system' => 'cog-6-tooth',
            'welcome' => 'hand-raised',
            'reminder' => 'bell',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get notification color based on type.
     */
    public function getColorAttribute()
    {
        $colors = [
            'message' => 'blue',
            'forum' => 'green',
            'volunteer' => 'purple',
            'event' => 'yellow',
            'organization' => 'indigo',
            'system' => 'gray',
            'welcome' => 'green',
            'reminder' => 'orange',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    /**
     * Create a new notification for a user.
     */
    public static function createForUser($userId, $title, $message, $type = 'system', $actionUrl = null, $data = [])
    {
        return static::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    /**
     * Create welcome notification for new user.
     */
    public static function createWelcomeNotification($user)
    {
        return static::createForUser(
            $user->id,
            'Welcome to African VLP!',
            'Welcome to the African Volunteer Leadership Platform. Start by exploring organizations and volunteer opportunities.',
            'welcome',
            route('dashboard')
        );
    }

    /**
     * Create notification for new message.
     */
    public static function createMessageNotification($userId, $senderName, $conversationId)
    {
        return static::createForUser(
            $userId,
            'New Message',
            "You have a new message from {$senderName}.",
            'message',
            route('messages.show', $conversationId)
        );
    }

    /**
     * Create notification for forum reply.
     */
    public static function createForumReplyNotification($userId, $threadTitle, $threadId)
    {
        return static::createForUser(
            $userId,
            'New Forum Reply',
            "Someone replied to your thread: {$threadTitle}",
            'forum',
            route('forum.show', $threadId)
        );
    }

    /**
     * Create notification for volunteer opportunity.
     */
    public static function createVolunteerNotification($userId, $title, $opportunityId)
    {
        return static::createForUser(
            $userId,
            'Volunteer Opportunity Update',
            $title,
            'volunteer',
            route('volunteer.opportunities.show', $opportunityId)
        );
    }

    /**
     * Create notification for event.
     */
    public static function createEventNotification($userId, $title, $eventId)
    {
        return static::createForUser(
            $userId,
            'Event Notification',
            $title,
            'event',
            route('events.show', $eventId)
        );
    }

    /**
     * Create notification for organization update.
     */
    public static function createOrganizationNotification($userId, $title, $organizationId)
    {
        return static::createForUser(
            $userId,
            'Organization Update',
            $title,
            'organization',
            route('organizations.show', $organizationId)
        );
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}