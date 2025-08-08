<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ForumController extends Controller
{
    /**
     * Display forum threads for an organization.
     */
    public function index(Organization $organization, Request $request)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization forum.');
        }

        $query = $organization->forumThreads()->with(['user', 'lastPostUser']);

        // Category filter
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort: pinned first, then by last post date
        $threads = $query->orderBy('is_pinned', 'desc')
                        ->orderBy('last_post_at', 'desc')
                        ->paginate(20)
                        ->withQueryString();

        $categories = [
            'general' => 'General Discussion',
            'announcements' => 'Announcements',
            'events' => 'Events',
            'volunteering' => 'Volunteering',
            'alumni' => 'Alumni',
            'support' => 'Support'
        ];

        return view('client.forum.index', compact('organization', 'threads', 'categories'));
    }

    /**
     * Show the form for creating a new thread.
     */
    public function createThread(Organization $organization)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization forum.');
        }

        $categories = [
            'general' => 'General Discussion',
            'announcements' => 'Announcements',
            'events' => 'Events',
            'volunteering' => 'Volunteering',
            'alumni' => 'Alumni',
            'support' => 'Support'
        ];

        return view('client.forum.create-thread', compact('organization', 'categories'));
    }

    /**
     * Store a newly created thread.
     */
    public function storeThread(Request $request, Organization $organization)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization forum.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:general,announcements,events,volunteering,alumni,support',
            'content' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $thread = ForumThread::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => Auth::id(),
            'organization_id' => $organization->id,
            'category' => $request->category,
            'status' => 'active',
            'last_post_at' => now(),
            'last_post_user_id' => Auth::id(),
            'created' => now(),
            'modified' => now(),
        ]);

        // Create the first post
        ForumPost::create([
            'forum_thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'status' => 'active',
            'created' => now(),
            'modified' => now(),
        ]);

        return redirect()->route('organizations.forum.threads.show', [$organization, $thread])
                        ->with('success', 'Thread created successfully!');
    }

    /**
     * Display the specified thread.
     */
    public function showThread(Organization $organization, ForumThread $thread)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization forum.');
        }

        // Check if thread belongs to this organization
        if ($thread->organization_id !== $organization->id) {
            abort(404);
        }

        // Increment views
        $thread->incrementViews();

        // Load thread with user
        $thread->load('user');

        // Get posts with pagination
        $posts = $thread->posts()
                       ->with('user')
                       ->where('status', 'active')
                       ->orderBy('created', 'asc')
                       ->paginate(20);

        return view('client.forum.thread-show', compact('organization', 'thread', 'posts'));
    }

    /**
     * Store a new post in a thread.
     */
    public function storePost(Request $request, Organization $organization, ForumThread $thread)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization forum.');
        }

        // Check if thread belongs to this organization
        if ($thread->organization_id !== $organization->id) {
            abort(404);
        }

        // Check if thread is locked
        if ($thread->isLocked()) {
            return back()->with('error', 'This thread is locked and cannot accept new posts.');
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $post = ForumPost::create([
            'forum_thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'status' => 'active',
            'created' => now(),
            'modified' => now(),
        ]);

        // Update thread's last post info
        $thread->updateLastPost($post);

        return back()->with('success', 'Post added successfully!');
    }

    /**
     * Update the specified post.
     */
    public function updatePost(Request $request, Organization $organization, ForumPost $post)
    {
        // Check if user can edit this post
        if (!$post->canUserEdit(Auth::user())) {
            abort(403, 'You cannot edit this post.');
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $post->update([
            'content' => $request->content,
            'modified' => now(),
        ]);

        $post->markAsEdited(Auth::id());

        return back()->with('success', 'Post updated successfully!');
    }

    /**
     * Delete the specified post.
     */
    public function deletePost(Organization $organization, ForumPost $post)
    {
        // Check if user can delete this post
        if (!$post->canUserDelete(Auth::user())) {
            abort(403, 'You cannot delete this post.');
        }

        $post->update([
            'status' => 'deleted',
            'modified' => now(),
        ]);

        return back()->with('success', 'Post deleted successfully!');
    }
}