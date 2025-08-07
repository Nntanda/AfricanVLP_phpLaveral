@props([
    'user' => null,
    'transparent' => false,
    'fixed' => false
])

@php
$navClasses = 'w-full z-40 transition-all duration-300';
$navClasses .= $fixed ? ' fixed top-0' : '';
$navClasses .= $transparent ? ' bg-transparent' : ' bg-white shadow-sm border-b border-gray-200';
@endphp

<nav {{ $attributes->merge(['class' => $navClasses]) }} 
     x-data="{ 
         mobileMenuOpen: false, 
         userMenuOpen: false,
         scrolled: false 
     }"
     x-init="
         window.addEventListener('scroll', () => {
             scrolled = window.scrollY > 10;
         });
     "
     :class="{ 'bg-white shadow-sm border-b border-gray-200': scrolled && {{ $transparent ? 'true' : 'false' }} }">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Main Navigation -->
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">AU</span>
                        </div>
                        <span class="text-xl font-bold text-gray-900">VLP</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="{{ route('home') }}" 
                       class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                        <i class="fas fa-home mr-1"></i>Home
                    </a>
                    
                    <a href="{{ route('map.index') }}" 
                       class="nav-link {{ request()->routeIs('map.*') ? 'active' : '' }}">
                        <i class="fas fa-map-marked-alt mr-1"></i>Map
                    </a>
                    
                    @auth
                        @if(auth()->user()->role === 'user')
                            <a href="{{ route('client.dashboard') }}" 
                               class="nav-link {{ request()->routeIs('client.*') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                            </a>
                        @endif
                        
                        @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                            <a href="{{ route('admin.dashboard') }}" 
                               class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                                <i class="fas fa-cog mr-1"></i>Admin
                            </a>
                        @endif
                    @endauth
                    
                    @guest
                        <a href="#" class="nav-link">
                            <i class="fas fa-calendar-alt mr-1"></i>Events
                        </a>
                        
                        <a href="#" class="nav-link">
                            <i class="fas fa-building mr-1"></i>Organizations
                        </a>
                        
                        <a href="#" class="nav-link">
                            <i class="fas fa-newspaper mr-1"></i>News
                        </a>
                    @endguest
                </div>
            </div>

            <!-- Right side -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
                @auth
                    <!-- Notifications -->
                    <button class="nav-icon-button" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>

                    <!-- User Menu -->
                    <div class="relative" @click.outside="userMenuOpen = false">
                        <button @click="userMenuOpen = !userMenuOpen" 
                                class="flex items-center space-x-2 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <img class="h-8 w-8 rounded-full object-cover" 
                                 src="{{ auth()->user()->profile_picture ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}" 
                                 alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}">
                            <span class="hidden md:block text-gray-700 font-medium">{{ auth()->user()->first_name }}</span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </button>

                        <!-- User Dropdown -->
                        <div x-show="userMenuOpen" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                             style="display: none;">
                            
                            <div class="py-1">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                </div>
                                
                                @if(auth()->user()->role === 'user')
                                    <a href="{{ route('client.profile') }}" 
                                       class="dropdown-item">
                                        <i class="fas fa-user mr-2"></i>Profile
                                    </a>
                                    
                                    <a href="{{ route('client.dashboard') }}" 
                                       class="dropdown-item">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                @endif
                                
                                @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                                    <a href="{{ route('admin.dashboard') }}" 
                                       class="dropdown-item">
                                        <i class="fas fa-cog mr-2"></i>Admin Panel
                                    </a>
                                @endif
                                
                                <div class="border-t border-gray-100"></div>
                                
                                <a href="#" class="dropdown-item">
                                    <i class="fas fa-cog mr-2"></i>Settings
                                </a>
                                
                                <a href="#" class="dropdown-item">
                                    <i class="fas fa-question-circle mr-2"></i>Help
                                </a>
                                
                                <div class="border-t border-gray-100"></div>
                                
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item w-full text-left">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Guest Navigation -->
                    <a href="{{ route('login') }}" class="nav-link">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login
                    </a>
                    
                    <a href="{{ route('register') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <i class="fas fa-user-plus mr-1"></i>Register
                    </a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="sm:hidden flex items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors duration-200">
                    <span class="sr-only">Open main menu</span>
                    <i class="fas fa-bars text-lg" x-show="!mobileMenuOpen"></i>
                    <i class="fas fa-times text-lg" x-show="mobileMenuOpen" style="display: none;"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="sm:hidden bg-white border-t border-gray-200 shadow-lg"
         style="display: none;">
        
        <div class="pt-2 pb-3 space-y-1">
            <a href="{{ route('home') }}" 
               class="mobile-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-home mr-3"></i>Home
            </a>
            
            <a href="{{ route('map.index') }}" 
               class="mobile-nav-link {{ request()->routeIs('map.*') ? 'active' : '' }}">
                <i class="fas fa-map-marked-alt mr-3"></i>Map
            </a>
            
            @auth
                @if(auth()->user()->role === 'user')
                    <a href="{{ route('client.dashboard') }}" 
                       class="mobile-nav-link {{ request()->routeIs('client.*') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                    </a>
                @endif
                
                @if(in_array(auth()->user()->role, ['admin', 'super_admin']))
                    <a href="{{ route('admin.dashboard') }}" 
                       class="mobile-nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                        <i class="fas fa-cog mr-3"></i>Admin
                    </a>
                @endif
            @else
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-calendar-alt mr-3"></i>Events
                </a>
                
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-building mr-3"></i>Organizations
                </a>
                
                <a href="#" class="mobile-nav-link">
                    <i class="fas fa-newspaper mr-3"></i>News
                </a>
            @endauth
        </div>
        
        @auth
            <!-- Mobile user menu -->
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center px-4 mb-3">
                    <div class="flex-shrink-0">
                        <img class="h-10 w-10 rounded-full object-cover" 
                             src="{{ auth()->user()->profile_picture ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}" 
                             alt="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}">
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium text-gray-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                
                <div class="space-y-1">
                    @if(auth()->user()->role === 'user')
                        <a href="{{ route('client.profile') }}" class="mobile-nav-link">
                            <i class="fas fa-user mr-3"></i>Profile
                        </a>
                    @endif
                    
                    <a href="#" class="mobile-nav-link">
                        <i class="fas fa-cog mr-3"></i>Settings
                    </a>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mobile-nav-link w-full text-left">
                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- Mobile guest menu -->
            <div class="pt-4 pb-3 border-t border-gray-200 space-y-1">
                <a href="{{ route('login') }}" class="mobile-nav-link">
                    <i class="fas fa-sign-in-alt mr-3"></i>Login
                </a>
                
                <a href="{{ route('register') }}" class="mobile-nav-link">
                    <i class="fas fa-user-plus mr-3"></i>Register
                </a>
            </div>
        @endauth
    </div>
</nav>

<style>
    .nav-link {
        @apply border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200;
    }
    
    .nav-link.active {
        @apply border-blue-500 text-blue-600;
    }
    
    .nav-icon-button {
        @apply relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full transition-colors duration-200;
    }
    
    .notification-badge {
        @apply absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center;
    }
    
    .dropdown-item {
        @apply block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200;
    }
    
    .mobile-nav-link {
        @apply block pl-3 pr-4 py-2 border-l-4 text-base font-medium transition-colors duration-200;
    }
    
    .mobile-nav-link:not(.active) {
        @apply border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300;
    }
    
    .mobile-nav-link.active {
        @apply bg-blue-50 border-blue-500 text-blue-700;
    }
</style>