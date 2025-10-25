<div>
    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center">
        <div class="relative flex-grow w-full md:w-auto">
            <input type="search" wire:model.live="search" autocomplete="off"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Search incidents...">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
        <div>
            <select wire:model.live="typeFilter"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Types</option>
                @foreach($types as $type)
                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="perPage"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
    </div>
    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded-lg shadow">
        <table class="w-full table-auto">
            <thead style="background-color: #1C3A5B;" class="border-b border-gray-200 dark:border-gray-700">
                <tr class="text-left">
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('incident_id')">ID
                        @if($sortField === 'incident_id')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('type')">TYPE
                        @if($sortField === 'type')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider hidden sm:table-cell cursor-pointer"
                        wire:click="sortBy('location')">LOCATION
                        @if($sortField === 'location')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider hidden md:table-cell cursor-pointer"
                        wire:click="sortBy('reporter_name')">REPORTER
                        @if($sortField === 'reporter_name')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('status')">STATUS
                        @if($sortField === 'status')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider hidden lg:table-cell cursor-pointer"
                        wire:click="sortBy('department')">DEPARTMENT
                        @if($sortField === 'department')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('timestamp')">TIMESTAMP
                        @if($sortField === 'timestamp')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-4 py-3 text-xs font-medium text-white uppercase tracking-wider">RESOLVED AT</th>
                    <th class="px-3 py-3 text-xs font-medium text-white uppercase tracking-wider">ACTIONS</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse($logs as $incident)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150">
                    <td class="px-4 py-3 text-gray-900 dark:text-white font-medium text-sm">{{ $incident->short_id ??
                        '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if(($incident->type ?? '') === 'fire') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                            @elseif(($incident->type ?? '') === 'vehicle_crash' || ($incident->type ?? '') === 'vehicular_accident') bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200
                            @elseif(($incident->type ?? '') === 'medical_emergency') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                            @elseif(($incident->type ?? '') === 'disturbance') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                            @else bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $incident->type ?? '-')) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-sm hidden sm:table-cell">{{
                        $incident->location ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-sm hidden md:table-cell">{{
                        $incident->reporter_name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                            @if(($incident->status ?? '') === 'new') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                            @elseif(($incident->status ?? '') === 'dispatched') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                            @elseif(($incident->status ?? '') === 'resolved') bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200
                            @elseif(($incident->status ?? '') === 'en_route') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                            @elseif(($incident->status ?? '') === 'on_scene') bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200
                            @else bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200
                            @endif">
                            {{ ucfirst(str_replace('_', ' ', $incident->status ?? '-')) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-medium text-sm hidden lg:table-cell">{{
                        $incident->department ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                        <div class="max-w-[120px] truncate">
                            {{ $incident->timestamp ? \Carbon\Carbon::parse($incident->timestamp)->format('M d, Y') :
                            '-' }}
                        </div>
                        <div class="text-gray-400 dark:text-gray-500 text-xs">
                            {{ $incident->timestamp ? \Carbon\Carbon::parse($incident->timestamp)->format('H:i') : '' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                        <div class="max-w-[120px] truncate">
                            {{ $incident->resolved_at ? \Carbon\Carbon::parse($incident->resolved_at)->format('M d, Y')
                            : '-' }}
                        </div>
                        <div class="text-gray-400 dark:text-gray-500 text-xs">
                            {{ $incident->resolved_at ? \Carbon\Carbon::parse($incident->resolved_at)->format('H:i') :
                            '' }}
                        </div>
                    </td>
                    <td class="px-3 py-4">
                        <a href="{{ route('dispatch', ['incident_id' => $incident->incident_id, 'from' => 'logs']) }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 dark:bg-blue-900 text-white text-xs font-semibold hover:bg-blue-700 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 shadow-sm">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12"
                        class="px-4 py-8 text-center text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-gray-800">
                        <div class="flex flex-col items-center">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-300 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-sm">No resolved incidents found.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $logs->links() }}
    </div>

    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                // Scroll to top on page change
                if (window.scrollY > 200) window.scrollTo({top: 0, behavior: 'smooth'});
            });
        });
    </script>

    <div wire:poll.10s></div> <!-- Auto-refresh every 10 seconds -->
</div>