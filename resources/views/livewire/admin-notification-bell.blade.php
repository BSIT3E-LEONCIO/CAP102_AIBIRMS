<div class="relative" x-data="{ open: false }" wire:poll.5s="fetchNotifications">
    <!-- Notification Bell Button -->
    <button type="button" @click.stop="open = !open; if(open) { $wire.markAllRead() }"
        class="relative focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-full group transition-all duration-200">
        <span
            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-gray-800 shadow-md hover:shadow-lg hover:scale-105 hover:bg-blue-50 dark:hover:bg-blue-900 transition-all duration-200 border border-gray-200 dark:border-gray-700">
            <i
                class="fas fa-bell text-xl text-gray-600 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200"></i>
        </span>
        @if($unreadCount > 0)
        <span
            class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold leading-none text-white bg-gradient-to-br from-red-500 to-red-600 rounded-full shadow-lg animate-pulse">{{
            $unreadCount }}</span>
        @endif
    </button>

    <!-- Dropdown Container -->
    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-2" @click.outside="open = false"
        class="absolute right-0 mt-3 w-96 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-sm"
        style="min-width: 320px;">
        <!-- Filter and Sort Header -->
        <div
            class="sticky top-0 z-10 flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 backdrop-blur-sm">
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Filter:</span>
                <button
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 @if($filter==='all') bg-blue-600 text-white shadow-md shadow-blue-500/30 @else bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 @endif"
                    wire:click="setFilter('all')">All</button>
                <button
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 @if($filter==='incident') bg-blue-600 text-white shadow-md shadow-blue-500/30 @else bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 @endif"
                    wire:click="setFilter('incident')">Incidents</button>
                <button
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 @if($filter==='status') bg-blue-600 text-white shadow-md shadow-blue-500/30 @else bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 @endif"
                    wire:click="setFilter('status')">Statuses</button>
                <button
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 @if($filter==='note') bg-blue-600 text-white shadow-md shadow-blue-500/30 @else bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 @endif"
                    wire:click="setFilter('note')">Notes</button>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 ml-1 mr-1">Sort:</span>
                <button
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 @if($sort==='desc') bg-blue-600 text-white shadow-md shadow-blue-500/30 @else bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 @endif"
                    wire:click="setSort('desc')">New</button>
                <button
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 @if($sort==='asc') bg-blue-600 text-white shadow-md shadow-blue-500/30 @else bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 @endif"
                    wire:click="setSort('asc')">Old</button>
            </div>
        </div>

        <!-- Notifications List -->
        <div
            class="max-h-[340px] custom-scrollbar overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
            @forelse($notifications as $notif)
            @php
            // Build a safe href that works even when the app runs under a subfolder (e.g., /CAP102_TEST/public)
            $rawLink = $notif->data['link'] ?? '';
            $type = $notif->data['type'] ?? null;
            $incidentId = $notif->data['incident_id'] ?? null;

            // Default href
            $href = '#';

            if ($type === 'incident' && $incidentId) {
            // Always generate the absolute URL via named route to avoid relative path issues
            // e.g., on /incidents/cctv a plain "dispatch?..." would resolve to /incidents/dispatch
            $href = route('dispatch', ['incident_id' => $incidentId]);
            } elseif (is_string($rawLink) && $rawLink !== '') {
            // Keep fully-qualified URLs as-is; otherwise resolve against the app base URL
            if (str_starts_with($rawLink, 'http://') || str_starts_with($rawLink, 'https://')) {
            $href = $rawLink;
            } else {
            // Ensure we build an absolute URL that respects subfolder deployments
            $href = url('/' . ltrim($rawLink, '/'));
            }
            }
            @endphp
            <div class="relative group">
                <a href="{{ $href }}"
                    class="block px-5 py-4 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-200 group @if(!$notif->read_at) bg-blue-50/30 dark:bg-blue-900/10 @endif">
                    <div class="flex items-start gap-3">
                        <!-- Icon with type-specific styling -->
                        <div class="flex-shrink-0 mt-1">
                            <span
                                class="inline-flex items-center justify-center w-8 h-8 rounded-full @if($notif->data['type']==='incident') bg-blue-100 dark:bg-blue-900/30 @elseif($notif->data['type']==='status') bg-yellow-100 dark:bg-yellow-900/30 @elseif($notif->data['type']==='note') bg-purple-100 dark:bg-purple-900/30 @else bg-gray-100 dark:bg-gray-700 @endif">
                                @if($notif->data['type']==='incident')
                                <i class="fas fa-exclamation-triangle text-blue-600 dark:text-blue-400 text-sm"></i>
                                @elseif($notif->data['type']==='status')
                                <i class="fas fa-sync-alt text-yellow-600 dark:text-yellow-400 text-sm"></i>
                                @elseif($notif->data['type']==='note')
                                <i class="fas fa-sticky-note text-purple-600 dark:text-purple-400 text-sm"></i>
                                @else
                                <i class="fas fa-bell text-gray-600 dark:text-gray-400 text-sm"></i>
                                @endif
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium @if($notif->data['type']==='incident') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 @elseif($notif->data['type']==='status') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 @elseif($notif->data['type']==='note') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ ucfirst($notif->data['type'] ?? 'notification') }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <i class="far fa-clock text-[10px]"></i>
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-snug line-clamp-2 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
                                {{ $notif->data['message'] ?? 'Notification' }}
                            </p>
                        </div>

                        <!-- Arrow indicator -->
                        <div class="flex-shrink-0 mt-10 opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-chevron-right text-blue-600 dark:text-blue-400 text-xs"></i>
                        </div>
                    </div>
                </a>
                <!-- Delete/hide button -->
                <button wire:click="deleteNotification('{{ $notif->id }}')"
                    class="absolute top-2 right-2 z-10 text-gray-400 hover:text-red-500 bg-white dark:bg-gray-800 rounded-full p-1 transition-colors duration-150"
                    title="Delete notification">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <i class="fas fa-bell-slash text-3xl text-gray-400 dark:text-gray-500"></i>
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">No notifications</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">You're all caught up!</p>
            </div>
            @endforelse
        </div>
    </div>
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