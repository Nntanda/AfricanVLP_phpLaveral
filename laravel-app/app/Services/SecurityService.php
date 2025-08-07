<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SecurityService
{
    /**
     * Log security events for audit purposes.
     */
    public function logSecurityEvent(string $event, array $data = [], ?string $userId = null): void
    {
        $logData = [
            'event' => $event,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ];

        Log::channel('security')->info('Security Event', $logData);
    }

    /**
     * Check if an IP address is suspicious based on failed login attempts.
     */
    public function isSuspiciousIP(string $ip): bool
    {
        $key = "suspicious_ip:{$ip}";
        $attempts = Cache::get($key, 0);
        
        return $attempts >= config('security.rate_limiting.auth.max_attempts', 5);
    }

    /**
     * Mark an IP as suspicious.
     */
    public function markIPAsSuspicious(string $ip): void
    {
        $key = "suspicious_ip:{$ip}";
        $attempts = Cache::get($key, 0) + 1;
        $ttl = config('security.rate_limiting.auth.decay_minutes', 15) * 60;
        
        Cache::put($key, $attempts, $ttl);
        
        if ($attempts >= 3) {
            $this->logSecurityEvent('suspicious_ip_detected', [
                'ip' => $ip,
                'attempts' => $attempts,
            ]);
        }
    }

    /**
     * Clear suspicious IP status.
     */
    public function clearSuspiciousIP(string $ip): void
    {
        Cache::forget("suspicious_ip:{$ip}");
    }

    /**
     * Validate file upload for security threats.
     */
    public function validateFileUpload($file): array
    {
        $errors = [];
        
        // Check file size
        $maxSize = config('security.file_upload.max_size', 2048) * 1024; // Convert to bytes
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size.';
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedImages = config('security.file_upload.allowed_image_types', []);
        $allowedDocs = config('security.file_upload.allowed_document_types', []);
        $allowedTypes = array_merge($allowedImages, $allowedDocs);
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed.';
        }
        
        // Check MIME type
        $mimeType = $file->getMimeType();
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
        
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = 'Invalid file type detected.';
        }
        
        // Check for executable files
        $executableExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'jsp'];
        if (in_array($extension, $executableExtensions)) {
            $errors[] = 'Executable files are not allowed.';
        }
        
        // Log file upload attempt
        $this->logSecurityEvent('file_upload_attempt', [
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'valid' => empty($errors),
        ]);
        
        return $errors;
    }

    /**
     * Sanitize HTML content while preserving safe tags.
     */
    public function sanitizeHTML(string $content): string
    {
        $allowedTags = config('security.input_validation.allowed_html_tags', '');
        
        // Strip all tags except allowed ones
        $content = strip_tags($content, $allowedTags);
        
        // Remove javascript: protocol
        $content = preg_replace('/javascript:/i', '', $content);
        
        // Remove on* event handlers
        $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        // Remove style attributes that could contain malicious CSS
        $content = preg_replace('/\s*style\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        return $content;
    }

    /**
     * Check if password meets security requirements.
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        $config = config('security.password');
        
        if (strlen($password) < $config['min_length']) {
            $errors[] = "Password must be at least {$config['min_length']} characters long.";
        }
        
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if ($config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        if ($config['require_symbols'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        return $errors;
    }

    /**
     * Generate a secure random token.
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Check if user session should be invalidated due to inactivity.
     */
    public function shouldInvalidateSession(): bool
    {
        $lastActivity = session('last_activity');
        $timeout = config('security.session.timeout_minutes', 120) * 60;
        
        if (!$lastActivity) {
            return false;
        }
        
        return (time() - $lastActivity) > $timeout;
    }

    /**
     * Update user's last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        session(['last_activity' => time()]);
    }

    /**
     * Check if request is from a bot or automated script.
     */
    public function isBotRequest(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $botSignatures = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java',
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot'
        ];
        
        foreach ($botSignatures as $signature) {
            if (strpos($userAgent, $signature) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate Content Security Policy header value.
     */
    public function generateCSPHeader(): string
    {
        $csp = config('security.csp');
        $directives = [];
        
        foreach ($csp as $directive => $value) {
            $directives[] = str_replace('_', '-', $directive) . ' ' . $value;
        }
        
        return implode('; ', $directives);
    }

    /**
     * Log failed authentication attempt.
     */
    public function logFailedAuthentication(string $email, string $ip): void
    {
        $this->logSecurityEvent('failed_authentication', [
            'email' => $email,
            'ip' => $ip,
        ]);
        
        $this->markIPAsSuspicious($ip);
    }

    /**
     * Log successful authentication.
     */
    public function logSuccessfulAuthentication(string $email, string $ip): void
    {
        $this->logSecurityEvent('successful_authentication', [
            'email' => $email,
            'ip' => $ip,
        ]);
        
        $this->clearSuspiciousIP($ip);
    }
}