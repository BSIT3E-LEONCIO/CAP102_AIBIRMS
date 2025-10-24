<?php

namespace App\Services;

use Kreait\Firebase\Factory;


class FirebaseService
{
    /**
     * Delete an incident from Firebase (mobile_incidents and incidents nodes).
     * Used for bulk delete from Livewire tables.
     */
    public function deleteIncident($incidentId)
    {
        return $this->deleteIncidentFromFirebase($incidentId);
    }
    /**
     * Copy a resolved incident to incident_logs without removing from original node.
     */
    public function logResolvedIncident($incidentId, $resolvedAt = null)
    {
        // Try mobile_incidents first
        $ref = $this->database->getReference('mobile_incidents/' . $incidentId);
        $incident = $ref->getValue();
        if (!$incident) {
            // Try incidents (CCTV)
            $ref = $this->database->getReference('incidents/' . $incidentId);
            $incident = $ref->getValue();
        }
        if ($incident) {
            $incident['incident_id'] = $incidentId;
            $incident['status'] = 'resolved';
            $incident['resolved_at'] = $resolvedAt ? (is_string($resolvedAt) ? $resolvedAt : $resolvedAt->toIso8601String()) : now()->toIso8601String();
            $logRef = $this->database->getReference('resolved_incidents/' . $incidentId);
            $logRef->set($incident);
            return true;
        }
        return false;
    }
    /**
     * Get all resolved incidents from Firebase.
     */
    public function getResolvedIncidents()
    {
        $resolved = $this->database->getReference('resolved_incidents')->getValue() ?? [];
        // Add a source property for each
        $resolvedList = collect($resolved)->map(function ($item) {
            $item['source'] = $item['source'] ?? 'resolved';
            return $item;
        })->values()->all();
        return $resolvedList;
    }

    /**
     * Remove a resolved incident from resolved_incidents in Firebase.
     */
    public function removeResolvedIncident($incidentId)
    {
        $ref = $this->database->getReference('resolved_incidents/' . $incidentId);
        $ref->remove();
        return true;
    }
    /**
     * Update the status of an incident in Firebase (mobile or CCTV).
     */
    public function updateIncidentStatus($incidentId, $status)
    {
        // Try mobile_incidents first
        $ref = $this->database->getReference('mobile_incidents/' . $incidentId);
        $incident = $ref->getValue();
        if ($incident) {
            $ref->update(['status' => $status]);
            return true;
        }
        // Try incidents (CCTV)
        $ref = $this->database->getReference('incidents/' . $incidentId);
        $incident = $ref->getValue();
        if ($incident) {
            $ref->update(['status' => $status]);
            return true;
        }
        return false;
    }
    /**
     * Create a new incident in Firebase with AI-predicted severity/priority.
     * Calls Flask API for prediction, adds fields, and pushes to mobile_incidents.
     * Returns the new incident data (including incident_id).
     */
    public function createIncidentWithPrediction(array $incident)
    {
        // Call Flask API for severity prediction
        try {
            $response = \Illuminate\Support\Facades\Http::post('http://127.0.0.1:5000/predict-severity', [
                'description' => $incident['incident_description'] ?? ''
            ]);
            $incident['severity'] = $response->json('severity') ?? 'unknown';
            $incident['priority'] = $incident['severity'];
        } catch (\Exception $e) {
            $incident['severity'] = 'unknown';
            $incident['priority'] = 'unknown';
        }

        // Push to Firebase
        $ref = $this->database->getReference('mobile_incidents');
        $newRef = $ref->push($incident);
        $incidentId = $newRef->getKey();
        $newRef->update(['incident_id' => $incidentId]);
        $incident['incident_id'] = $incidentId;

        // Send email and database notification to all admins
        try {
            $admins = \App\Models\User::where('role', 'admin')->get();
            if ($admins->count() > 0) {
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewIncidentNotification((object) $incident));
                // Database notification
                $msg = 'New incident reported: ' . ($incident['type'] ?? $incident['event'] ?? '') . ' at ' . ($incident['location'] ?? $incident['camera_name'] ?? '');
                // Use relative link so it works when app is in a subfolder (no leading slash)
                $link = 'dispatch?incident_id=' . ($incident['incident_id'] ?? $incident['firebase_id'] ?? '');
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminIncidentNotification(
                    'incident',
                    ($incident['incident_id'] ?? $incident['firebase_id'] ?? ''),
                    $msg,
                    $link,
                    ['raw' => $incident]
                ));
            }
        } catch (\Exception $e) {
            // Optionally log error
            \Illuminate\Support\Facades\Log::error('Failed to send new incident notification', ['error' => $e->getMessage()]);
        }

        return $incident;
    }
    protected $database;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials'))
            ->withDatabaseUri(config('firebase.database_url'));

        $this->database = $factory->createDatabase();
    }



    /**
     * Alias for getAllIncidents for compatibility.
     */
    public function getIncidents()
    {
        return $this->getAllIncidents();
    }



    /**
     * Get all incidents from both mobile_incidents and incidents (CCTV) as a merged array.
     */
    public function getAllIncidents()
    {
        $mobile = $this->database->getReference('mobile_incidents')->getValue() ?? [];
        $cctv = $this->database->getReference('incidents')->getValue() ?? [];
        // Add a source property for each
        $mobileList = collect($mobile)->map(function ($item) {
            $item['source'] = 'mobile';
            return $item;
        })->values()->all();
        $cctvList = collect($cctv)->map(function ($item) {
            $item['source'] = 'cctv';
            return $item;
        })->values()->all();
        // Merge and return
        return array_merge($mobileList, $cctvList);
    }
    public function getIncidentById($incidentId)
    {
        // Try mobile_incidents first, then fallback to incidents (for CCTV)
        $mobile = $this->database->getReference('mobile_incidents/' . $incidentId)->getValue();
        if ($mobile) return $mobile;
        return $this->database->getReference('incidents/' . $incidentId)->getValue();
    }
    /**
     * Get summary counts for dashboard (total, current, completed issues) from both sources.
     */
    public function getSummaryData()
    {
        $all = $this->getAllIncidents();
        $total = count($all);
        $current = collect($all)->where('status', '!=', 'resolved')->count();
        // Count completed issues from resolved_incidents node
        $resolved = $this->database->getReference('resolved_incidents')->getValue() ?? [];
        $completed = count($resolved);
        return [
            'total_cases' => $total,
            'current_issues' => $current,
            'completed_issues' => $completed,
        ];
    }
    /**
     * Delete an incident from both mobile_incidents and incidents nodes in Firebase.
     */
    public function deleteIncidentFromFirebase($incidentId)
    {
        $this->database->getReference('mobile_incidents/' . $incidentId)->remove();
        $this->database->getReference('incidents/' . $incidentId)->remove();
    }
}
