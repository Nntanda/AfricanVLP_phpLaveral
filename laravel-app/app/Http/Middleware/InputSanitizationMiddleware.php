<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InputSanitizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize input data
        $this->sanitizeInput($request);

        return $next($request);
    }

    /**
     * Sanitize request input data.
     */
    protected function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        
        $sanitized = $this->sanitizeArray($input);
        
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data.
     */
    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize string input.
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Remove potentially dangerous characters for non-HTML fields
        // Note: This is basic sanitization. Rich text fields should be handled separately
        if (!$this->isRichTextField($value)) {
            // Remove script tags and their content
            $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
            
            // Remove javascript: protocol
            $value = preg_replace('/javascript:/i', '', $value);
            
            // Remove on* event handlers
            $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        }

        return $value;
    }

    /**
     * Check if the value appears to be rich text content.
     */
    protected function isRichTextField(string $value): bool
    {
        // Simple heuristic to detect rich text content
        // In a real application, you might check field names or use a whitelist
        return strlen($value) > 100 && (
            strpos($value, '<p>') !== false ||
            strpos($value, '<div>') !== false ||
            strpos($value, '<br>') !== false ||
            strpos($value, '&lt;') !== false
        );
    }
}