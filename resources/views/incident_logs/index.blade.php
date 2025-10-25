<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                {{ __('Incident Logs') }}
            </h2>
            @auth
            @if(auth()->user()->role === 'admin')
            <div class="ml-4">
                @livewire('admin-notification-bell')
            </div>
            @endif
            @endauth
        </div>
    </x-slot>

    <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-4"></h1>
        <livewire:incident-logs-table />
    </div>
</x-app-layout>