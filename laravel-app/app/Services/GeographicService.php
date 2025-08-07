<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeographicService
{
    protected $geocodingApiKey;
    protected $isConfigured = false;

    public function __construct()
    {
        $this->geocodingApiKey = config('services.google_maps.api_key');
        $this->isConfigured = !empty($this->geocodingApiKey);
    }

    /**
     * Check if geographic services are configured
     */
    public function isAvailable(): bool
    {
        return $this->isConfigured;
    }

    /**
     * Geocode an address to get coordinates
     */
    public function geocodeAddress(string $address): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Geographic services not configured'
            ];
        }

        try {
            $cacheKey = 'geocode_' . md5($address);
            
            // Check cache first
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                return array_merge($cachedResult, ['cached' => true]);
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $this->geocodingApiKey
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Geocoding API request failed'
                ];
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return [
                    'success' => false,
                    'error' => 'Address not found'
                ];
            }

            $result = $data['results'][0];
            $location = $result['geometry']['location'];

            $geocodeResult = [
                'success' => true,
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'formatted_address' => $result['formatted_address'],
                'place_id' => $result['place_id'],
                'address_components' => $this->parseAddressComponents($result['address_components']),
                'cached' => false
            ];

            // Cache for 24 hours
            Cache::put($cacheKey, $geocodeResult, 24 * 60 * 60);

            return $geocodeResult;

        } catch (\Exception $e) {
            Log::error('Geocoding failed', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Geocoding failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reverse geocode coordinates to get address
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Geographic services not configured'
            ];
        }

        try {
            $cacheKey = 'reverse_geocode_' . md5($latitude . '_' . $longitude);
            
            // Check cache first
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                return array_merge($cachedResult, ['cached' => true]);
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $latitude . ',' . $longitude,
                'key' => $this->geocodingApiKey
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Reverse geocoding API request failed'
                ];
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return [
                    'success' => false,
                    'error' => 'Location not found'
                ];
            }

            $result = $data['results'][0];

            $reverseGeocodeResult = [
                'success' => true,
                'formatted_address' => $result['formatted_address'],
                'place_id' => $result['place_id'],
                'address_components' => $this->parseAddressComponents($result['address_components']),
                'cached' => false
            ];

            // Cache for 24 hours
            Cache::put($cacheKey, $reverseGeocodeResult, 24 * 60 * 60);

            return $reverseGeocodeResult;

        } catch (\Exception $e) {
            Log::error('Reverse geocoding failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Reverse geocoding failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    public function calculateDistance(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2, 
        string $unit = 'km'
    ): float {
        $earthRadius = $unit === 'miles' ? 3959 : 6371; // Earth radius in km or miles

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Find nearby locations within a radius
     */
    public function findNearbyLocations(
        float $latitude, 
        float $longitude, 
        float $radiusKm, 
        $query
    ): array {
        try {
            // Calculate bounding box for efficient database query
            $boundingBox = $this->calculateBoundingBox($latitude, $longitude, $radiusKm);

            // Apply geographic filtering to the query
            $results = $query->whereBetween('latitude', [$boundingBox['min_lat'], $boundingBox['max_lat']])
                           ->whereBetween('longitude', [$boundingBox['min_lng'], $boundingBox['max_lng']])
                           ->get();

            // Filter by exact distance and add distance information
            $nearbyLocations = $results->map(function ($item) use ($latitude, $longitude, $radiusKm) {
                if ($item->latitude && $item->longitude) {
                    $distance = $this->calculateDistance(
                        $latitude, 
                        $longitude, 
                        $item->latitude, 
                        $item->longitude
                    );

                    if ($distance <= $radiusKm) {
                        $item->distance = $distance;
                        return $item;
                    }
                }
                return null;
            })->filter()->sortBy('distance');

            return [
                'success' => true,
                'locations' => $nearbyLocations->values(),
                'total_found' => $nearbyLocations->count()
            ];

        } catch (\Exception $e) {
            Log::error('Nearby locations search failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radiusKm,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Nearby locations search failed'
            ];
        }
    }

    /**
     * Get geographic bounds for a set of coordinates
     */
    public function getBounds(array $coordinates): array
    {
        if (empty($coordinates)) {
            return [
                'success' => false,
                'error' => 'No coordinates provided'
            ];
        }

        $latitudes = array_column($coordinates, 'latitude');
        $longitudes = array_column($coordinates, 'longitude');

        // Filter out null values
        $latitudes = array_filter($latitudes, fn($lat) => $lat !== null);
        $longitudes = array_filter($longitudes, fn($lng) => $lng !== null);

        if (empty($latitudes) || empty($longitudes)) {
            return [
                'success' => false,
                'error' => 'No valid coordinates found'
            ];
        }

        return [
            'success' => true,
            'bounds' => [
                'north' => max($latitudes),
                'south' => min($latitudes),
                'east' => max($longitudes),
                'west' => min($longitudes),
                'center' => [
                    'latitude' => (max($latitudes) + min($latitudes)) / 2,
                    'longitude' => (max($longitudes) + min($longitudes)) / 2
                ]
            ]
        ];
    }

    /**
     * Generate map URL for static map
     */
    public function generateStaticMapUrl(array $options): string
    {
        if (!$this->isAvailable()) {
            return '';
        }

        $defaultOptions = [
            'size' => '600x400',
            'zoom' => 10,
            'maptype' => 'roadmap',
            'format' => 'png'
        ];

        $options = array_merge($defaultOptions, $options);
        $params = [
            'size' => $options['size'],
            'zoom' => $options['zoom'],
            'maptype' => $options['maptype'],
            'format' => $options['format'],
            'key' => $this->geocodingApiKey
        ];

        // Add center point
        if (isset($options['center'])) {
            $params['center'] = $options['center']['latitude'] . ',' . $options['center']['longitude'];
        }

        // Add markers
        if (isset($options['markers']) && is_array($options['markers'])) {
            $markerStrings = [];
            foreach ($options['markers'] as $marker) {
                $markerString = '';
                if (isset($marker['color'])) {
                    $markerString .= 'color:' . $marker['color'] . '|';
                }
                if (isset($marker['size'])) {
                    $markerString .= 'size:' . $marker['size'] . '|';
                }
                $markerString .= $marker['latitude'] . ',' . $marker['longitude'];
                $markerStrings[] = $markerString;
            }
            $params['markers'] = $markerStrings;
        }

        return 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($params);
    }

    /**
     * Validate coordinates
     */
    public function validateCoordinates(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90 && 
               $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Parse address components from Google Maps API response
     */
    protected function parseAddressComponents(array $components): array
    {
        $parsed = [
            'street_number' => '',
            'route' => '',
            'locality' => '',
            'administrative_area_level_1' => '',
            'administrative_area_level_2' => '',
            'country' => '',
            'postal_code' => ''
        ];

        foreach ($components as $component) {
            $type = $component['types'][0];
            if (array_key_exists($type, $parsed)) {
                $parsed[$type] = $component['long_name'];
            }
        }

        return $parsed;
    }

    /**
     * Calculate bounding box for geographic queries
     */
    protected function calculateBoundingBox(float $latitude, float $longitude, float $radiusKm): array
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $latRadian = deg2rad($latitude);
        $degLatKm = 110.574235;
        $degLngKm = 110.572833 * cos($latRadian);

        $deltaLat = $radiusKm / $degLatKm;
        $deltaLng = $radiusKm / $degLngKm;

        return [
            'min_lat' => $latitude - $deltaLat,
            'max_lat' => $latitude + $deltaLat,
            'min_lng' => $longitude - $deltaLng,
            'max_lng' => $longitude + $deltaLng
        ];
    }

    /**
     * Get timezone for coordinates
     */
    public function getTimezone(float $latitude, float $longitude): array
    {
        if (!$this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Geographic services not configured'
            ];
        }

        try {
            $cacheKey = 'timezone_' . md5($latitude . '_' . $longitude);
            
            // Check cache first
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                return array_merge($cachedResult, ['cached' => true]);
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/timezone/json', [
                'location' => $latitude . ',' . $longitude,
                'timestamp' => time(),
                'key' => $this->geocodingApiKey
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Timezone API request failed'
                ];
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                return [
                    'success' => false,
                    'error' => 'Timezone not found'
                ];
            }

            $timezoneResult = [
                'success' => true,
                'timezone_id' => $data['timeZoneId'],
                'timezone_name' => $data['timeZoneName'],
                'dst_offset' => $data['dstOffset'],
                'raw_offset' => $data['rawOffset'],
                'cached' => false
            ];

            // Cache for 30 days (timezones don't change often)
            Cache::put($cacheKey, $timezoneResult, 30 * 24 * 60 * 60);

            return $timezoneResult;

        } catch (\Exception $e) {
            Log::error('Timezone lookup failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Timezone lookup failed: ' . $e->getMessage()
            ];
        }
    }
}