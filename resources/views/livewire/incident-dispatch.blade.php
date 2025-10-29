<div wire:poll.5s="pollUpdates" class="space-y-6">
    <!-- Success/Error Messages -->
    @if($successMessage)
    <div
        class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900 dark:to-emerald-900 border border-green-200 dark:border-green-700 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-green-800 dark:text-green-200 font-medium">{{ $successMessage }}</p>
        </div>
    </div>
    @endif
    @if($errorMessage)
    <div
        class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900 dark:to-pink-900 border border-red-200 dark:border-red-700 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-red-800 dark:text-red-200 font-medium">{{ $errorMessage }}</p>
        </div>
    </div>
    @endif
    <!-- Assign Responders Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden w-full">
        <div class="bg-blue-900 px-6 py-3">
            <div class="flex items-center gap-3 text-white">
                <i class="fas fa-user-plus"></i>
                <h3 class="text-md font-bold">Assign Responders</h3>
            </div>
        </div>
        <div class="p-4 space-y-3">
            <!-- Main Responder Dropdown -->
            <div>
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-white mb-3">
                    <i class="fas fa-user-tag"></i>
                    Lead Responder
                </label>
                <select wire:model="mainResponder"
                    class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm text-sm">
                    <option value="">Choose Lead Responder</option>
                    @foreach($this->allResponders as $responder)
                    <option value="{{ $responder->id }}">{{ $responder->name }} ({{ $responder->responder_type ?? 'N/A'
                        }})</option>
                    @endforeach
                </select>
            </div>
            <!-- Additional Responders -->
            @if(count($additionalResponders) > 0)
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <i class="fas fa-user-plus"></i>
                    Additional Responders
                </label>
                @foreach($additionalResponders as $index => $responderId)
                <div class="flex items-center gap-3">
                    <select wire:model="additionalResponders.{{ $index }}"
                        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm text-sm">
                        <option value="">Select Additional Responder</option>
                        @foreach($this->allResponders as $responder)
                        <option value="{{ $responder->id }}">{{ $responder->name }} ({{ $responder->responder_type ??
                            'N/A' }})</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="removeResponder({{ $index }})"
                        class="flex items-center justify-center text-black rounded-lg text-sm shadow-sm"
                        title="Remove Responder">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Add Another Responder & Dispatch Button Row -->
            <div class="flex flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-600 w-full">
                <button type="button" wire:click="addResponder"
                    class="w-1/2 min-w-0 bg-white dark:bg-gray-800 text-green-600 dark:text-white px-3 py-2 rounded-2xl font-semibold border-2 border-dashed border-green-300 dark:hover:from-green-800 text-center h-14 text-xs break-words overflow-hidden"
                    @if($readOnly) disabled style="opacity:0.5;cursor:not-allowed;" @endif>
                    <span class="flex items-center justify-center w-full gap-1">
                        <i class="fas fa-plus"></i>
                        <span class="break-words">Add Another Responder</span>
                    </span>
                </button>
                <button type="button"
                    class="w-1/2 bg-blue-900 text-white px-3 rounded-2xl font-bold shadow-lg hover:shadow-xl flex items-center justify-center gap-2 h-14 text-xs whitespace-nowrap"
                    @if($readOnly) disabled style="opacity:0.5;cursor:not-allowed;" @endif x-data="{}" @if(!$readOnly)
                    x-on:click.prevent="Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to dispatch this incident?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, dispatch it!'
                    }).then((result) => { if (result.isConfirmed) { $wire.dispatchIncident(); } })" @endif>
                    <i class="fas fa-paper-plane"></i>
                    Dispatch Incident
                </button>
            </div>
        </div>
    </div>

    <!-- Status Management Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="bg-blue-900 px-6 py-3">
            <div class="flex items-center gap-3 text-white">
                <i class="fas fa-info-circle"></i></i>
                <h3 class="text-md font-bold">Incident Status</h3>
            </div>
        </div>
        <div class="p-4 space-y-3">
            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
                        <i class="fas fa-flag"></i>
                        Current Status:
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold shadow-sm transition-all duration-200 whitespace-nowrap max-w-[160px]
                        @if($status === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 border border-blue-300 dark:border-blue-700
                        @elseif($status === 'dispatched') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 border border-yellow-300 dark:border-yellow-700
                        @elseif($status === 'resolved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 border border-green-300 dark:border-green-700
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 border border-gray-300 dark:border-gray-700 @endif">
                        <i class="fas fa-circle mr-2 text-xs
                            @if($status === 'new') text-blue-500
                            @elseif($status === 'dispatched') text-yellow-500
                            @elseif($status === 'resolved') text-green-500
                            @else text-gray-500 @endif"></i>
                        {{ ucfirst($status) }}
                    </span>
                </div>
            </div>
            <form wire:submit.prevent="updateStatus" class="space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Update
                        Status</label>
                    <select wire:model="status"
                        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm text-sm">
                        @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button"
                    class="w-full bg-blue-900 text-white py-3 px-4 rounded-2xl font-bold shadow-lg hover:shadow-xl flex items-center justify-center gap-2 h-12 text-xs whitespace-nowrap"
                    @if($readOnly) disabled style="opacity:0.5;cursor:not-allowed;" @endif x-data="{}" @if(!$readOnly)
                    x-on:click.prevent="Swal.fire({
                        title: 'Update Status?',
                        text: 'Are you sure you want to update the status?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2563eb',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, update!'
                    }).then((result) => { if (result.isConfirmed) { $wire.updateStatus(); } })" @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    Update Status
                </button>
            </form>
        </div>
    </div>

    <!-- Internal Notes Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="bg-blue-900 px-6 py-3">
            <div class="flex items-center gap-3 text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
                <h3 class="text-md font-bold">Internal Notes</h3>
            </div>
        </div>
        <div class="p-4 space-y-3">
            <form wire:submit.prevent="addNote" class="mb-3">
                <div class="space-y-3">
                    <textarea wire:model.defer="newNote" rows="3"
                        placeholder="Add an internal note about this incident..."
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none shadow-sm text-sm transition-all duration-200 placeholder-gray-500"
                        x-data="{}"
                        x-on:keydown.enter="if (!event.shiftKey) { event.preventDefault(); $el.closest('form').dispatchEvent(new Event('submit', { cancelable: true })); }">
                    </textarea>
                    <button type="submit"
                        class="w-full bg-blue-900 text-white py-3 px-4 rounded-2xl font-bold shadow-lg hover:shadow-xl flex items-center justify-center gap-2 h-12 text-xs whitespace-nowrap"
                        @if($readOnly) disabled style="opacity:0.5;cursor:not-allowed;" @endif>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Note
                    </button>
                </div>
            </form>
            <!-- SweetAlert2 CDN -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <div style="height: 220px; overflow-y: auto;" class="space-y-3 custom-scrollbar">
                @forelse($incidentNotes as $note)
                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 shadow-sm relative">
                    <!-- Top row: Avatar, Name, and Role badges in top-right -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-8 h-8 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                {{ substr($note->user->name ?? 'U', 0, 1) }}
                            </div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ $note->user->name ??
                                'Unknown' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            @if(isset($note->user) && !empty($note->user->role))
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold border
                                    @if($note->user->role === 'admin') bg-red-100 text-red-800 border-red-300 dark:bg-red-900 dark:text-red-200 dark:border-red-700
                                    @elseif($note->user->role === 'responder') bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200 dark:border-green-700
                                    @else bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700 @endif">
                                @if($note->user->role === 'admin') A
                                @elseif($note->user->role === 'responder') R
                                @else {{ substr(ucfirst($note->user->role), 0, 1) }}
                                @endif
                            </span>
                            @endif
                            @if(isset($note->user) && strtolower($note->user->role ?? '') === 'responder' &&
                            !empty($note->user->responder_type))
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-900 dark:text-emerald-200 dark:border-emerald-700">
                                {{ $note->user->responder_type }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <!-- Note text -->
                    <div class="text-gray-700 dark:text-gray-200 text-sm leading-relaxed pl-10 pr-16 mb-2">{{
                        $note->note }}</div>
                    <!-- Bottom-right timestamp -->
                    <div class="absolute bottom-3 right-4">
                        <span
                            class="text-xs text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-600 px-2 py-0.5 rounded-full">
                            @php
                            $seconds = $note->created_at->diffInSeconds(now());
                            if ($seconds < 60) { echo max(1, round($seconds)) . 's' ; } elseif ($seconds < 3600) { echo
                                round($seconds / 60) . 'm' ; } elseif ($seconds < 86400) { echo round($seconds / 3600)
                                . 'h' ; } else { echo round($seconds / 86400) . 'd' ; } @endphp </span>
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
                    <div class="text-gray-500 dark:text-gray-400 font-medium">No notes yet</div>
                    <div class="text-gray-400 dark:text-gray-500 text-sm">Add the first internal note about this
                        incident</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Timeline / Activity Log Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="bg-blue-900 px-6 py-3">
            <div class="flex items-center gap-3 text-white">
                <i class="fas fa-stream"></i>
                <h3 class="text-md font-bold">Incident Timeline</h3>
            </div>
        </div>
        <div class="p-4">
            <div style="height: 220px; overflow-y: auto;" class="space-y-4 custom-scrollbar">
                @forelse($timeline as $entry)
                <div class="relative flex items-start gap-4">
                    <!-- Timeline dot -->
                    <div class="flex-shrink-0 w-3 h-3 rounded-full mt-2
                        @if($entry->action === 'status_changed') bg-blue-500
                        @elseif($entry->action === 'responder_status_changed') bg-green-500
                        @elseif($entry->action === 'note_added') bg-purple-500
                        @else bg-gray-500 @endif">
                    </div>
                    <!-- Timeline line -->
                    @if(!$loop->last)
                    <div class="absolute left-1.5 top-5 w-px h-full bg-gray-200 dark:bg-gray-600"></div>
                    @endif
                    <!-- Timeline content -->
                    <div
                        class="flex-1 bg-gradient-to-r from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white
                                    @if($entry->action === 'status_changed') bg-blue-500
                                    @elseif($entry->action === 'responder_status_changed') bg-green-500
                                    @elseif($entry->action === 'note_added') bg-purple-500
                                    @else bg-gray-500 @endif">
                                    {{ substr($entry->user->name ?? 'S', 0, 1) }}
                                </div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">{{ $entry->user->name
                                    ?? 'System' }}</span>
                            </div>
                            <span
                                class="text-xs text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded-full">
                                {{ $entry->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <div class="text-gray-700 dark:text-gray-200 text-sm">
                            @if($entry->action === 'status_changed')
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                <span>Status changed to <span class="font-semibold text-blue-600 dark:text-blue-400">{{
                                        ucfirst($entry->details) }}</span></span>
                            </div>
                            @elseif($entry->action === 'responder_status_changed')
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Responder status changed to <span
                                        class="font-semibold text-green-600 dark:text-green-400">{{
                                        ucfirst($entry->details) }}</span></span>
                            </div>
                            @elseif($entry->action === 'note_added')
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                <span>Added an internal note</span>
                            </div>
                            @else
                            <span>{{ ucfirst($entry->action) }}</span>
                            @endif
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
                    <div class="text-gray-500 dark:text-gray-400 font-medium">No timeline entries yet</div>
                    <div class="text-gray-400 dark:text-gray-500 text-sm">Activity will appear here as the incident
                        progresses</div>
                </div>
                @endforelse
            </div>
        </div>

    </div>
    <!-- Delete Incident Button -->

    <div class="flex justify-center">
        <div class="w-full max-w-xl">
            <button type="button"
                class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-2xl font-bold shadow-lg flex items-center justify-center gap-2"
                @if($readOnly) disabled style="opacity:0.5;cursor:not-allowed;" @endif x-data="{}" @if(!$readOnly)
                x-on:click.prevent="Swal.fire({
                            title: 'Delete Incident?',
                            text: 'Are you sure you want to permanently delete this incident from both MySQL and Firebase?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#2563eb',
                            confirmButtonText: 'Yes, delete it!'
                        }).then((result) => { if (result.isConfirmed) { $wire.deleteIncident(); } })" @endif>
                <i class="fas fa-trash"></i>
                Delete Incident
            </button>
        </div>
    </div>
    @if($readOnly)
    <div class="w-full max-w-xl">
        <a href="{{ route('incident-report.single', ['incidentId' => $incidentId]) }}"
            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl font-bold shadow-lg transition-colors duration-200 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 bg-green-600 hover:bg-green-700 text-white">
            <i class="fas fa-file-pdf"></i>
            Download Report (PDF)
        </a>
    </div>
    @endif
    <!-- Custom Scrollbar Styles -->
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-track {
            background: #374151;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #6b7280;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</div>