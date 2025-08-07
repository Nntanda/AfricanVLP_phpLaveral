@extends('layouts.client')

@section('title', 'Profile')

@section('content')
<div class="space-y-6">
    <!-- Profile Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center space-x-6">
                <div class="flex-shrink-0">
                    <img class="h-20 w-20 rounded-full" 
                         src="{{ $user->profile_picture ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->first_name . ' ' . $user->last_name) . '&size=80' }}" 
                         alt="{{ $user->first_name }} {{ $user->last_name }}">
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $user->first_name }} {{ $user->last_name }}
                    </h1>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <div class="mt-2 flex items-center space-x-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $user->is_email_verified ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $user->is_email_verified ? 'Email Verified' : 'Email Not Verified' }}
                        </span>
                        @if($user->country)
                            <span class="text-sm text-gray-500">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $user->city ? $user->city->name . ', ' : '' }}{{ $user->country->nicename }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ $profileCompletion }}%</div>
                        <div class="text-sm text-gray-500">Profile Complete</div>
                        <div class="w-16 bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $profileCompletion }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Information Form -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Profile Information</h3>
                    
                    <form method="POST" action="{{ route('client.profile.update') }}">
                        @csrf
                        @method('PATCH')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="first_name" id="first_name" 
                                       value="{{ old('first_name', $user->first_name) }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" name="last_name" id="last_name" 
                                       value="{{ old('last_name', $user->last_name) }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" name="phone_number" id="phone_number" 
                                       value="{{ old('phone_number', $user->phone_number) }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('phone_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" 
                                       value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('date_of_birth')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                <select name="gender" id="gender" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->gender) === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Country -->
                            <div>
                                <label for="country_id" class="block text-sm font-medium text-gray-700">Country</label>
                                <select name="country_id" id="country_id" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Country</option>
                                    <!-- Countries will be loaded via JavaScript or server-side -->
                                </select>
                                @error('country_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- About -->
                        <div class="mt-6">
                            <label for="about" class="block text-sm font-medium text-gray-700">About</label>
                            <textarea name="about" id="about" rows="4" 
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                      placeholder="Tell us about yourself...">{{ old('about', $user->about) }}</textarea>
                            @error('about')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6">
                            <button type="submit" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-save mr-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Account Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                    
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900">{{ $user->email }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                            <dd class="text-sm text-gray-900">
                                {{ $user->created ? \Carbon\Carbon::parse($user->created)->format('F j, Y') : 'Unknown' }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $user->status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->status === 1 ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- My Organizations -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">My Organizations</h3>
                        <a href="{{ route('client.organizations.my') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                            View all
                        </a>
                    </div>
                    
                    @if($user->organizations->count() > 0)
                        <div class="space-y-3">
                            @foreach($user->organizations->take(3) as $organization)
                                <div class="flex items-center space-x-3">
                                    <div class="h-8 w-8 bg-gray-300 rounded-md flex items-center justify-center flex-shrink-0">
                                        @if($organization->logo)
                                            <img src="{{ $organization->logo }}" alt="{{ $organization->name }}" class="h-8 w-8 rounded-md">
                                        @else
                                            <i class="fas fa-building text-gray-500 text-sm"></i>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <a href="{{ route('client.organizations.show', $organization) }}" class="hover:text-indigo-600">
                                                {{ $organization->name }}
                                            </a>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ ucfirst($organization->pivot->role) }} • 
                                            Joined {{ \Carbon\Carbon::parse($organization->pivot->created)->format('M Y') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 mb-2">No organizations yet</p>
                            <a href="{{ route('client.organizations.index') }}" 
                               class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Browse organizations
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    
                    <div class="space-y-3">
                        <a href="{{ route('client.organizations.index') }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                            <i class="fas fa-building mr-2 text-blue-500"></i>Browse Organizations
                        </a>
                        
                        <a href="{{ route('client.events.index') }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                            <i class="fas fa-calendar-alt mr-2 text-green-500"></i>Find Events
                        </a>
                        
                        <a href="{{ route('client.resources.index') }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                            <i class="fas fa-file-alt mr-2 text-purple-500"></i>Browse Resources
                        </a>
                        
                        <a href="{{ route('client.news.index') }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                            <i class="fas fa-newspaper mr-2 text-yellow-500"></i>Read News
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection