<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AU VLP - African Union Volunteer Leadership Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">AU VLP</h1>
                <p class="text-lg text-gray-600 mb-8">African Union Volunteer Leadership Platform</p>
                
                <div class="space-y-4">
                    @guest
                        <div class="flex space-x-4 justify-center">
                            <a href="{{ route('login') }}" 
                               class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Login
                            </a>
                            <a href="{{ route('register') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Register
                            </a>
                        </div>
                    @else
                        <div class="space-y-2">
                            <p class="text-gray-700">Welcome back, {{ auth()->user()->first_name }}!</p>
                            <div class="flex space-x-4 justify-center">
                                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin')
                                    <a href="{{ route('admin.dashboard') }}" 
                                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                        Admin Dashboard
                                    </a>
                                @endif
                                <a href="{{ route('client.dashboard') }}" 
                                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Dashboard
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</body>
</html>