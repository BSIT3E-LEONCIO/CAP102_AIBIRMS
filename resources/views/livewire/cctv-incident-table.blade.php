<div>
    <!-- Report Controls -->
    <div class="flex items-center gap-3 mb-4"
        x-data="{ period: @entangle('period').live, allYears: @entangle('allYears').live, anchorDate: @entangle('anchorDate').live, yearSelection: @entangle('yearSelection').live }">
        <form method="GET" action="{{ route('incident-report.generate') }}" target="_blank"
            class="flex gap-2 items-center w-full justify-end">
            <select name="period" x-model="period" wire:model.live="period"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="day">Day</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
                <option value="year">Year</option>
            </select>
            <input type="date" name="date" x-model="anchorDate" wire:model.live="anchorDate"
                value="{{ now()->toDateString() }}" x-show="period === 'day' || period === 'week' || period === 'month'"
                :disabled="!(period === 'day' || period === 'week' || period === 'month')"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <template x-if="period === 'year'">
                <div class="ml-2">
                    <select name="yearSelection" wire:model.live="yearSelection" x-model="yearSelection"
                        class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">All Years</option>
                        @foreach(($years ?? []) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    <!-- Report hidden fields for year mode (render only the one we need) -->
                    <template x-if="yearSelection === 'all'">
                        <input type="hidden" name="allYears" value="1">
                    </template>
                    <template x-if="yearSelection !== 'all'">
                        <input type="hidden" name="date" :value="`${yearSelection}-01-01`">
                    </template>
                </div>
            </template>
            <input type="hidden" name="source" value="cctv">
            <input type="hidden" name="typeFilter" value="{{ $typeFilter }}">
            <input type="hidden" name="statusFilter" value="{{ $statusFilter }}">
            <input type="hidden" name="page" value="{{ request()->query('page', 1) }}">
            <input type="hidden" name="perPage" value="{{ $perPage }}">
            <button type="submit"
                class="inline-flex items-center px-3 py-2 bg-green-700 hover:bg-green-900 text-white text-xs font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-green-500 ml-2">
                <i class="fas fa-file-pdf text-white mr-1"></i> Generate Report
            </button>
        </form>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 items-center">
        <div class="relative flex-grow w-full md:w-auto">
            <input type="search" wire:model.live="search" autocomplete="off"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Search CCTV incidents...">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400 dark:text-gray-300" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
        @if(count($selectedIncidents) > 0)
        @if($showHidden)
        <button wire:click="unhideSelected"
            class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-green-500">
            <i class="fas fa-eye text-white mr-1"></i>
            Unhide
        </button>
        @else
        <button wire:click="hideSelected"
            class="inline-flex items-center px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-yellow-500">
            <i class="fas fa-eye-slash text-white mr-1"></i>
            Hide
        </button>
        @endif
        <button type="button"
            x-on:click="Swal.fire({title: 'Delete selected incidents?', text: 'This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete', cancelButtonText: 'Cancel', confirmButtonColor: '#b91c1c'}).then((result) => { if (result.isConfirmed) { $wire.deleteSelected(); } })"
            class="inline-flex items-center px-3 py-2 bg-red-700 hover:bg-red-800 text-white text-xs font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-red-600 ml-2">
            <i class="fas fa-trash text-white mr-1"></i>
            Delete
        </button>
        @else
        <button type="button" wire:click="toggleShowHidden"
            class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-blue-500">
            @if($showHidden)
            <i class="fas fa-eye-slash text-white mr-1"></i>
            Show Visible
            @else
            <i class="fas fa-eye text-white mr-1"></i>
            Show Hidden
            @endif
        </button>
        @endif
        <div class="flex items-center gap-2 mt-2 md:mt-0">
            <select wire:model.live="typeFilter"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Types</option>
                @foreach($types as $type)
                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter"
                class="pl-3 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="new">New</option>
                <option value="dispatched">Dispatched</option>
                <option value="resolved">Resolved</option>
            </select>
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
    </div>
    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded-lg shadow">
        <table class="w-full table-auto">
            <thead style="background-color: #1C3A5B;" class="border-b border-gray-200 dark:border-gray-700">
                <tr class="text-center">
                    <th class="px-2 py-4">
                        <input type="checkbox" wire:model="selectAll" class="form-checkbox h-4 w-4 text-blue-600">
                    </th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('location')">CAMERA NAME
                        @if($sortField === 'location')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('type')">TYPE
                        @if($sortField === 'type')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('status')">STATUS
                        @if($sortField === 'status')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">SCREENSHOT</th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider cursor-pointer"
                        wire:click="sortBy('timestamp')">TIMESTAMP
                        @if($sortField === 'timestamp')
                        <span class="ml-1">{!! $sortDirection === 'asc' ? '&#8593;' : '&#8595;' !!}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-xs font-medium text-white uppercase tracking-wider">ACTIONS</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @forelse($incidents as $incident)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-150 text-center">
                    <td class="px-2 py-4">
                        <input type="checkbox" wire:model="selectedIncidents" value="{{ $incident->id }}"
                            class="form-checkbox h-4 w-4 text-blue-600">
                    </td>
                    <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">{{ $incident->location ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ $incident->type ?? '-' }}</td>
                    <td class="px-6 py-4">
                        @php
                        $status = $incident->status ?? '-';
                        @endphp
                        @if($status === 'new')
                        <span
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-yellow-100 text-yellow-800">New</span>
                        @elseif($status === 'dispatched')
                        <span
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">Dispatched</span>
                        @elseif($status === 'resolved')
                        <span
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">Resolved</span>
                        @else
                        <span
                            class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-gray-200 text-gray-700">{{
                            ucfirst($status) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($incident->proof_image_url)
                        <div x-data="{ showModal: false }">
                            <img src="{{ $incident->proof_image_url }}" alt="Screenshot"
                                class="h-16 w-auto rounded shadow border border-gray-200 dark:border-gray-600 cursor-pointer"
                                @click="showModal = true" />
                            <!-- Modal -->
                            <div x-show="showModal" x-transition
                                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70"
                                @click.self="showModal = false">
                                <img src="{{ $incident->proof_image_url }}" alt="Screenshot Full Size"
                                    class="object-contain rounded-lg shadow-2xl"
                                    style="max-height: 90vh; max-width: 95vw;" />
                            </div>
                        </div>
                        @else
                        <span class="text-gray-400 dark:text-gray-500 text-sm">No image</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">
                        {{ $incident->timestamp ? \Carbon\Carbon::parse($incident->timestamp)->format('M d, Y H:i') :
                        '-' }}
                    </td>
                    <td class="px-6 py-4">
                        <a href="/dispatch?incident_id={{ urlencode($incident->firebase_id) }}"
                            class="inline-flex items-center px-3 py-2 rounded-md bg-blue-600 dark:bg-blue-700 text-white text-sm font-medium hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors duration-150">
                            <i class="fas fa-eye mr-1"></i>
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10"
                        class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-video text-gray-400 dark:text-gray-500 text-2xl mb-2"></i>
                            <p class="text-sm">No CCTV incidents found.</p>
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

    @once
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @endonce

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