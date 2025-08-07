<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - AU VLP</title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div class="flex h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
             :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
            
            <!-- Sidebar Header -->
            <div class="flex items-center justify-center h-16 bg-gray-800">
                <h1 class="text-white text-xl font-bold">AU VLP Admin</h1>
            </div>

            <!-- Navigation -->
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>

                    <!-- Users -->
                    <a href="{{ route('admin.users.index') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-users mr-3"></i>
                        Users
                    </a>

                    <!-- Organizations -->
                    <a href="{{ route('admin.organizations.index') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.organizations.*') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-building mr-3"></i>
                        Organizations
                    </a>

                    <!-- Events -->
                    <a href="{{ route('admin.events.index') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.events.*') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-calendar-alt mr-3"></i>
                        Events
                    </a>

                    <!-- News -->
                    <a href="{{ route('admin.news.index') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.news.*') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-newspaper mr-3"></i>
                        News
                    </a>

                    <!-- Blog Posts -->
                    <a href="{{ route('admin.blog-posts.index') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.blog-posts.*') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-blog mr-3"></i>
                        Blog Posts
                    </a>

                    <!-- Resources -->
                    <a href="{{ route('admin.resources.index') }}" 
                       class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200 {{ request()->routeIs('admin.resources.*') ? 'bg-gray-700 text-white' : '' }}">
                        <i class="fas fa-file-alt mr-3"></i>
                        Resources
                    </a>

                    <!-- System -->
                    <div class="pt-4 mt-4 border-t border-gray-700">
                        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p>
                        
                        <a href="{{ route('admin.system.health') }}" 
                           class="flex items-center px-4 py-2 mt-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                            <i class="fas fa-heartbeat mr-3"></i>
                            System Health
                        </a>

                        @if($isSuperAdmin)
                        <a href="{{ route('admin.settings') }}" 
                           class="flex items-center px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors duration-200">
                            <i class="fas fa-cog mr-3"></i>
                            Settings
                        </a>
                        @endif
                    </div>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-6 py-4">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = !sidebarOpen" 
                            class="text-gray-500 hover:text-gray-600 lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex-1 lg:ml-0 ml-4">
                        <h2 class="text-2xl font-semibold text-gray-900">
                            @yield('page-title', 'Dashboard')
                        </h2>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen" 
                                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <img class="h-8 w-8 rounded-full bg-gray-300" 
                                 src="{{ $authUser->profile_picture ?? 'https://ui-avatars.com/api/?name=' . urlencode($authUser->first_name . ' ' . $authUser->last_name) }}" 
                                 alt="{{ $authUser->first_name }} {{ $authUser->last_name }}">
                            <span class="ml-2 text-gray-700">{{ $authUser->first_name }} {{ $authUser->last_name }}</span>
                            <i class="fas fa-chevron-down ml-1 text-gray-400"></i>
                        </button>

                        <!-- User Dropdown -->
                        <div x-show="userMenuOpen" 
                             @click.away="userMenuOpen = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            
                            <a href="{{ route('admin.profile') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            
                            <a href="{{ route('client.dashboard') }}" 
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-external-link-alt mr-2"></i>View Client Site
                            </a>
                            
                            <div class="border-t border-gray-100"></div>
                            
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" 
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-6 py-8">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" x-data="{ show: true }" x-show="show">
                            <div class="flex justify-between items-center">
                                <span>{{ session('success') }}</span>
                                <button @click="show = false" class="text-green-700 hover:text-green-900">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" x-data="{ show: true }" x-show="show">
                            <div class="flex justify-between items-center">
                                <span>{{ session('error') }}</span>
                                <button @click="show = false" class="text-red-700 hover:text-red-900">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" x-data="{ show: true }" x-show="show">
                            <div class="flex justify-between items-start">
                                <div>
                                    <ul class="list-disc list-inside">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <button @click="show = false" class="text-red-700 hover:text-red-900 ml-4">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Page Content -->
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    @stack('scripts')
</body>
</html>