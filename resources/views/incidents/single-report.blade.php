<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Incident Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            margin: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header-logos {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .header-logo {
            height: 60px;
            width: auto;
        }

        .header-center {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }

        .header-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.3;
            margin: 0;
            color: #1e293b;
        }

        .meta {
            font-size: 12px;
            color: #334155;
        }

        .section {
            margin: 12px 0;
        }

        .section h3 {
            margin: 0 0 6px 0;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 11px;
            border: 1px solid #cbd5e1;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 4px;
        }

        .table th,
        .table td {
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
        }

        .muted {
            color: #64748b;
        }

        .img {
            width: 100%;
            max-height: 360px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }

        .footer {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 2px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-logos">
            <img src="{{ public_path('logo/Ph_seal_Malabon.png') }}" alt="Malabon Seal" class="header-logo">
            <img src="{{ public_path('logo/baritan-logo.png') }}" alt="Baritan Logo" class="header-logo">
        </div>
        <div class="header-center">
            <div class="header-title">AI‑Based Incident Report and Management System for Barangay Baritan, Malabon City
            </div>
        </div>
        <div class="header-logos">
            <img src="{{ public_path('logo/bagong-pilipinas-logo.png') }}" alt="Bagong Pilipinas" class="header-logo">
            <img src="{{ public_path('logo/cmu-favicon.ico') }}" alt="CMU Logo" class="header-logo">
        </div>
    </div>

    <div class="section" style="background:#f8fafc; padding:10px; border-radius:6px; border-left:4px solid #3b82f6;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <h3 style="margin:0; border:none; padding:0;">Incident Summary</h3>
            <div class="meta" style="font-weight:600;">Generated: {{ now()->format('M d, Y H:i') }}</div>
        </div>
        <p class="meta" style="line-height:1.5; margin:0 0 6px 0;">
            This report is type <strong>{{ ucfirst(str_replace('_',' ',$incident['type'] ?? 'unknown')) }}</strong>
            from <strong>{{ $incident['location'] ?? 'Unknown' }}</strong>
            that happened on <strong>{{ \Carbon\Carbon::parse($incident['timestamp'] ?? now())->format('M d, Y H:i')
                }}</strong>
            and was resolved at <strong>{{ \Carbon\Carbon::parse($incident['resolved_at'] ?? now())->format('M d, Y
                H:i') }}</strong>.
            The current status is <strong>{{ ucfirst(str_replace('_',' ',$incident['status'] ?? 'unknown')) }}</strong>.
        </p>
        @php
        $lead = $incident['leadResponder'] ?? null;
        $adds = $incident['additionalResponders'] ?? collect();
        @endphp
        <p class="meta" style="margin:0; line-height:1.5;">
            @if($lead)
            Admin chose <strong>{{ $lead->name }}</strong>@if(!empty($lead->responder_type)) ({{ $lead->responder_type
            }})@endif as the lead responder
            @if($adds && $adds->count() > 0)
            and added additional responder{{ $adds->count() > 1 ? 's' : '' }}
            {{ $adds->map(function($r){ return $r->name . (!empty($r->responder_type) ? ' ('.$r->responder_type.')' :
            ''); })->implode(', ') }}
            to help resolve the incident quickly and minimize casualties in the area.
            @else
            to lead the response and ensure a quick and safe resolution.
            @endif
            @else
            Responders were dispatched to lead and assist in resolving the incident promptly and safely.
            @endif
        </p>
    </div>

    <div class="grid" style="margin-top:12px;">
        <!-- Left Column: Incident Details & Responders -->
        <div>
            <div class="section" style="margin:0;">
                <h3>Incident Details</h3>
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width:40%;">ID</th>
                            <td>{{ $incident['id'] }}</td>
                        </tr>
                        <tr>
                            <th>Source</th>
                            <td>{{ strtoupper($incident['source'] ?? 'N/A') }}</td>
                        </tr>
                        @if(strtolower($incident['source'] ?? '') !== 'cctv')
                        <tr>
                            <th>Severity</th>
                            <td>{{ $incident['severity'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Priority</th>
                            <td>{{ $incident['priority'] ?? 'N/A' }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Reporter</th>
                            <td>{{ $incident['reporter_name'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Department</th>
                            <td>{{ $incident['department'] ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>Responders Dispatched</h3>
                @if(($incident['responders'] ?? collect())->count() === 0)
                <div class="muted">No responders recorded.</div>
                @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incident['responders'] as $r)
                        <tr>
                            <td>{{ $r->name }}</td>
                            <td>{{ $r->responder_type ?? 'N/A' }}</td>
                            <td>{{ ucfirst(str_replace('_',' ',$r->status ?? 'dispatched')) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            @php
            $hasLat = isset($incident['latitude']) && is_numeric($incident['latitude']);
            $hasLng = isset($incident['longitude']) && is_numeric($incident['longitude']);
            $hasAddress = !empty($incident['location']);
            @endphp
            @if(($hasLat && $hasLng) || $hasAddress)
            <div class="section">
                <h3>Location Map</h3>
                @php
                $mapboxToken = env('MAPBOX_ACCESS_TOKEN',
                'pk.eyJ1IjoiZGplbnRsZW8iLCJhIjoiY21mNnoxMDgzMGt3NjJyb20zY3dqdnRjdSJ9.OKI8RAGo7e9eRRXejMLfOA');

                if ($hasLat && $hasLng) {
                // Use coordinates directly
                $lat = $incident['latitude'];
                $lng = $incident['longitude'];
                $mapUrl =
                "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+ff0000({$lng},{$lat})/{$lng},{$lat},15,0/400x200@2x?access_token={$mapboxToken}";
                } else {
                // Geocode address to get coordinates
                $address = $incident['location'] . ', Malabon City, Philippines';
                $geocodeUrl = "https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode($address) .
                ".json?access_token={$mapboxToken}&limit=1";

                try {
                $geocodeResponse = @file_get_contents($geocodeUrl);
                if ($geocodeResponse) {
                $geocodeData = json_decode($geocodeResponse, true);
                if (!empty($geocodeData['features'][0]['center'])) {
                $lng = $geocodeData['features'][0]['center'][0];
                $lat = $geocodeData['features'][0]['center'][1];
                $mapUrl =
                "https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+ff0000({$lng},{$lat})/{$lng},{$lat},15,0/400x200@2x?access_token={$mapboxToken}";
                } else {
                $mapUrl = null;
                }
                } else {
                $mapUrl = null;
                }
                } catch (\Exception $e) {
                $mapUrl = null;
                }
                }
                @endphp
                @if($mapUrl)
                <div style="background:#f8fafc; padding:4px; border-radius:6px; border:1px solid #e2e8f0;">
                    <img src="{{ $mapUrl }}" alt="Location Map"
                        style="width:100%; max-width:400px; height:auto; border-radius:4px;"
                        onerror="this.nextElementSibling.style.display='block'; this.style.display='none'">
                    <div class="muted" style="display:none; font-size:10px; margin-top:4px;">Map unavailable.</div>
                </div>
                @else
                <div class="muted" style="font-size:11px; padding:8px;">Unable to generate map for this location.</div>
                @endif
            </div>
            @endif
        </div>

        <!-- Right Column: Evidence & Description -->
        <div>
            @if(!empty($incident['image']))
            <div class="section" style="margin:0 0 10px 0;">
                <h3>Evidence</h3>
                @php
                $imgSrc = $incident['image'];
                if (filter_var($imgSrc, FILTER_VALIDATE_URL)) {
                $imgPath = $imgSrc;
                } else {
                $imgPath = $imgSrc;
                }
                @endphp
                <div
                    style="text-align:center; background:#f8fafc; padding:6px; border-radius:6px; border:1px solid #e2e8f0;">
                    <img class="img" src="{{ $imgPath }}" alt="Incident Evidence" onerror="this.style.display='none'"
                        style="max-height:240px; width:auto;">
                </div>
            </div>
            @endif

            <div class="section" style="margin:0;">
                <h3>Description</h3>
                <div
                    style="background:#f8fafc; padding:8px; border-radius:6px; border:1px solid #e2e8f0; min-height:60px;">
                    <p class="meta" style="margin:0; line-height:1.4;">{{ $incident['description'] ?
                        strip_tags($incident['description']) : 'No description available.' }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(($incident['notes'] ?? collect())->count() > 0)
    <div class="section">
        <h3>Notes</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Note</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($incident['notes'] as $n)
                <tr>
                    <td>{{ $n->user->name ?? 'Unknown' }}</td>
                    <td>{{ $n->note }}</td>
                    <td>{{ $n->created_at?->format('M d, Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Prepared By and Signature Section -->
    <div style="display:flex; justify-content:flex-end; margin-top:14px; padding:12px; ">
        <div style="text-align:left; min-width:240px;">
            <div style="margin-bottom:10px;">
                <div class="meta" style="font-weight:600; margin-bottom:3px;">Prepared by:</div>
                <div
                    style="border-bottom:1px solid #0f172a; padding-bottom:2px; min-width:190px; text-transform: uppercase; text-align:center;">
                    <span class="meta">{{ Auth::user()->name ?? 'System Administrator' }}</span>
                </div>
            </div>
            <div>
                <div class="meta" style="font-weight:600; margin-bottom:3px;">Signature:</div>
                <div style="border-bottom:1px solid #0f172a; padding-bottom:2px; min-width:190px; height:35px;"></div>
            </div>
        </div>
    </div>

    <div class="footer">This is a system-generated report. Barangay Baritan, Malabon City.</div>
</body>

</html>