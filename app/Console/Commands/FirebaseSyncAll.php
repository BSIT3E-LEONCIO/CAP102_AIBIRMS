<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use App\Models\User;

class FirebaseSyncAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:sync-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all relevant data from Firebase to MySQL (incidents, users, dispatches, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting full Firebase to MySQL sync...');
        $factory = (new \Kreait\Firebase\Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database_url'));
        $database = $factory->createDatabase();

        // --- Add additional sync logic for other tables below ---

        // Example: Sync mobile_incidents (to incidents table)
        $this->info('Syncing mobile_incidents...');
        $mobileRaw = $database->getReference('mobile_incidents')->getValue() ?? [];
        $countMobile = 0;
        foreach ($mobileRaw as $firebaseKey => $incident) {
            // Detect if new before upsert
            $existed = DB::table('incidents')->where('firebase_id', $firebaseKey)->exists();
            // Prioritize latitude/longitude if available
            $latitude = isset($incident['latitude']) && is_numeric($incident['latitude']) ? $incident['latitude'] : null;
            $longitude = isset($incident['longitude']) && is_numeric($incident['longitude']) ? $incident['longitude'] : null;
            $location = $incident['location'] ?? null;
            DB::table('incidents')->updateOrInsert(
                ['firebase_id' => $firebaseKey],
                [
                    'type' => $incident['type'] ?? null,
                    'location' => $location,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'reporter_name' => $incident['reporter_name'] ?? null,
                    'contact_number' => $incident['contact_number'] ?? null,
                    'reporter_id' => $incident['reporter_id'] ?? null,
                    'department' => $incident['department'] ?? null,
                    'status' => $incident['status'] ?? null,
                    'timestamp' => isset($incident['timestamp']) ? date('Y-m-d H:i:s', strtotime($incident['timestamp'])) : null,
                    'source' => $incident['source'] ?? 'mobile',
                    'incident_description' => $incident['incident_description'] ?? null,
                    'priority' => $incident['priority'] ?? null,
                    'severity' => $incident['severity'] ?? null,
                    'proof_image_url' => $incident['proofImageUrl'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            // If it didn't exist before, create an admin DB notification
            if (! $existed) {
                try {
                    $admins = User::where('role', 'admin')->get();
                    if ($admins->count() > 0) {
                        $msg = 'New incident reported: ' . ($incident['type'] ?? '') . ' at ' . ($location ?? 'Unknown location');
                        // Store relative link so UI works under subfolders
                        $link = 'dispatch?incident_id=' . $firebaseKey;
                        Notification::send($admins, new \App\Notifications\AdminIncidentNotification(
                            'incident',
                            $firebaseKey,
                            $msg,
                            $link,
                            ['raw' => $incident]
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to send mobile sync incident notification', ['error' => $e->getMessage(), 'id' => $firebaseKey]);
                }
            }
            $countMobile++;
        }
        $this->info("Synced {$countMobile} mobile incidents.");

        // Example: Sync CCTV incidents (to incidents table)
        $this->info('Syncing CCTV incidents...');
        $cctvRaw = $database->getReference('incidents')->getValue() ?? [];
        $countCctv = 0;
        foreach ($cctvRaw as $firebaseKey => $incident) {
            // Only sync if this is a CCTV incident (e.g., has camera_name or source is cctv)
            $isCctv = isset($incident['camera_name']) || ($incident['source'] ?? null) === 'cctv';
            if (!$isCctv) continue;
            // Detect if new before upsert
            $existed = DB::table('incidents')->where('firebase_id', $firebaseKey)->exists();

            // Derive department if missing in Firebase based on the event type
            $eventRaw = $incident['event'] ?? '';
            $event = is_string($eventRaw) ? strtolower(trim($eventRaw)) : '';
            $derivedDepartment = match ($event) {
                'natural_disaster'   => 'MDRRMO',
                'fire'               => 'BFP',
                'criminal_activity' => 'PNP',
                'accident' => 'QRT',
                default              => null,
            };
            $department = $incident['department'] ?? $derivedDepartment;
            DB::table('incidents')->updateOrInsert(
                ['firebase_id' => $firebaseKey],
                [
                    'type' => $incident['event'] ?? 'CCTV',
                    'location' => $incident['camera_name'] ?? null,
                    'reporter_name' => 'CCTV',
                    'reporter_id' => null,
                    'department' => $department,
                    'status' => $incident['status'] ?? null,
                    'timestamp' => isset($incident['timestamp']) ? date('Y-m-d H:i:s', strtotime($incident['timestamp'])) : null,
                    'source' => 'cctv',
                    'incident_description' => null,
                    'proof_image_url' => $incident['firebase_url'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            // If it didn't exist before, create an admin DB notification
            if (! $existed) {
                try {
                    $admins = User::where('role', 'admin')->get();
                    if ($admins->count() > 0) {
                        $msg = 'New incident reported: ' . ($incident['event'] ?? 'CCTV') . ' at ' . ($incident['camera_name'] ?? '');
                        // Store relative link so UI works under subfolders
                        $link = 'dispatch?incident_id=' . $firebaseKey;
                        Notification::send($admins, new \App\Notifications\AdminIncidentNotification(
                            'incident',
                            $firebaseKey,
                            $msg,
                            $link,
                            ['raw' => $incident]
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to send CCTV sync incident notification', ['error' => $e->getMessage(), 'id' => $firebaseKey]);
                }
            }
            $countCctv++;
        }
        $this->info("Synced {$countCctv} CCTV incidents.");

        // --- Sync resolved_incidents (incident_logs) ---
        $this->info('Syncing resolved_incidents to incident_logs...');
        $resolvedRaw = $database->getReference('resolved_incidents')->getValue() ?? [];
        $countLogs = 0;
        foreach ($resolvedRaw as $firebaseKey => $incident) {
            $incidentId = $incident['incident_id'] ?? $incident['firebase_id'] ?? $firebaseKey;
            $isCctv = isset($incident['camera_name']) || ($incident['source'] ?? null) === 'cctv';
            DB::table('incident_logs')->updateOrInsert(
                ['incident_id' => $incidentId],
                [
                    'type' => $isCctv ? ($incident['event'] ?? 'CCTV') : ($incident['type'] ?? null),
                    'location' => $isCctv ? ($incident['camera_name'] ?? null) : ($incident['location'] ?? null),
                    'reporter_name' => $isCctv ? 'CCTV' : ($incident['reporter_name'] ?? null),
                    'reporter_id' => $isCctv ? null : ($incident['reporter_id'] ?? null),
                    'department' => $incident['department'] ?? null,
                    'status' => $incident['status'] ?? null,
                    'timestamp' => isset($incident['timestamp']) ? date('Y-m-d H:i:s', strtotime($incident['timestamp'])) : null,
                    'resolved_at' => isset($incident['resolved_at']) ? date('Y-m-d H:i:s', strtotime($incident['resolved_at'])) : null,
                    'source' => $incident['source'] ?? ($isCctv ? 'cctv' : null),
                    'incident_description' => $isCctv ? ($incident['screenshot_path'] ?? null) : ($incident['incident_description'] ?? null),
                    'priority' => $incident['priority'] ?? null,
                    'severity' => $incident['severity'] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $countLogs++;
        }
        $this->info("Synced {$countLogs} resolved incidents to incident_logs.");

        // Example: Sync dispatches
        $this->info('Syncing dispatches...');
        $dispatchesRaw = $database->getReference('dispatches')->getValue() ?? [];
        $countDispatches = 0;
        foreach ($dispatchesRaw as $dispatchId => $dispatch) {
            DB::table('dispatches')->updateOrInsert(
                ['firebase_id' => $dispatchId],
                [
                    'incident_id' => $dispatch['incident_id'] ?? null,
                    'responder_id' => $dispatch['responder_id'] ?? null,
                    'status' => $dispatch['status'] ?? null,
                    'assigned_at' => isset($dispatch['assigned_at']) ? date('Y-m-d H:i:s', strtotime($dispatch['assigned_at'])) : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $countDispatches++;
        }
        $this->info("Synced {$countDispatches} dispatches.");

        $this->info('Firebase to MySQL sync complete.');
    }
}
