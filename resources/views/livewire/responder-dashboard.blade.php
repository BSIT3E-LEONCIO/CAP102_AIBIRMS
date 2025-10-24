<div wire:poll.5s>
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
            <select wire:model.live="filterType"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Types</option>
                @foreach($typeOptions as $type)
                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="sourceFilter"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Sources</option>
                <option value="mobile">Mobile</option>
                <option value="cctv">CCTV</option>
            </select>
        </div>
        <div>
            <select wire:model.live="statusFilter"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="dispatched">Dispatched</option>
                <option value="en_route">En Route</option>
                <option value="resolved">Resolved</option>
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
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('firebase_id')">ID
                        @if($sortField === 'firebase_id')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('type')">TYPE
                        @if($sortField === 'type')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider">SEVERITY</th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider hidden sm:table-cell cursor-pointer"
                        wire:click="sortBy('location')">LOCATION
                        @if($sortField === 'location')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider hidden md:table-cell cursor-pointer"
                        wire:click="sortBy('reporter_name')">REPORTER
                        @if($sortField === 'reporter_name')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('status')">STATUS
                        @if($sortField === 'status')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider hidden lg:table-cell cursor-pointer"
                        wire:click="sortBy('department')">DEPARTMENT
                        @if($sortField === 'department')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('timestamp')">TIMESTAMP
                        @if($sortField === 'timestamp')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-3 py-4 text-xs font-semibold text-white uppercase tracking-wider">ACTIONS</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse($incidents as $item)
                @php $incident = $item['incident'] ?? []; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150 text-center">
                    <td class="px-3 py-4 text-gray-900 dark:text-white font-medium text-xs">{{ $incident['firebase_id']
                        ?? '-' }}</td>
                    <td class="px-3 py-4">
                        @php
                        $typeVal = $incident['type'] ?? ($incident['event'] ?? '-');
                        $typeClasses = match($typeVal) {
                        'fire' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                        'vehicle_crash', 'vehicular_accident' => 'bg-orange-100 dark:bg-orange-900 text-orange-800
                        dark:text-orange-200',
                        'medical_emergency' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                        'disturbance' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                        default => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200',
                        };
                        @endphp
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $typeClasses }}">
                            {{ ucfirst(str_replace('_', ' ', $typeVal)) }}
                        </span>
                    </td>
                    <td class="px-3 py-4">
                        @php
                        $sev = $incident['severity'] ?? '-';
                        $sevClasses = match($sev) {
                        'critical' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                        'high' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                        'medium' => 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200',
                        'low' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                        default => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200',
                        };
                        @endphp
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $sevClasses }}">
                            {{ ucfirst($sev) }}
                        </span>
                    </td>
                    <td class="px-3 py-4 text-gray-600 dark:text-gray-300 text-xs hidden sm:table-cell">{{
                        $incident['location'] ?? '-' }}</td>
                    <td class="px-3 py-4 text-gray-600 dark:text-gray-300 text-xs hidden md:table-cell">{{
                        $incident['reporter_name'] ?? 'CCTV' }}</td>
                    <td class="px-3 py-4">
                        @php
                        $st = $item['status'] ?? ($incident['status'] ?? '-');
                        $statusClasses = match($st) {
                        'new' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                        'dispatched' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                        'resolved' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200',
                        'en_route' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                        'on_scene' => 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200',
                        default => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200',
                        };
                        @endphp
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $statusClasses }}">
                            {{ ucfirst(str_replace('_', ' ', $st)) }}
                        </span>
                    </td>
                    <td class="px-3 py-4 text-gray-600 dark:text-gray-300 font-medium text-xs hidden lg:table-cell">{{
                        $incident['department'] ?? '-' }}</td>
                    <td class="px-3 py-4 text-gray-500 dark:text-gray-400 text-xs">
                        @php
                        $tsRaw = $incident['datetime'] ?? ($incident['date_time'] ?? ($incident['timestamp'] ?? null));
                        try { $dt = $tsRaw ? \Carbon\Carbon::parse($tsRaw) : null; } catch (\Throwable $e) { $dt = null;
                        }
                        @endphp
                        <div class="max-w-[120px] truncate">
                            {{ $dt ? $dt->format('M d, Y') : '-' }}
                        </div>
                        <div class="text-gray-400 dark:text-gray-500 text-xs">
                            {{ $dt ? $dt->format('H:i') : '' }}
                        </div>
                    </td>
                    <td class="px-3 py-4">
                        <a href="{{ route('responder.incident-details', $item['dispatch_id']) }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 dark:bg-blue-900 text-white text-xs font-semibold hover:bg-blue-700 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 shadow-sm">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9"
                        class="px-3 py-8 text-center text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-gray-800">
                        <div class="flex flex-col items-center">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-300 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-sm">No incidents found.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <!-- Keep our own pagination controls since records are aggregated, but show a simple summary -->
        <div class="flex justify-between items-center mt-2">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ $total === 0 ? 0 : (($page - 1) * $perPage + 1) }}</span>
                to
                <span class="font-medium">{{ min($page * $perPage, $total) }}</span>
                of
                <span class="font-medium">{{ $total }}</span>
                incidents
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="prevPage" @if(!$hasPrev) disabled @endif
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Prev</button>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Page {{ $page }}</span>
                <button wire:click="nextPage" @if(!$hasNext) disabled @endif
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                if (window.scrollY > 200) window.scrollTo({top: 0, behavior: 'smooth'});
            });
        });
    </script>

    <!-- wire:poll moved to main container above for auto-refresh -->
</div>