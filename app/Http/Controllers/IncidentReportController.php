<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use App\Models\Incident;
use App\Models\Dispatch;
use App\Models\IncidentNote;
use App\Models\IncidentTimeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\FirebaseService;

class IncidentReportController extends Controller
{
    public function generate(Request $request)
    {
        $source = $request->input('source', 'mobile');
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());
        $typeFilter = $request->input('typeFilter', '');
        $statusFilter = $request->input('statusFilter', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 10);
        $allYears = $request->input('allYears', false);

        // Build query
        $query = Incident::query()->where('source', $source);
        if ($typeFilter) $query->where('type', $typeFilter);
        if ($statusFilter) $query->where('status', $statusFilter);

        // Date filtering anchored to selected date
        $anchor = Carbon::parse($date);
        switch ($period) {
            case 'day':
                $query->whereDate('timestamp', $anchor->toDateString());
                break;
            case 'week':
                $query->whereBetween('timestamp', [
                    $anchor->copy()->startOfWeek(),
                    $anchor->copy()->endOfWeek(),
                ]);
                break;
            case 'month':
                $query->whereYear('timestamp', $anchor->year)
                    ->whereMonth('timestamp', $anchor->month);
                break;
            case 'year':
                if (!$allYears) {
                    $query->whereYear('timestamp', $anchor->year);
                }
                // else: no year filter, get all years
                break;
        }

        // For report/PDF, fetch all filtered incidents (no pagination)
        $incidents = $query->orderBy('timestamp', 'desc')->get();

