<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Dispatch;
use Illuminate\Support\Facades\DB;
use App\Models\IncidentNote;
use App\Models\IncidentTimeline;
use Illuminate\Support\Facades\Auth;
use App\Models\Incident;
use App\Services\FirebaseService;

class IncidentDispatch extends Component
{
    // Incident
    public $incidentId;
    public $readOnly = false;
    public $mainResponder = '';
    public $additionalResponders = [];
    public $allResponders = [];
    public $successMessage = '';
    public $errorMessage = '';

    // Notes
    public $incidentNotes = [];
    public $newNote = '';

    // Status
    public $status = '';
    public $statusOptions = [
        'new' => 'New',
        'dispatched' => 'Dispatched',
        'resolved' => 'Resolved',
    ];
    // Timeline
    public $timeline = [];

    public function mount($incidentId)
    {
        // Detect if 'from' query param is set to 'logs'
        $this->incidentId = $incidentId;
        $fromLogs = request()->query('from') === 'logs';
        $this->readOnly = $fromLogs;
        // Only responders (role = 'responder')
        $this->allResponders = User::where('role', 'responder')->get();

        // Load assigned responders from dispatches table
        $dispatches = Dispatch::where('incident_id', $incidentId)->get();
        if ($dispatches->count() > 0) {
            $this->mainResponder = $dispatches->first()->responder_id;
            $this->additionalResponders = $dispatches->skip(1)->pluck('responder_id')->toArray();
        } else {
            $this->mainResponder = '';
            $this->additionalResponders = [];
        }

        // Load notes for this incident
        $this->loadNotes();

        // Load current status from MySQL
        $this->loadStatus();

        // Load timeline
        $this->loadTimeline();
    }
    public function loadStatus()
    {
        $incident = Incident::where('firebase_id', $this->incidentId)
            ->orWhere('id', $this->incidentId)
            ->first();
        $this->status = $incident ? $incident->status : '';
    }

