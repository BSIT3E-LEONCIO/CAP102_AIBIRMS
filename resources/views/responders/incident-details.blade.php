<div class="max-w-5xl mx-auto p-6 space-y-6" wire:poll.5s="pollUpdates">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">Incident Details</h2>
                <p class="text-blue-100">ID: {{ $incident['firebase_id'] ?? $incident['id'] ?? 'N/A' }}</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-blue-100 mb-1">Last Updated</div>
                <div class="text-lg font-semibold">{{ now()->format('M d, Y H:i') }}</div>
            </div>
        </div>
    </div>

    <!-- Main Details Card -->
    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Incident Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div class="space-y-1">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Reporter
                        Name</div>
                    <div class="text-gray-900 dark:text-white font-medium">{{ $incident['reporter_name'] ?? 'N/A' }}
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type</div>
                    <div class="flex items-center">
                        @php
                        $typeVal = $incident['type'] ?? $incident['event'] ?? 'N/A';
                        $typeClasses = match($typeVal) {
                        'fire' => 'bg-red-100 text-red-800 border-red-200',
                        'vehicle_crash', 'vehicular_accident' => 'bg-orange-100 text-orange-800 border-orange-200',
                        'medical_emergency' => 'bg-green-100 text-green-800 border-green-200',
                        'disturbance' => 'bg-blue-100 text-blue-800 border-blue-200',
                        default => 'bg-gray-100 text-gray-800 border-gray-200',
                        };
                        @endphp
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium border {{ $typeClasses }}">
                            {{ ucfirst(str_replace('_', ' ', $typeVal)) }}
                        </span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border
                            @if($status === 'resolved') bg-green-100 text-green-800 border-green-200
                            @elseif($status === 'en_route') bg-yellow-100 text-yellow-800 border-yellow-200
                            @else bg-blue-100 text-blue-800 border-blue-200
                            @endif">
                            {{ $statusOptions[$status] ?? ucfirst($status) }}
                        </span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Source
                    </div>
                    <div class="text-gray-900 dark:text-white font-medium">{{ $incident['source'] ?? 'N/A' }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Location
                    </div>
                    <div class="text-gray-900 dark:text-white font-medium">{{ $incident['location'] ??
                        $incident['camera_name'] ?? 'N/A' }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Date &
                        Time</div>
                    <div class="text-gray-900 dark:text-white font-medium">{{ $incident['datetime'] ??
                        $incident['date_time'] ?? $incident['timestamp'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                    Description</div>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-gray-900 dark:text-white">
                    {{ $incident['incident_description'] ?? $incident['screenshot'] ?? 'No description available.' }}
                </div>
            </div>

            <!-- Status Messages (success only). Read-only errors suppressed by design. -->
            @if (session()->has('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-green-800">{{ session('status') }}</p>
                </div>
            </div>
            @endif

            <!-- Update Status Button -->
            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                <div class="flex items-center gap-3">
                    <select wire:model="selectedStatus" @if($readOnly) disabled @endif class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent
                        @if($readOnly)
                            opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400
                        @else
                            bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white
                        @endif">
                        @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="button" @if($readOnly) disabled @endif class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 shadow-sm
                        @if($readOnly)
                            bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300 cursor-not-allowed opacity-70
                        @else
                            bg-blue-600 hover:bg-blue-700 text-white
                        @endif" x-data="{}" @if(!$readOnly) x-on:click.prevent="Swal.fire({
                            title: 'Update Status?',
                            text: 'Are you sure you want to update the status?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#2563eb',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, update!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => { if (result.isConfirmed) { $wire.updateStatus(); } })" @endif>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Thin scrollbar utility for vertical lists -->
    <style>
        /* Thin vertical scrollbar for modern browsers */
        .scroll-thin-y {
            scrollbar-width: thin;
            scrollbar-color: rgba(100, 116, 139, .6) transparent;
        }

        .scroll-thin-y::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .scroll-thin-y::-webkit-scrollbar-track {
            background: transparent;
        }

        .scroll-thin-y::-webkit-scrollbar-thumb {
            background-color: rgba(100, 116, 139, .6);
            border-radius: 9999px;
        }

        .dark .scroll-thin-y {
            scrollbar-color: rgba(148, 163, 184, .6) transparent;
        }

        .dark .scroll-thin-y::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, .6);
        }
    </style>

    <!-- Notes & Timeline Side by Side -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Internal Notes -->
        <div
            class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Internal Notes</h3>
                </div>
            </div>
            <div class="p-6 flex flex-col">
                <div class="space-y-3 overflow-y-auto overflow-x-hidden mb-4 scroll-thin-y" style="max-height: 18rem;">
                    @forelse($incidentNotes as $note)
                    <div
                        class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border-l-4 border-blue-500 relative">
                        <!-- Top row: Avatar, Name, and Role badges in top-right -->
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                                    {{ substr($note['user_name'], 0, 1) }}
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $note['user_name']
                                    }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                @if(!empty($note['user_role']))
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold border
                                    @if($note['user_role'] === 'admin') bg-red-100 text-red-800 border-red-300 dark:bg-red-900 dark:text-red-200 dark:border-red-700
                                    @elseif($note['user_role'] === 'responder') bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200 dark:border-green-700
                                    @else bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700 @endif">
                                    @if($note['user_role'] === 'admin') ADMIN
                                    @elseif($note['user_role'] === 'responder') RESPONDER
                                    @else {{ substr(ucfirst($note['user_role']), 0, 1) }}
                                    @endif
                                </span>
                                @endif
                                @if(!empty($note['user_role']) && strtolower($note['user_role']) === 'responder' &&
                                !empty($note['responder_type']))
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-900 dark:text-emerald-200 dark:border-emerald-700">
                                    {{ $note['responder_type'] }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <!-- Note text -->
                        <div class="text-gray-700 dark:text-gray-300 leading-relaxed pl-10 pr-16 mb-2">{{ $note['note']
                            }}</div>
                        <!-- Bottom-right timestamp -->
                        <div class="absolute bottom-3 right-4">
                            <span
                                class="text-xs text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-600 px-2 py-0.5 rounded-full">
                                @php
                                $createdAt = is_string($note['created_at']) ? \Carbon\Carbon::parse($note['created_at'])
                                : $note['created_at'];
                                $seconds = $createdAt->diffInSeconds(now());
                                if ($seconds < 60) { echo max(1, round($seconds)) . 's' ; } elseif ($seconds < 3600) {
                                    echo round($seconds / 60) . 'm' ; } elseif ($seconds < 86400) { echo round($seconds
                                    / 3600) . 'h' ; } else { echo round($seconds / 86400) . 'd' ; } @endphp </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                            </path>
                        </svg>
                        <div class="text-gray-500 dark:text-gray-400">No notes yet.</div>
                    </div>
                    @endforelse
                </div>
                <form wire:submit.prevent="addNote" class="flex gap-3">
                    <input type="text" wire:model.defer="newNote" @if($readOnly) disabled @endif class="flex-1 border rounded-lg px-4 py-2.5 placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent
                        @if($readOnly)
                            bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed opacity-70
                        @else
                            bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white
                        @endif" placeholder="Add a note...">
                    <button type="submit" @if($readOnly) disabled @endif class="px-6 py-2.5 rounded-lg font-medium transition-colors duration-200 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                        @if($readOnly)
                            bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300 cursor-not-allowed opacity-70
                        @else
                            bg-blue-600 hover:bg-blue-700 text-white
                        @endif">
                        Add Note
                    </button>
                </form>
            </div>
        </div>
        <!-- Timeline -->
        <div
            class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Timeline</h3>
                </div>
            </div>
            <div class="p-6 flex flex-col">
                <div class="space-y-4 overflow-y-auto overflow-x-hidden scroll-thin-y" style="max-height: 18rem;">
                    @forelse($timeline as $item)
                    <div class="relative flex items-start gap-4">
                        <div class="flex-shrink-0 w-3 h-3 bg-blue-500 rounded-full mt-2"></div>
                        <div class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $item['user_name']
                                    }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['created_at'] }}</span>
                            </div>
                            <div class="text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ $item['action'] }}:</span> {{ $item['details'] }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-gray-500 dark:text-gray-400">No timeline entries yet.</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Location & Map Section (Mobile only: no camera_name) -->
    @if(!isset($incident['camera_name']))
    <!-- Evidence & Attachments Section (Mobile only) -->
    @if(!empty($incident['proof_image_url']) || (!isset($incident['camera_name']) &&
    !empty($incident['proofImageUrl'])))
    <div x-data="{ showModal: false }"
        class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        <div class="bg-gradient-to-r from-yellow-500 to-orange-600 px-6 py-4">
            <div class="flex items-center gap-2 text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                    </path>
                </svg>
                <h3 class="text-xl font-semibold">Evidence & Attachments</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label
                    class="block text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Photo
                    Evidence</label>
                <div
                    class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 flex justify-center">
                    <img src="{{ $incident['proof_image_url'] ?? $incident['proofImageUrl'] }}" alt="Photo Evidence"
                        class="cursor-pointer object-cover rounded-lg shadow-lg"
                        style="width: 100%; max-width: 600px; height: 400px;" @click="showModal = true">
                </div>
                <!-- Modal -->
                <div x-show="showModal" x-transition
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70"
                    @click.self="showModal = false">
                    <img src="{{ $incident['proof_image_url'] ?? $incident['proofImageUrl'] }}"
                        alt="Photo Evidence Full Size" class="object-contain rounded-lg shadow-2xl"
                        style="max-height: 90vh; max-width: 95vw;">
                </div>
            </div>
        </div>
    </div>
    @endif
    <div
        class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
            <div class="flex items-center gap-2 text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <h3 class="text-xl font-semibold">Incident Location</h3>
            </div>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label
                    class="block text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Address</label>
                <div
                    class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                        </svg>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $incident['location'] ?? 'N/A'
                            }}</span>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-2">
                <div id="map" wire:ignore style="width: 100%; height: 400px; border-radius: 0.5rem; overflow: hidden;">
                </div>
            </div>
        </div>
    </div>
    <!-- Mapbox CSS & JS -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js"></script>
    <style>
        /* Custom Map Style Switcher Control */
        .mapboxgl-ctrl-style-switcher {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, .1);
        }

        .mapboxgl-ctrl-style-switcher button {
            width: 29px;
            height: 29px;
            display: block;
            padding: 0;
            outline: none;
            border: 0;
            box-sizing: border-box;
            background-color: transparent;
            color: #111827;
            /* ensure icon visible in light mode */
            cursor: pointer;
            position: relative;
        }

        .mapboxgl-ctrl-style-switcher button:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .mapboxgl-ctrl-style-switcher button svg {
            width: 20px;
            height: 20px;
            display: block;
            margin: auto;
        }

        .mapboxgl-ctrl-style-switcher .style-dropdown {
            position: absolute;
            top: 0;
            right: 35px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, .1);
            display: none;
            min-width: 180px;
            z-index: 1;
        }

        .mapboxgl-ctrl-style-switcher .style-dropdown.active {
            display: block;
        }

        .mapboxgl-ctrl-style-switcher .style-option {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            border-bottom: 1px solid #f0f0f0;
            white-space: nowrap;
        }

        .mapboxgl-ctrl-style-switcher .style-option:last-child {
            border-bottom: none;
        }

        .mapboxgl-ctrl-style-switcher .style-option:hover {
            background-color: #f0f0f0;
        }

        .mapboxgl-ctrl-style-switcher .style-option.active {
            background-color: #e8f4f8;
            font-weight: 600;
        }

        /* Dark mode support (Tailwind uses `.dark` class) */
        .dark .mapboxgl-ctrl-style-switcher {
            background-color: #111827;
            /* gray-900 */
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.08);
        }

        .dark .mapboxgl-ctrl-style-switcher button {
            color: #e5e7eb;
            /* icon color in dark */
        }

        .dark .mapboxgl-ctrl-style-switcher button:hover {
            background-color: rgba(255, 255, 255, 0.06);
        }

        .dark .mapboxgl-ctrl-style-switcher .style-dropdown {
            background: #111827;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.08);
        }

        .dark .mapboxgl-ctrl-style-switcher .style-option {
            color: #e5e7eb;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .dark .mapboxgl-ctrl-style-switcher .style-option:hover {
            background-color: rgba(255, 255, 255, 0.06);
        }

        .dark .mapboxgl-ctrl-style-switcher .style-option.active {
            background-color: rgba(59, 130, 246, 0.18);
            color: #ffffff;
        }
    </style>
    <script>
        (function () {
            if (window.__initResponderMapsBound) return; // prevent double-binding
            window.__initResponderMapsBound = true;

            function add3DBuildings(map) {
                map.on('load', function () {
                    const layers = map.getStyle().layers || [];
                    let labelLayerId;
                    for (let i = 0; i < layers.length; i++) {
                        if (layers[i].type === 'symbol' && layers[i].layout && layers[i].layout['text-field']) {
                            labelLayerId = layers[i].id;
                            break;
                        }
                    }
                    try {
                        map.addLayer({
                            id: '3d-buildings',
                            source: 'composite',
                            'source-layer': 'building',
                            filter: ['==', 'extrude', 'true'],
                            type: 'fill-extrusion',
                            minzoom: 15,
                            paint: {
                                'fill-extrusion-color': '#aaa',
                                'fill-extrusion-height': ['get', 'height'],
                                'fill-extrusion-base': ['get', 'min_height'],
                                'fill-extrusion-opacity': 0.6
                            }
                        }, labelLayerId);
                    } catch (e) {
                        // layer may already exist; ignore
                    }
                });
            }

            function destroyExistingMap(containerEl) {
                if (!containerEl) return;
                if (containerEl._mapInstance && typeof containerEl._mapInstance.remove === 'function') {
                    try { containerEl._mapInstance.remove(); } catch (e) {}
                    containerEl._mapInstance = null;
                }
                // Clear any leftover nodes
                while (containerEl.firstChild) containerEl.removeChild(containerEl.firstChild);
            }

            // Custom Style Switcher Control
            class StyleSwitcherControl {
                constructor(styles) {
                    this._styles = styles;
                    this._currentStyle = styles[0].url;
                }

                onAdd(map) {
                    this._map = map;
                    this._container = document.createElement('div');
                    this._container.className = 'mapboxgl-ctrl mapboxgl-ctrl-group mapboxgl-ctrl-style-switcher';

                    // Create button
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/></svg>';
                    button.title = 'Change map style';

                    // Create dropdown
                    const dropdown = document.createElement('div');
                    dropdown.className = 'style-dropdown';

                    this._styles.forEach(style => {
                        const option = document.createElement('div');
                        option.className = 'style-option';
                        option.textContent = style.name;
                        option.dataset.styleUrl = style.url;
                        
                        if (style.url === this._currentStyle) {
                            option.classList.add('active');
                        }

                        option.addEventListener('click', () => {
                            // Update active state
                            dropdown.querySelectorAll('.style-option').forEach(opt => opt.classList.remove('active'));
                            option.classList.add('active');
                            
                            // Change map style
                            this._currentStyle = style.url;
                            this._map.setStyle(style.url);
                            
                            // Close dropdown
                            dropdown.classList.remove('active');
                        });

                        dropdown.appendChild(option);
                    });

                    // Toggle dropdown on button click
                    button.addEventListener('click', (e) => {
                        e.stopPropagation();
                        dropdown.classList.toggle('active');
                    });

                    // Close dropdown when clicking outside
                    document.addEventListener('click', () => {
                        dropdown.classList.remove('active');
                    });

                    this._container.appendChild(button);
                    this._container.appendChild(dropdown);

                    return this._container;
                }

                onRemove() {
                    this._container.parentNode.removeChild(this._container);
                    this._map = undefined;
                }
            }

            window.initResponderMaps = function () {
                if (!window.mapboxgl) return;
                mapboxgl.accessToken = @json(config('services.mapbox.token'));

                const mapStyles = [
                    { name: 'Streets', url: 'mapbox://styles/mapbox/streets-v12' },
                    { name: 'Satellite Streets', url: 'mapbox://styles/mapbox/satellite-streets-v12' },
                    { name: 'Navigation Day', url: 'mapbox://styles/mapbox/navigation-day-v1' },
                    { name: 'Navigation Night', url: 'mapbox://styles/mapbox/navigation-night-v1' }
                ];

                // Initialize Mobile map if present
                var mapEl = document.getElementById('map');
                if (mapEl) {
                    destroyExistingMap(mapEl);
                    var map = new mapboxgl.Map({
                        container: 'map',
                        style: mapStyles[0].url,
                        center: [120.9532, 14.6562],
                        zoom: 13,
                        pitch: 45,
                        bearing: -17.6
                    });
                    mapEl._mapInstance = map;
                    map.addControl(new mapboxgl.NavigationControl());
                    map.addControl(new mapboxgl.FullscreenControl());
                    map.addControl(new mapboxgl.GeolocateControl({
                        positionOptions: { enableHighAccuracy: true },
                        trackUserLocation: true,
                        showUserHeading: true
                    }));
                    map.addControl(new StyleSwitcherControl(mapStyles), 'top-right');
                    add3DBuildings(map);
                    // Ensure proper sizing after DOM updates
                    setTimeout(() => { try { map.resize(); } catch (e) {} }, 60);

                    // Dynamic marker/geocode logic
                    const address = @json($incident['location'] ?? '');
                    const lat = parseFloat(@json($incident['latitude'] ?? ''));
                    const lng = parseFloat(@json($incident['longitude'] ?? ''));
                    if (!isNaN(lat) && !isNaN(lng)) {
                        map.setCenter([lng, lat]);
                        new mapboxgl.Marker({ color: 'red' }).setLngLat([lng, lat]).addTo(map);
                    } else if (address && window.mapboxSdk) {
                        const mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });
                        mapboxClient.geocoding
                            .forwardGeocode({ query: address, limit: 1 })
                            .send()
                            .then(function (response) {
                                const feature = response?.body?.features?.[0];
                                if (feature) {
                                    map.setCenter(feature.center);
                                    new mapboxgl.Marker({ color: 'red' }).setLngLat(feature.center).addTo(map);
                                }
                            })
                            .catch(() => {});
                    }
                }

                // Initialize CCTV map if present
                var cctvEl = document.getElementById('cctv-map');
                if (cctvEl) {
                    destroyExistingMap(cctvEl);
                    var cctvMap = new mapboxgl.Map({
                        container: 'cctv-map',
                        style: mapStyles[0].url,
                        center: [120.9532, 14.6562],
                        zoom: 13,
                        pitch: 45,
                        bearing: -17.6
                    });
                    cctvEl._mapInstance = cctvMap;
                    cctvMap.addControl(new mapboxgl.NavigationControl());
                    cctvMap.addControl(new mapboxgl.FullscreenControl());
                    cctvMap.addControl(new mapboxgl.GeolocateControl({
                        positionOptions: { enableHighAccuracy: true },
                        trackUserLocation: true,
                        showUserHeading: true
                    }));
                    cctvMap.addControl(new StyleSwitcherControl(mapStyles), 'top-right');
                    add3DBuildings(cctvMap);
                    // Ensure proper sizing after DOM updates
                    setTimeout(() => { try { cctvMap.resize(); } catch (e) {} }, 60);

                    const cameraName = @json($incident['camera_name'] ?? '');
                    const clat = parseFloat(@json($incident['latitude'] ?? ''));
                    const clng = parseFloat(@json($incident['longitude'] ?? ''));
                    if (!isNaN(clat) && !isNaN(clng)) {
                        cctvMap.setCenter([clng, clat]);
                        new mapboxgl.Marker({ color: 'red' }).setLngLat([clng, clat]).addTo(cctvMap);
                    } else if (cameraName && window.mapboxSdk) {
                        const mapboxClient = mapboxSdk({ accessToken: mapboxgl.accessToken });
                        mapboxClient.geocoding
                            .forwardGeocode({ query: cameraName, limit: 1 })
                            .send()
                            .then(function (response) {
                                const feature = response?.body?.features?.[0];
                                if (feature) {
                                    cctvMap.setCenter(feature.center);
                                    new mapboxgl.Marker({ color: 'red' }).setLngLat(feature.center).addTo(cctvMap);
                                }
                            })
                            .catch(() => {});
                    }
                }
            };

            // Bind to initial load and Livewire lifecycle events
            document.addEventListener('DOMContentLoaded', window.initResponderMaps);
            document.addEventListener('livewire:load', window.initResponderMaps);
            document.addEventListener('livewire:update', window.initResponderMaps);
            document.addEventListener('livewire:navigated', window.initResponderMaps);
            // Livewire v2 compatibility: re-run after each message processed
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                try {
                    window.Livewire.hook('message.processed', () => {
                        window.initResponderMaps();
                    });
                } catch (e) {}
            }
        })();
    </script>
</div>
@endif

<!-- Camera Location & Map Section (CCTV only) -->
@if(isset($incident['camera_name']))
<div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-4">
        <div class="flex items-center gap-2 text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                </path>
            </svg>
            <h3 class="text-xl font-semibold">Camera Location</h3>
        </div>
    </div>
    <div class="p-6">
        <div class="mb-4">
            <label
                class="block text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Camera
                Name</label>
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="text-gray-900 dark:text-white font-medium">{{ $incident['camera_name'] ?? 'N/A'
                        }}</span>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-2">
            <div id="cctv-map" wire:ignore style="width: 100%; height: 400px; border-radius: 0.5rem; overflow: hidden;">
            </div>
        </div>
    </div>
</div>
<!-- Mapbox CSS & JS -->
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script src="https://unpkg.com/@mapbox/mapbox-sdk/umd/mapbox-sdk.min.js"></script>
<script>
    // The actual initialization is handled by window.initResponderMaps,
    // which is bound once in the earlier script block.
    if (window.initResponderMaps) {
        // If the CCTV section renders without the mobile section, ensure init is bound and run once
        document.addEventListener('DOMContentLoaded', window.initResponderMaps);
    }
</script>
</div>
@endif
</div>