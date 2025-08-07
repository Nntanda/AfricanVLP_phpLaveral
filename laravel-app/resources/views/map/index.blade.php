@extends('layouts.app')

@section('title', 'Map View')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Map View</h1>
                    <p class="text-gray-600">Explore events and organizations near you</p>
                </div>
                
                <!-- Filters -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" 
                               id="search-input"
                               placeholder="Search locations..."
                               value="{{ $filters['search'] ?? '' }}"
                               class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                    </div>
                    
                    <select id="type-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="both">All</option>
                        <option value="events">Events</option>
                        <option value="organizations">Organizations</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="relative">
        <div id="map" class="w-full h-screen"></div>
        
        <!-- Map Controls -->
        <div class="absolute top-4 left-4 bg-white rounded-lg shadow-lg p-4 space-y-2">
            <button id="locate-me" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-location-arrow mr-2"></i>
                Find My Location
            </button>
            
            <div class="flex items-center space-x-2">
                <label class="flex items-center">
                    <input type="checkbox" id="show-events" checked class="rounded border-gray-300 text-blue-600">
                    <span class="ml-2 text-sm">Events</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" id="show-organizations" checked class="rounded border-gray-300 text-blue-600">
                    <span class="ml-2 text-sm">Organizations</span>
                </label>
            </div>
        </div>

        <!-- Legend -->
        <div class="absolute bottom-4 left-4 bg-white rounded-lg shadow-lg p-4">
            <h3 class="font-semibold text-gray-900 mb-2">Legend</h3>
            <div class="space-y-1">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                    <span class="text-sm text-gray-700">Events</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-blue-500 rounded-full mr-2"></div>
                    <span class="text-sm text-gray-700">Organizations</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div id="info-panel" class="hidden fixed top-0 right-0 w-96 h-full bg-white shadow-xl z-50 overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 id="info-title" class="text-xl font-bold text-gray-900"></h2>
                <button id="close-info" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="info-content"></div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
        <span class="text-gray-700">Loading map data...</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
let map;
let markers = [];
let infoWindow;
let userLocation = null;

// Initialize map
function initMap() {
    // Default center (Africa)
    const defaultCenter = { lat: 0, lng: 20 };
    
    @if($bounds['success'] ?? false)
        const bounds = @json($bounds['bounds']);
        const center = { 
            lat: bounds.center.latitude, 
            lng: bounds.center.longitude 
        };
    @else
        const center = defaultCenter;
    @endif

    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 6,
        center: center,
        styles: [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            }
        ]
    });

    infoWindow = new google.maps.InfoWindow();

    // Load initial data
    loadMapData();

    // Set up event listeners
    setupEventListeners();
}

// Load map data
function loadMapData() {
    showLoading(true);
    
    const showEvents = document.getElementById('show-events').checked;
    const showOrganizations = document.getElementById('show-organizations').checked;
    const search = document.getElementById('search-input').value;

    const promises = [];

    if (showEvents) {
        promises.push(
            fetch(`{{ route('map.events-data') }}?search=${encodeURIComponent(search)}`)
                .then(response => response.json())
        );
    }

    if (showOrganizations) {
        promises.push(
            fetch(`{{ route('map.organizations-data') }}?search=${encodeURIComponent(search)}`)
                .then(response => response.json())
        );
    }

    Promise.all(promises)
        .then(results => {
            clearMarkers();
            
            results.forEach(result => {
                if (result.success) {
                    if (result.events) {
                        addEventMarkers(result.events);
                    }
                    if (result.organizations) {
                        addOrganizationMarkers(result.organizations);
                    }
                }
            });
            
            showLoading(false);
        })
        .catch(error => {
            console.error('Error loading map data:', error);
            showLoading(false);
        });
}

