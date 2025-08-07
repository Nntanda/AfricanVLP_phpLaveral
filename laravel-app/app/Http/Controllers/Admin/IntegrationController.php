<?php

namespace App\Http\Controllers\Admin;

use App\Services\GoogleTranslateService;
use App\Services\MobileDetectService;
use App\Services\EmailService;
use App\Services\NewsletterService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IntegrationController extends AdminController
{
    protected $googleTranslateService;
    protected $mobileDetectService;
    protected $emailService;
    protected $newsletterService;
    protected $cloudinaryService;

    public function __construct(
        GoogleTranslateService $googleTranslateService,
        MobileDetectService $mobileDetectService,
        EmailService $emailService,
        NewsletterService $newsletterService,
        CloudinaryService $cloudinaryService
    ) {
        parent::__construct();
        $this->googleTranslateService = $googleTranslateService;
        $this->mobileDetectService = $mobileDetectService;
        $this->emailService = $emailService;
        $this->newsletterService = $newsletterService;
        $this->cloudinaryService = $cloudinaryService;
    }

    /**
     * Display integration status dashboard
     */
    public function index()
    {
        $this->shareViewData();

        $integrations = [
            'google_translate' => [
                'name' => 'Google Translate',
                'status' => $this->googleTranslateService->isAvailable(),
                'description' => 'Multi-language content translation',
                'config_key' => 'GOOGLE_TRANSLATE_API_KEY'
            ],
            'sendgrid' => [
                'name' => 'SendGrid',
                'status' => $this->emailService->isAvailable(),
                'description' => 'Email delivery service',
                'config_key' => 'SENDGRID_API_KEY'
            ],
            'cloudinary' => [
                'name' => 'Cloudinary',
                'status' => $this->cloudinaryService->isAvailable(),
                'description' => 'Image and file management',
                'config_key' => 'CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET'
            ],
            'mobile_detect' => [
                'name' => 'Mobile Detection',
                'status' => true, // Always available as it's a PHP library
                'description' => 'Device and browser detection',
                'config_key' => 'Built-in (no configuration required)'
            ]
        ];

        // Get service statistics
        $stats = [
            'email' => $this->emailService->getEmailStats(),
            'newsletter' => $this->newsletterService->getStats(),
            'device' => $this->mobileDetectService->getDeviceStats()
        ];

        return view('admin.integrations.index', compact('integrations', 'stats'));
    }

    /**
     * Test Google Translate integration
     */
    public function testTranslate(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2'
        ]);

        $result = $this->googleTranslateService->translate(
            $request->text,
            $request->target_language,
            $request->source_language
        );

        return response()->json($result);
    }

    /**
     * Get supported languages for translation
     */
    public function getSupportedLanguages(): JsonResponse
    {
        $languages = $this->googleTranslateService->getSupportedLanguages();
        
        return response()->json([
            'success' => true,
            'languages' => $languages
        ]);
    }

    /**
     * Detect language of text
     */
    public function detectLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:1000'
        ]);

        $result = $this->googleTranslateService->detectLanguage($request->text);

        return response()->json($result);
    }

    /**
     * Get device information
     */
    public function getDeviceInfo(): JsonResponse
    {
        $deviceInfo = $this->mobileDetectService->getDeviceInfo();
        
        return response()->json([
            'success' => true,
            'device_info' => $deviceInfo
        ]);
    }

    /**
     * Test email sending
     */
    public function testEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000'
        ]);

        // Create a test user object
        $testUser = (object) [
            'email' => $request->email,
            'first_name' => 'Test',
            'last_name' => 'User'
        ];

        $success = $this->emailService->sendNotificationEmail(
            $testUser,
            $request->subject,
            $request->message,
            ['test_email' => true]
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Test email sent successfully' : 'Failed to send test email'
        ]);
    }

    /**
     * Newsletter management
     */
    public function newsletter()
    {
        $this->shareViewData();

        $stats = $this->newsletterService->getStats();
        $subscribers = $this->newsletterService->getSubscribers(1, 10);

        return view('admin.integrations.newsletter', compact('stats', 'subscribers'));
    }

    /**
     * Send newsletter
     */
    public function sendNewsletter(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'filters' => 'nullable|array'
        ]);

        $result = $this->newsletterService->sendNewsletter(
            $request->subject,
            $request->content,
            $request->filters ?? []
        );

        return response()->json($result);
    }

    /**
     * Get newsletter subscribers
     */
    public function getNewsletterSubscribers(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $filters = $request->only(['email', 'date_from', 'date_to']);

        $result = $this->newsletterService->getSubscribers($page, $perPage, $filters);

        return response()->json($result);
    }

    /**
     * Clear translation cache
     */
    public function clearTranslationCache(): JsonResponse
    {
        $success = $this->googleTranslateService->clearCache();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Translation cache cleared successfully' : 'Failed to clear translation cache'
        ]);
    }

    /**
     * Get integration health status
     */
    public function healthCheck(): JsonResponse
    {
        $health = [
            'google_translate' => [
                'status' => $this->googleTranslateService->isAvailable(),
                'last_check' => now()->toISOString()
            ],
            'sendgrid' => [
                'status' => $this->emailService->isAvailable(),
                'last_check' => now()->toISOString()
            ],
            'cloudinary' => [
                'status' => $this->cloudinaryService->isAvailable(),
                'last_check' => now()->toISOString()
            ],
            'mobile_detect' => [
                'status' => true,
                'last_check' => now()->toISOString()
            ]
        ];

        $overallHealth = collect($health)->every(fn($service) => $service['status']);

        return response()->json([
            'success' => true,
            'overall_health' => $overallHealth,
            'services' => $health,
            'timestamp' => now()->toISOString()
        ]);
    }
}