    public function loadTimeline()
    {
        // Always resolve to DB id for timeline consistency
        $incident = Incident::where('id', $this->incidentId)
            ->orWhere('firebase_id', $this->incidentId)
            ->first();
        $dbId = $incident ? $incident->id : $this->incidentId;
        $this->timeline = IncidentTimeline::where('incident_id', $dbId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateStatus()
    {
        // Always save status as lowercase
        $status = strtolower($this->status);
        $incident = Incident::where('firebase_id', $this->incidentId)->orWhere('id', $this->incidentId)->first();
        if ($incident) {
            // Remove resolved incident from Firebase and MySQL if changing from resolved to dispatched or new
            $previousStatus = $incident->status;
            $incident->status = $status;
            // If resolved, set resolved_at timestamp
            $resolvedAt = null;
            if ($status === 'resolved') {
                $resolvedAt = now();
                $incident->resolved_at = $resolvedAt;
            } else if (($status === 'dispatched' || $status === 'new') && $previousStatus === 'resolved') {
                // Remove from resolved_incidents in Firebase
                if (!empty($incident->firebase_id)) {
                    try {
                        $firebase = new FirebaseService();
                        $firebase->removeResolvedIncident($incident->firebase_id);
                    } catch (\Throwable $e) {
                        // Optionally log error
                    }
                }
                // Remove resolved log from MySQL incident_logs (match both DB id and Firebase id)
                \App\Models\IncidentLog::where(function ($q) use ($incident) {
                    $q->where('incident_id', $incident->id)
                        ->orWhere('incident_id', $incident->firebase_id);
                })
                    ->where('status', 'resolved')
                    ->delete();
                $incident->resolved_at = null;
            }
            $incident->save();

            // Update or clear related dispatches for this incident (force IDs to string)
            $idInt = (string) $incident->id;
            $idFirebase = (string) $incident->firebase_id;
            if ($status === 'new') {
                // When resetting to New, unassign all responders and clear notes/timeline/history in a transaction
                DB::beginTransaction();
                try {
                    \App\Models\Dispatch::where(function ($q) use ($idInt, $idFirebase) {
                        $q->where('incident_id', $idInt)
                            ->orWhere('incident_id', $idFirebase);
                    })->delete();

                    // Clear notes
                    \App\Models\IncidentNote::where(function ($q) use ($idInt, $idFirebase) {
                        $q->where('incident_id', $idInt)
                            ->orWhere('incident_id', $idFirebase);
                    })->delete();
                    // Clear timeline
                    \App\Models\IncidentTimeline::where(function ($q) use ($idInt, $idFirebase) {
                        $q->where('incident_id', $idInt)
                            ->orWhere('incident_id', $idFirebase);
                    })->delete();
                    // Clear incident logs (status history)
                    \App\Models\IncidentLog::where(function ($q) use ($idInt, $idFirebase) {
                        $q->where('incident_id', $idInt)
                            ->orWhere('incident_id', $idFirebase);
                    })->delete();
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->errorMessage = 'Failed to reset incident data: ' . $e->getMessage();
                    return;
                }
            } else {
                \App\Models\Dispatch::where(function ($q) use ($idInt, $idFirebase) {
                    $q->where('incident_id', $idInt)
                        ->orWhere('incident_id', $idFirebase);
                })->update(['status' => $status]);
            }

            $this->successMessage = 'Status updated.';
            // Database notification for admins
            $admins = \App\Models\User::where('role', 'admin')->get();
            if ($admins->count() > 0) {
                $msg = 'Incident status changed to ' . ucfirst($status) . ' for incident ' . ($incident->type ?? '') . ' at ' . ($incident->location ?? '');
                // Use relative link so it respects subfolder deployments (e.g., /CAP102_TEST/public)
                $link = 'dispatch?incident_id=' . ($incident->firebase_id ?? $incident->id);
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminIncidentNotification(
                    'status',
                    ($incident->firebase_id ?? $incident->id),
                    $msg,
                    $link,
                    ['status' => $status, 'incident' => $incident]
                ));
            }
        } else {
            $this->errorMessage = 'Incident not found.';
            return;
        }

        // Reflect status change in Firebase as well
        try {
            if (!empty($incident->firebase_id)) {
                $firebase = new FirebaseService();
                $firebase->updateIncidentStatus($incident->firebase_id, $status);
                // If resolved, also log to resolved_incidents for analytics
                if ($status === 'resolved') {
                    $firebase->logResolvedIncident($incident->firebase_id, $resolvedAt);
                }
            }
        } catch (\Throwable $e) {
            // Don't block UI on Firebase failure; surface a soft warning
            $this->errorMessage = 'Status updated locally, but failed to update Firebase: ' . $e->getMessage();
        }
        // Log to timeline unless we've reset to a clean slate
        if ($status !== 'new') {
            $user = Auth::user();
            \App\Models\IncidentTimeline::create([
                'incident_id' => $incident->id,
                'user_id' => $user ? $user->id : null,
                'action' => 'status_changed',
                'details' => $this->status,
            ]);
            $this->loadTimeline();
        }
    }

    public function addResponder()
    {
        $this->additionalResponders[] = '';
    }

    public function removeResponder($index)
    {
        unset($this->additionalResponders[$index]);
        $this->additionalResponders = array_values($this->additionalResponders);
    }

    public function dispatchIncident()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
        // Build the final, unique list of responder IDs (lead + additional)
        $responderIds = array_values(array_unique(array_filter(array_merge([$this->mainResponder], $this->additionalResponders))));

        // Resolve incident and collect both possible identifiers (DB id and firebase_id)
        $incident = Incident::where('firebase_id', $this->incidentId)
            ->orWhere('id', $this->incidentId)
            ->first();
        $matchIncidentIds = $incident
            ? array_values(array_unique(array_filter([(string) $incident->id, (string) $incident->firebase_id, (string) $this->incidentId])))
            : [(string) $this->incidentId];

        DB::beginTransaction();
        try {
            // If nothing is selected, treat this as "unassign all responders"
            if (empty($responderIds)) {
                // Unassign all responders
                Dispatch::whereIn('incident_id', $matchIncidentIds)->delete();
                // Clear notes, timeline, and logs for a full reset
                \App\Models\IncidentNote::where(function ($q) use ($matchIncidentIds) {
                    $q->whereIn('incident_id', $matchIncidentIds);
                })->delete();
                \App\Models\IncidentTimeline::where(function ($q) use ($matchIncidentIds) {
                    $q->whereIn('incident_id', $matchIncidentIds);
                })->delete();
                \App\Models\IncidentLog::where(function ($q) use ($matchIncidentIds) {
                    $q->whereIn('incident_id', $matchIncidentIds);
                })->delete();

                if ($incident) {
                    $incident->status = 'new';
                    // If it was previously resolved, ensure resolved_at cleared (safety)
                    $incident->resolved_at = null;
                    $incident->save();
                    // Reflect to Firebase
                    try {
                        if (!empty($incident->firebase_id)) {
                            $firebase = new FirebaseService();
                            $firebase->updateIncidentStatus($incident->firebase_id, 'new');
                            // Also remove from resolved_incidents if exists
                            try {
                                (new FirebaseService())->removeResolvedIncident($incident->firebase_id);
                            } catch (\Throwable $e) {
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->errorMessage = 'Unassigned locally, but failed to update Firebase: ' . $e->getMessage();
                    }
                }
                DB::commit();
                // Reset UI state
                $this->mainResponder = '';
                $this->additionalResponders = [];
                $this->successMessage = 'Incident reset to New: responders, notes, and history cleared.';
                // Refresh UI data immediately (no need to wait for poll)
                $this->loadStatus();
                $this->loadNotes();
                $this->loadTimeline();
                return;
            }
            // Upsert the selected responders
            foreach ($responderIds as $responderId) {
                Dispatch::updateOrCreate(
                    [
                        'incident_id' => $this->incidentId,
                        'responder_id' => $responderId,
                    ],
                    [
                        'status' => 'dispatched',
                    ]
                );
            }

            // Remove responders no longer selected (sync behavior)
            $existingResponderIds = Dispatch::whereIn('incident_id', $matchIncidentIds)
                ->pluck('responder_id')
                ->toArray();
            $toDelete = array_diff($existingResponderIds, $responderIds);
            if (!empty($toDelete)) {
                Dispatch::whereIn('incident_id', $matchIncidentIds)
                    ->whereIn('responder_id', $toDelete)
                    ->delete();
            }
            // Also update the incident status in the incidents table
            if ($incident) {
                $incident->status = 'dispatched';
                $incident->save();
                // Reflect to Firebase as well
                try {
                    if (!empty($incident->firebase_id)) {
                        $firebase = new FirebaseService();
                        $firebase->updateIncidentStatus($incident->firebase_id, 'dispatched');
                    }
                } catch (\Throwable $e) {
                    // Soft warning only
                    $this->errorMessage = 'Dispatched locally, but failed to update Firebase: ' . $e->getMessage();
                }
            }
            DB::commit();
            $this->successMessage = 'Dispatch updated successfully!';
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errorMessage = 'Dispatch failed: ' . $e->getMessage();
        }
    }

    public function loadNotes()
    {
        // Always resolve to DB id for notes
        $incident = \App\Models\Incident::where('id', $this->incidentId)
            ->orWhere('firebase_id', $this->incidentId)
            ->first();
        $dbId = $incident ? $incident->id : $this->incidentId;
        $this->incidentNotes = IncidentNote::where('incident_id', $dbId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function addNote()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
        $noteText = trim($this->newNote);
        if ($noteText === '') {
            $this->errorMessage = 'Note cannot be empty.';
            return;
        }
        $user = Auth::user();
        if (!$user) {
            $this->errorMessage = 'You must be logged in to add a note.';
            return;
        }
        // Always resolve to DB id for notes
        $incident = \App\Models\Incident::where('id', $this->incidentId)
            ->orWhere('firebase_id', $this->incidentId)
            ->first();
        $dbId = $incident ? $incident->id : $this->incidentId;
        IncidentNote::create([
            'incident_id' => $dbId,
            'user_id' => $user->id,
            'note' => $noteText,
        ]);
        $this->newNote = '';
        // Database notification for admins
        $admins = \App\Models\User::where('role', 'admin')->get();
        if ($admins->count() > 0) {
            $msg = 'New note added to incident ' . ($incident->type ?? '') . ' at ' . ($incident->location ?? '') . ': "' . $noteText . '"';
            // Use relative link so it respects subfolder deployments (e.g., /CAP102_TEST/public)
            $link = 'dispatch?incident_id=' . ($incident->firebase_id ?? $incident->id);
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminIncidentNotification(
                'note',
                ($incident->firebase_id ?? $incident->id),
                $msg,
                $link,
                ['note' => $noteText, 'incident' => $incident]
            ));
        }
        $this->loadNotes();
        $this->successMessage = 'Note added.';

        // Log to timeline
        IncidentTimeline::create([
            'incident_id' => $dbId,
            'user_id' => $user->id,
            'action' => 'note_added',
            'details' => 'Note added',
        ]);
        $this->loadTimeline();
    }

    public function render()
    {
        return view('livewire.incident-dispatch');
    }

    public function pollUpdates()
    {
        $this->loadNotes();
        $this->loadStatus();
        $this->loadTimeline();
    }

    public function deleteIncident()
    {
        $incident = Incident::where('firebase_id', $this->incidentId)
            ->orWhere('id', $this->incidentId)
            ->first();
        if ($incident) {
            // Delete from Firebase
            if (!empty($incident->firebase_id)) {
                try {
                    $firebase = new FirebaseService();
                    // Remove from main incidents node
                    $firebase->updateIncidentStatus($incident->firebase_id, 'deleted');
                    $firebase->removeResolvedIncident($incident->firebase_id);
                    $firebase->deleteIncidentFromFirebase($incident->firebase_id);
                } catch (\Throwable $e) {
                    // Optionally log error
                }
            }
            // Delete from MySQL
            \App\Models\IncidentLog::where(function ($q) use ($incident) {
                $q->where('incident_id', $incident->id)
                    ->orWhere('incident_id', $incident->firebase_id);
            })->delete();
            Dispatch::where('incident_id', $incident->id)
                ->orWhere('incident_id', $incident->firebase_id)
                ->delete();
            IncidentNote::where('incident_id', $incident->id)
                ->orWhere('incident_id', $incident->firebase_id)
                ->delete();
            IncidentTimeline::where('incident_id', $incident->id)
                ->orWhere('incident_id', $incident->firebase_id)
                ->delete();
            $incident->delete();
            $this->successMessage = 'Incident deleted from both MySQL and Firebase.';
        } else {
            $this->errorMessage = 'Incident not found.';
        }
    }
}