// Add event markers
function addEventMarkers(events) {
    events.forEach(event => {
        const marker = new google.maps.Marker({
            position: { lat: event.latitude, lng: event.longitude },
            map: map,
            title: event.title,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" fill="#ef4444" stroke="#ffffff" stroke-width="2"/>
                        <path d="M12 6v6l4 2" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                `),
                scaledSize: new google.maps.Size(24, 24)
            }
        });

        marker.addListener('click', () => {
            showEventInfo(event);
        });

        markers.push(marker);
    });
}

// Add organization markers
function addOrganizationMarkers(organizations) {
    organizations.forEach(org => {
        const marker = new google.maps.Marker({
            position: { lat: org.latitude, lng: org.longitude },
            map: map,
            title: org.name,
            icon: {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" fill="#3b82f6" stroke="#ffffff" stroke-width="2"/>
                        <path d="M8 12h8M8 8h8M8 16h8" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                `),
                scaledSize: new google.maps.Size(24, 24)
            }
        });

        marker.addListener('click', () => {
            showOrganizationInfo(org);
        });

        markers.push(marker);
    });
}

// Clear all markers
function clearMarkers() {
    markers.forEach(marker => {
        marker.setMap(null);
    });
    markers = [];
}

// Show event info
function showEventInfo(event) {
    const content = `
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">${event.title}</h3>
                <p class="text-sm text-gray-600">${event.organization.name}</p>
            </div>
            
            <div class="text-sm text-gray-700">
                <p><i class="fas fa-calendar mr-2"></i>${event.start_date}</p>
                <p><i class="fas fa-map-marker-alt mr-2"></i>${event.location}</p>
            </div>
            
            <p class="text-sm text-gray-700">${event.description}</p>
            
            <div class="flex space-x-2">
                <a href="${event.url}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    View Details
                </a>
            </div>
        </div>
    `;
    
    document.getElementById('info-title').textContent = event.title;
    document.getElementById('info-content').innerHTML = content;
    document.getElementById('info-panel').classList.remove('hidden');
}

// Show organization info
function showOrganizationInfo(org) {
    const content = `
        <div class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">${org.name}</h3>
                ${org.category ? `<p class="text-sm text-gray-600">${org.category}</p>` : ''}
            </div>
            
            <div class="text-sm text-gray-700">
                <p><i class="fas fa-map-marker-alt mr-2"></i>${org.address}</p>
                ${org.phone ? `<p><i class="fas fa-phone mr-2"></i>${org.phone}</p>` : ''}
                ${org.email ? `<p><i class="fas fa-envelope mr-2"></i>${org.email}</p>` : ''}
                ${org.website ? `<p><i class="fas fa-globe mr-2"></i><a href="${org.website}" target="_blank" class="text-blue-600 hover:underline">${org.website}</a></p>` : ''}
            </div>
            
            <p class="text-sm text-gray-700">${org.about}</p>
            
            <div class="flex space-x-2">
                <a href="${org.url}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    View Profile
                </a>
            </div>
        </div>
    `;
    
    document.getElementById('info-title').textContent = org.name;
    document.getElementById('info-content').innerHTML = content;
    document.getElementById('info-panel').classList.remove('hidden');
}

// Setup event listeners
function setupEventListeners() {
    // Search input
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadMapData, 500);
    });

    // Type filter
    document.getElementById('type-filter').addEventListener('change', loadMapData);

    // Show/hide toggles
    document.getElementById('show-events').addEventListener('change', loadMapData);
    document.getElementById('show-organizations').addEventListener('change', loadMapData);

    // Locate me button
    document.getElementById('locate-me').addEventListener('click', () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    map.setCenter(userLocation);
                    map.setZoom(12);
                    
                    // Add user location marker
                    new google.maps.Marker({
                        position: userLocation,
                        map: map,
                        title: 'Your Location',
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" fill="#10b981" stroke="#ffffff" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" fill="#ffffff"/>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(24, 24)
                        }
                    });
                },
                error => {
                    alert('Error getting your location: ' + error.message);
                }
            );
        } else {
            alert('Geolocation is not supported by this browser.');
        }
    });

    // Close info panel
    document.getElementById('close-info').addEventListener('click', () => {
        document.getElementById('info-panel').classList.add('hidden');
    });
}

// Show/hide loading overlay
function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    if (show) {
        overlay.classList.remove('hidden');
    } else {
        overlay.classList.add('hidden');
    }
}

// Initialize map when page loads
window.addEventListener('load', () => {
    if (typeof google !== 'undefined') {
        initMap();
    }
});
</script>

<!-- Google Maps API -->
<script async defer 
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&callback=initMap">
</script>
@endpush