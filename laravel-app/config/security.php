<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | application including rate limiting, CSRF protection, and input validation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different types of requests.
    |
    */
    'rate_limiting' => [
        'auth' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        'api' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
        ],
        'upload' => [
            'max_attempts' => 10,
            'decay_minutes' => 5,
        ],
        'email' => [
            'max_attempts' => 5,
            'decay_minutes' => 10,
        ],
        'global' => [
            'max_attempts' => 200,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configure security settings for file uploads.
    |
    */
    'file_upload' => [
        'max_size' => 2048, // KB
        'allowed_image_types' => ['jpeg', 'png', 'jpg', 'gif', 'svg'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        'scan_for_viruses' => env('SCAN_UPLOADS_FOR_VIRUSES', false),
        'quarantine_suspicious' => env('QUARANTINE_SUSPICIOUS_FILES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    |
    | Configure input validation and sanitization settings.
    |
    */
    'input_validation' => [
        'strip_tags_from_input' => true,
        'sanitize_html' => true,
        'max_input_length' => 10000,
        'allowed_html_tags' => '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    |
    | Configure password security requirements.
    |
    */
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'check_compromised' => true,
        'max_age_days' => 90, // Force password change after X days
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session security settings.
    |
    */
    'session' => [
        'timeout_minutes' => 120,
        'regenerate_on_login' => true,
        'invalidate_on_password_change' => true,
        'secure_cookies' => env('SESSION_SECURE_COOKIE', true),
        'same_site_cookies' => 'strict',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Configure Content Security Policy directives.
    |
    */
    'csp' => [
        'default_src' => "'self'",
        'script_src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://maps.googleapis.com",
        'style_src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
        'img_src' => "'self' data: https: blob:",
        'font_src' => "'self' https://fonts.gstatic.com",
        'connect_src' => "'self' https://api.cloudinary.com https://translate.googleapis.com",
        'frame_src' => "'self' https://maps.google.com",
        'object_src' => "'none'",
        'base_uri' => "'self'",
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure additional security headers.
    |
    */
    'headers' => [
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(self), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), speaker=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist/Blacklist
    |--------------------------------------------------------------------------
    |
    | Configure IP-based access control.
    |
    */
    'ip_access' => [
        'admin_whitelist' => env('ADMIN_IP_WHITELIST', ''),
        'blacklist' => env('IP_BLACKLIST', ''),
        'enable_geo_blocking' => env('ENABLE_GEO_BLOCKING', false),
        'blocked_countries' => env('BLOCKED_COUNTRIES', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure security audit logging.
    |
    */
    'audit' => [
        'log_failed_logins' => true,
        'log_admin_actions' => true,
        'log_file_uploads' => true,
        'log_password_changes' => true,
        'log_permission_changes' => true,
        'retention_days' => 90,
    ],

];