        // Chart data
        // Build base filtered query for charts (clone of $query without pagination)
        $baseForCharts = (clone $query);
        $severityData = [];
        if ($source === 'mobile') {
            $severityData = (clone $baseForCharts)
                ->selectRaw('severity, count(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray();
        }
        $statusData = (clone $baseForCharts)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Type distribution
        $typeData = (clone $baseForCharts)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Time series data depending on period
        $timeSeries = ['labels' => [], 'data' => []];
        if ($period === 'day') {
            $buckets = (clone $baseForCharts)
                ->selectRaw('HOUR(timestamp) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket')
                ->toArray();
            for ($h = 0; $h < 24; $h++) {
                $timeSeries['labels'][] = sprintf('%02d:00', $h);
                $timeSeries['data'][] = (int)($buckets[$h] ?? 0);
            }
        } elseif ($period === 'week') {
            $start = $anchor->copy()->startOfWeek();
            $end = $anchor->copy()->endOfWeek();
            $buckets = (clone $baseForCharts)
                ->selectRaw('DATE(timestamp) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket')
                ->toArray();
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $label = $d->format('Y-m-d');
                $timeSeries['labels'][] = $label;
                $timeSeries['data'][] = (int)($buckets[$label] ?? 0);
            }
        } elseif ($period === 'month') {
            $start = $anchor->copy()->startOfMonth();
            $end = $anchor->copy()->endOfMonth();
            $buckets = (clone $baseForCharts)
                ->selectRaw('DATE(timestamp) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket')
                ->toArray();
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $label = $d->format('Y-m-d');
                $timeSeries['labels'][] = $label;
                $timeSeries['data'][] = (int)($buckets[$label] ?? 0);
            }
        } elseif ($period === 'year' && $allYears) {
            // Group by year for all years
            $buckets = (clone $baseForCharts)
                ->selectRaw('YEAR(timestamp) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket')
                ->toArray();
            $years = array_keys($buckets);
            sort($years);
            foreach ($years as $year) {
                $timeSeries['labels'][] = (string)$year;
                $timeSeries['data'][] = (int)($buckets[$year] ?? 0);
            }
        } else { // year (single year)
            $buckets = (clone $baseForCharts)
                ->selectRaw('MONTH(timestamp) as bucket, COUNT(*) as count')
                ->groupBy('bucket')
                ->pluck('count', 'bucket')
                ->toArray();
            for ($m = 1; $m <= 12; $m++) {
                $timeSeries['labels'][] = Carbon::create($anchor->year, $m, 1)->format('Y-m');
                $timeSeries['data'][] = (int)($buckets[$m] ?? 0);
            }
        }

        // Render Blade view to HTML
        $html = View::make('incidents.report', [
            'incidents' => $incidents,
            'source' => $source,
            'period' => $period,
            'date' => $date,
            'allYears' => $allYears,
            'severityData' => $severityData,
            'statusData' => $statusData,
            'typeData' => $typeData,
            'timeSeries' => $timeSeries,
        ])->render();

        // Generate PDF using Browsershot
        $pdf = Browsershot::html($html)
            ->setOption('args', ['--no-sandbox'])
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->setDelay(1200)
            ->pdf();

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="incident-report-' . $source . '-' . $period . '-' . now()->format('Ymd_His') . '.pdf"');
    }

    /**
     * Generate a single-incident PDF report with a structured template.
     * Supports both mobile and CCTV incidents, pulling details from MySQL first,
     * then falling back to Firebase if not found.
     */
    public function generateSingle(Request $request, string $incidentId)
    {
        // Attempt to resolve by DB id or firebase_id
        $incident = Incident::where('id', $incidentId)
            ->orWhere('firebase_id', $incidentId)
            ->first();

        $source = 'mobile';
        $payload = null;
        if ($incident) {
            $payload = $incident->toArray();
            // Check source field first, then camera_name as fallback
            $source = $payload['source'] ?? (!empty($payload['camera_name']) ? 'cctv' : 'mobile');
        } else {
            // Fallback to Firebase (read-only)
            try {
                $firebase = new FirebaseService();
                $payload = $firebase->getIncidentById($incidentId);
                if (!$payload) {
                    abort(404, 'Incident not found.');
                }
                // Check source field first, then camera_name as fallback
                $source = $payload['source'] ?? (!empty($payload['camera_name']) ? 'cctv' : 'mobile');
            } catch (\Throwable $e) {
                abort(404, 'Incident not found.');
            }
        }

        // Resolve identifiers for joins
        $dbId = $incident?->id;
        $fbId = $incident?->firebase_id ?? ($payload['incident_id'] ?? null);

        // Collect responders (names and types) from Dispatch joined with Users
        $responderQuery = Dispatch::query()
            ->when($dbId || $fbId, function ($q) use ($dbId, $fbId) {
                $q->where(function ($w) use ($dbId, $fbId) {
                    if ($dbId) $w->orWhere('incident_id', (string) $dbId);
                    if ($fbId) $w->orWhere('incident_id', (string) $fbId);
                });
            })
            ->join('users', 'dispatches.responder_id', '=', 'users.id')
            ->select('users.name', 'users.responder_type', 'dispatches.status', 'dispatches.created_at', 'dispatches.id')
            ->orderBy('dispatches.created_at')
            ->orderBy('dispatches.id');
        $responders = $responderQuery->get();

        $leadResponder = $responders->first();
        $additionalResponders = $responders->count() > 1 ? $responders->slice(1)->values() : collect();

        // Notes & Timeline (DB-only if we have a DB id)
        $notes = collect();
        $timeline = collect();
        if ($dbId) {
            $notes = IncidentNote::where('incident_id', $dbId)->with('user')->orderBy('created_at')->get();
            $timeline = IncidentTimeline::where('incident_id', $dbId)->with('user')->orderBy('created_at')->get();
        }

        // Try to normalize coordinates from various possible payload keys
        $lat = $payload['latitude']
            ?? $payload['lat']
            ?? ($payload['coords']['lat'] ?? null)
            ?? ($payload['coordinates']['lat'] ?? null)
            ?? null;
        $lng = $payload['longitude']
            ?? $payload['lng']
            ?? $payload['long']
            ?? ($payload['coords']['lng'] ?? ($payload['coords']['long'] ?? null))
            ?? ($payload['coordinates']['lng'] ?? ($payload['coordinates']['long'] ?? null))
            ?? null;

        // Compose a normalized data set for the template
        $data = [
            'id' => $payload['firebase_id'] ?? $payload['incident_id'] ?? $payload['id'] ?? (string) $incidentId,
            'source' => $source,
            'type' => $payload['type'] ?? $payload['event'] ?? 'unknown',
            'location' => $payload['location'] ?? $payload['camera_name'] ?? 'Unknown',
            'timestamp' => $payload['timestamp'] ?? $payload['datetime'] ?? $payload['date_time'] ?? ($incident?->created_at?->toDateTimeString()),
            'status' => $payload['status'] ?? $incident?->status ?? 'unknown',
            'resolved_at' => $payload['resolved_at'] ?? ($incident?->resolved_at?->toDateTimeString()),
            'severity' => $payload['severity'] ?? null,
            'priority' => $payload['priority'] ?? null,
            'reporter_name' => $payload['reporter_name'] ?? null,
            'department' => $payload['department'] ?? null,
            'description' => $payload['incident_description'] ?? ($payload['screenshot'] ?? ''),
            'image' => $payload['proof_image_url'] ?? $payload['proofImageUrl'] ?? $payload['image_url'] ?? null,
            'latitude' => $lat,
            'longitude' => $lng,
            'db' => $incident,
            'responders' => $responders,
            'leadResponder' => $leadResponder,
            'additionalResponders' => $additionalResponders,
            'notes' => $notes,
            'timeline' => $timeline,
        ];

        // Render a dedicated Blade template for single incident
        $html = View::make('incidents.single-report', [
            'incident' => $data,
        ])->render();

        $pdf = Browsershot::html($html)
            ->setOption('args', ['--no-sandbox'])
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->waitUntilNetworkIdle()
            ->setDelay(600)
            ->pdf();

        $fname = 'incident-' . ($data['id'] ?? 'report') . '-' . now()->format('Ymd_His') . '.pdf';
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fname . '"');
    }
}
