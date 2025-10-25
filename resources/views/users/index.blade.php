<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl dark:text-white text-gray-800 leading-tight">
                {{ __('User Management') }}
            </h2>
            @auth
            @if (auth()->user()->role === 'admin')
            <div class="ml-4">
                @livewire('admin-notification-bell')
            </div>
            @endif
            @endauth
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:user-management />
        </div>
    </div>
</x-app-layout>