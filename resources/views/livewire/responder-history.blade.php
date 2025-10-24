<div wire:poll.10s>
    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center">
        <div class="relative flex-grow w-full md:w-auto">
            <input type="search" wire:model.live="search" autocomplete="off"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Search resolved incidents...">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
        <div>
            <select wire:model.live="dateFilter"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 mr-2">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="last24">Last 24 Hours</option>
                <option value="last7">Last 7 Days</option>
                <option value="last30">Last 30 Days</option>
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
                <tr class="text-center">
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Reporter Name</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Source</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Resolved At</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse($incidents as $incident)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150 text-center">
                    <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $incident->reporter_name ?? '-'
                        }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $incident->type ?? '-' }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $incident->source ?? '-' }}</td>
                    <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $incident->location ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $incident->incident_description ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">
                        {{ $incident->resolved_at ? \Carbon\Carbon::parse($incident->resolved_at)->format('H:i') :
                        '' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php $dispatchId = $incident->dispatch_id ?? null; @endphp
                        @if($dispatchId)
                        <a href="{{ url('/responder/incidents/' . $dispatchId . '?from=logs') }}"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        @else
                        <span class="text-xs text-gray-400">No link</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12"
                        class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-history text-gray-400 dark:text-gray-500 text-2xl mb-2"></i>
                            <p class="text-sm">No resolved incidents found.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $incidents->links() }}
    </div>
</div>