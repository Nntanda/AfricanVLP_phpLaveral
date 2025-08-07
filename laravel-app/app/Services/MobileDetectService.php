<?php

namespace App\Services;

use Detection\MobileDetect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MobileDetectService
{
    protected $mobileDetect;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->mobileDetect = new MobileDetect();
        
        // Set user agent from request
        $this->mobileDetect->setUserAgent($request->userAgent());
    }

    /**
     * Check if the current request is from a mobile device
     */
    public function isMobile(): bool
    {
        return $this->mobileDetect->isMobile();
    }

    /**
     * Check if the current request is from a tablet
     */
    public function isTablet(): bool
    {
        return $this->mobileDetect->isTablet();
    }

    /**
     * Check if the current request is from a desktop
     */
    public function isDesktop(): bool
    {
        return !$this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet();
    }

    /**
     * Get device type
     */
    public function getDeviceType(): string
    {
        if ($this->isTablet()) {
            return 'tablet';
        } elseif ($this->isMobile()) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    /**
     * Check if the current request is from a phone (mobile but not tablet)
     */
    public function isPhone(): bool
    {
        return $this->mobileDetect->isMobile() && !$this->mobileDetect->isTablet();
    }

    /**
     * Get operating system
     */
    public function getOperatingSystem(): ?string
    {
        $operatingSystems = [
            'AndroidOS', 'BlackBerryOS', 'PalmOS', 'SymbianOS', 'WindowsMobileOS', 'WindowsPhoneOS',
            'iOS', 'MeeGoOS', 'MaemoOS', 'JavaOS', 'webOS', 'badaOS', 'BREWOS'
        ];

        foreach ($operatingSystems as $os) {
            if ($this->mobileDetect->is($os)) {
                return $os;
            }
        }

        return null;
    }

    /**
     * Get browser information
     */
    public function getBrowser(): ?string
    {
        $browsers = [
            'Chrome', 'Dolfin', 'Opera', 'Skyfire', 'Edge', 'IE', 'Firefox', 'Bolt', 'TeaShark',
            'Blazer', 'Safari', 'WeChat', 'UCBrowser', 'baiduboxapp', 'baidubrowser', 'DiigoBrowser',
            'Puffin', 'Mercury', 'ObigoBrowser', 'NetFront', 'GenericBrowser', 'PaleMoon'
        ];

        foreach ($browsers as $browser) {
            if ($this->mobileDetect->is($browser)) {
                return $browser;
            }
        }

        return null;
    }

    /**
     * Get device brand/manufacturer
     */
    public function getDeviceBrand(): ?string
    {
        $brands = [
            'Samsung', 'Apple', 'Huawei', 'Xiaomi', 'Oppo', 'Vivo', 'OnePlus', 'Motorola',
            'LG', 'Sony', 'HTC', 'Nokia', 'BlackBerry', 'Asus', 'Acer', 'Alcatel',
            'Lenovo', 'ZTE', 'Meizu', 'Honor'
        ];

        foreach ($brands as $brand) {
            if ($this->mobileDetect->is($brand)) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * Get detailed device information
     */
    public function getDeviceInfo(): array
    {
        return [
            'is_mobile' => $this->isMobile(),
            'is_tablet' => $this->isTablet(),
            'is_phone' => $this->isPhone(),
            'is_desktop' => $this->isDesktop(),
            'device_type' => $this->getDeviceType(),
            'operating_system' => $this->getOperatingSystem(),
            'browser' => $this->getBrowser(),
            'device_brand' => $this->getDeviceBrand(),
            'user_agent' => $this->request->userAgent(),
            'ip_address' => $this->request->ip(),
        ];
    }

    /**
     * Get responsive breakpoint based on device
     */
    public function getResponsiveBreakpoint(): string
    {
        if ($this->isPhone()) {
            return 'sm'; // Small screens (phones)
        } elseif ($this->isTablet()) {
            return 'md'; // Medium screens (tablets)
        } else {
            return 'lg'; // Large screens (desktops)
        }
    }

    /**
     * Get recommended image sizes based on device
     */
    public function getRecommendedImageSizes(): array
    {
        $deviceType = $this->getDeviceType();

        switch ($deviceType) {
            case 'mobile':
                return [
                    'thumbnail' => ['width' => 150, 'height' => 150],
                    'small' => ['width' => 300, 'height' => 200],
                    'medium' => ['width' => 480, 'height' => 320],
                    'large' => ['width' => 768, 'height' => 512]
                ];
            case 'tablet':
                return [
                    'thumbnail' => ['width' => 200, 'height' => 200],
                    'small' => ['width' => 400, 'height' => 300],
                    'medium' => ['width' => 600, 'height' => 400],
                    'large' => ['width' => 1024, 'height' => 768]
                ];
            default: // desktop
                return [
                    'thumbnail' => ['width' => 250, 'height' => 250],
                    'small' => ['width' => 500, 'height' => 375],
                    'medium' => ['width' => 800, 'height' => 600],
                    'large' => ['width' => 1200, 'height' => 900]
                ];
        }
    }

    /**
     * Check if device supports specific features
     */
    public function supportsFeature(string $feature): bool
    {
        $supportedFeatures = [
            'touch' => $this->isMobile() || $this->isTablet(),
            'geolocation' => true, // Most modern devices support this
            'camera' => $this->isMobile() || $this->isTablet(),
            'push_notifications' => true, // Most modern browsers support this
            'offline_storage' => true, // Most modern browsers support this
        ];

        return $supportedFeatures[$feature] ?? false;
    }

    /**
     * Get device-specific CSS classes
     */
    public function getCssClasses(): array
    {
        $classes = [];

        if ($this->isMobile()) {
            $classes[] = 'is-mobile';
        }
        if ($this->isTablet()) {
            $classes[] = 'is-tablet';
        }
        if ($this->isPhone()) {
            $classes[] = 'is-phone';
        }
        if ($this->isDesktop()) {
            $classes[] = 'is-desktop';
        }

        $os = $this->getOperatingSystem();
        if ($os) {
            $classes[] = 'os-' . strtolower(str_replace('OS', '', $os));
        }

        $browser = $this->getBrowser();
        if ($browser) {
            $classes[] = 'browser-' . strtolower($browser);
        }

        return $classes;
    }

    /**
     * Get device statistics for analytics
     */
    public function getDeviceStats(): array
    {
        $cacheKey = 'device_stats_' . date('Y-m-d');
        
        return Cache::remember($cacheKey, 3600, function () {
            $deviceInfo = $this->getDeviceInfo();
            
            // In a real implementation, you would store this data in a database
            // and aggregate it for analytics purposes
            return [
                'device_type' => $deviceInfo['device_type'],
                'operating_system' => $deviceInfo['operating_system'],
                'browser' => $deviceInfo['browser'],
                'timestamp' => now(),
                'user_agent' => $deviceInfo['user_agent']
            ];
        });
    }

    /**
     * Generate device-specific meta tags
     */
    public function getMetaTags(): array
    {
        $metaTags = [
            'viewport' => 'width=device-width, initial-scale=1.0'
        ];

        if ($this->isMobile()) {
            $metaTags['mobile-web-app-capable'] = 'yes';
            $metaTags['apple-mobile-web-app-capable'] = 'yes';
            $metaTags['apple-mobile-web-app-status-bar-style'] = 'default';
        }

        return $metaTags;
    }
}