<?php

namespace App\Services;

use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GoogleTranslateService
{
    protected $translateClient;
    protected $isConfigured = false;
    protected $supportedLanguages = [
        'en' => 'English',
        'fr' => 'French',
        'es' => 'Spanish',
        'pt' => 'Portuguese',
        'ar' => 'Arabic',
        'sw' => 'Swahili',
        'am' => 'Amharic',
        'ha' => 'Hausa',
        'yo' => 'Yoruba',
        'ig' => 'Igbo',
        'zu' => 'Zulu',
        'af' => 'Afrikaans',
    ];

    public function __construct()
    {
        // Check if Google Translate is configured
        $this->isConfigured = !empty(config('services.google_translate.api_key'));

        if ($this->isConfigured) {
            try {
                $this->translateClient = new TranslateClient([
                    'key' => config('services.google_translate.api_key')
                ]);
            } catch (\Exception $e) {
                Log::error('Google Translate initialization failed', [
                    'error' => $e->getMessage()
                ]);
                $this->isConfigured = false;
            }
        }
    }

    /**
     * Check if Google Translate is configured and available
     */
    public function isAvailable(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }

    /**
     * Translate text to target language
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = null): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Google Translate is not configured'
            ];
        }

        if (!array_key_exists($targetLanguage, $this->supportedLanguages)) {
            return [
                'success' => false,
                'error' => 'Target language not supported'
            ];
        }

        try {
            // Create cache key for translation
            $cacheKey = 'translate_' . md5($text . $targetLanguage . ($sourceLanguage ?? ''));
            
            // Check cache first
            $cachedTranslation = Cache::get($cacheKey);
            if ($cachedTranslation) {
                return [
                    'success' => true,
                    'translatedText' => $cachedTranslation['translatedText'],
                    'detectedSourceLanguage' => $cachedTranslation['detectedSourceLanguage'],
                    'targetLanguage' => $targetLanguage,
                    'cached' => true
                ];
            }

            // Prepare translation options
            $options = ['target' => $targetLanguage];
            if ($sourceLanguage) {
                $options['source'] = $sourceLanguage;
            }

            // Perform translation
            $result = $this->translateClient->translate($text, $options);

            $translatedText = $result['text'];
            $detectedSourceLanguage = $result['source'] ?? $sourceLanguage;

            // Cache the result for 24 hours
            $cacheData = [
                'translatedText' => $translatedText,
                'detectedSourceLanguage' => $detectedSourceLanguage
            ];
            Cache::put($cacheKey, $cacheData, 24 * 60 * 60);

            return [
                'success' => true,
                'translatedText' => $translatedText,
                'detectedSourceLanguage' => $detectedSourceLanguage,
                'targetLanguage' => $targetLanguage,
                'cached' => false
            ];

        } catch (\Exception $e) {
            Log::error('Google Translate failed', [
                'text' => substr($text, 0, 100) . '...',
                'target_language' => $targetLanguage,
                'source_language' => $sourceLanguage,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Translation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Translate multiple texts at once
     */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = null): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Google Translate is not configured'
            ];
        }

        $results = [];
        $errors = [];

        foreach ($texts as $key => $text) {
            $result = $this->translate($text, $targetLanguage, $sourceLanguage);
            
            if ($result['success']) {
                $results[$key] = $result['translatedText'];
            } else {
                $errors[$key] = $result['error'];
            }
        }

        return [
            'success' => count($results) > 0,
            'translations' => $results,
            'errors' => $errors,
            'total_texts' => count($texts),
            'successful_translations' => count($results),
            'failed_translations' => count($errors)
        ];
    }

    /**
     * Detect language of text
     */
    public function detectLanguage(string $text): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Google Translate is not configured'
            ];
        }

        try {
            $result = $this->translateClient->detectLanguage($text);
            
            return [
                'success' => true,
                'language' => $result['languageCode'],
                'confidence' => $result['confidence'],
                'languageName' => $this->supportedLanguages[$result['languageCode']] ?? 'Unknown'
            ];

        } catch (\Exception $e) {
            Log::error('Language detection failed', [
                'text' => substr($text, 0, 100) . '...',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Language detection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available languages from Google Translate API
     */
    public function getAvailableLanguages(): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Google Translate is not configured'
            ];
        }

        try {
            // Cache the languages list for 24 hours
            $cacheKey = 'google_translate_languages';
            $cachedLanguages = Cache::get($cacheKey);
            
            if ($cachedLanguages) {
                return [
                    'success' => true,
                    'languages' => $cachedLanguages,
                    'cached' => true
                ];
            }

            $languages = $this->translateClient->localizedLanguages();
            
            // Cache the result
            Cache::put($cacheKey, $languages, 24 * 60 * 60);

            return [
                'success' => true,
                'languages' => $languages,
                'cached' => false
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get available languages', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get available languages: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Translate content for a model (news, events, etc.)
     */
    public function translateContent(array $content, string $targetLanguage, array $fieldsToTranslate = ['title', 'description', 'content']): array
    {
        $translatedContent = [];
        $errors = [];

        foreach ($fieldsToTranslate as $field) {
            if (isset($content[$field]) && !empty($content[$field])) {
                $result = $this->translate($content[$field], $targetLanguage);
                
                if ($result['success']) {
                    $translatedContent[$field] = $result['translatedText'];
                } else {
                    $errors[$field] = $result['error'];
                }
            }
        }

        return [
            'success' => count($translatedContent) > 0,
            'translatedContent' => $translatedContent,
            'errors' => $errors,
            'targetLanguage' => $targetLanguage
        ];
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): bool
    {
        try {
            // Clear all translation cache keys
            $cacheKeys = [
                'google_translate_languages'
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            // Clear translation cache by pattern (if using Redis)
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getStore()->getRedis();
                $keys = $redis->keys('translate_*');
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear translation cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}