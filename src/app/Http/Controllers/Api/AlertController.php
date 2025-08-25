<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendAlertPushNotification;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * GET /api/alerts
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $statuses = $request->input('status', []);

        if (is_string($statuses)) {
            $statuses = array_filter(explode(',', $statuses));
        }

        $perPage = min($request->integer('per_page', 10), 200);

        // Apply visibility scope here
        $alerts = Alert::visibleTo($request->user())
            ->with(['acknowledgedByUser', 'resolvedByUser'])
            ->when($search, function ($q, $term) {
                $like = "%{$term}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('camera_ip', 'like', $like)
                        ->orWhere('label', 'like', $like)
                        ->orWhere('maxT', 'like', $like)
                        ->orWhere('box_id', 'like', $like)
                        ->orWhere('triggered_at', 'like', $like);
                });
            })
            ->when($statuses, fn ($q) => $q->whereIn('status', $statuses))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($alerts);
    }

    /**
     * POST /api/alerts
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'camera_ip' => 'required|ip',
            'label' => 'required|string|max:255',
            'maxT' => 'required|numeric',
            'box_id' => 'required|integer',
        ]);

        // Split the label by colon
        $labelParts = explode(':', $data['label'], 2);

        if (count($labelParts) === 2) {
            [$siteCode, $alertLabel] = $labelParts;

            // Try to find a site with this code
            $site = \App\Models\Site::where('code', $siteCode)->first();

            $siteId = $site?->id ?? 0;
            $label = $alertLabel;
        } else {
            // No colon found; use fallback values
            $siteId = 0;
            $label = $data['label'];
        }

        $alert = Alert::create([
            'camera_ip' => $data['camera_ip'],
            'label' => $data['label'],
            'maxT' => $data['maxT'],
            'box_id' => $data['box_id'],
            'triggered_at' => now(),
            'status' => Alert::STATUS_PENDING,
            'site_id' => $siteId,
        ]);

        // Dispatch push-notification to queue
        SendAlertPushNotification::dispatch($alert);

        return response()->json($alert, 201);
    }

    /**
     * POST /api/alerts/{alert}/acknowledge
     */
    public function acknowledge(Alert $alert)
    {
        $alert->update([
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        return response()->noContent();
    }

    /**
     * PATCH /api/alerts/{alert}/status
     */
    public function updateStatus(Request $request, Alert $alert)
    {
        $request->validate([
            'status' => 'required|in:Pending,Acknowledged,Resolved',
        ]);

        $alert->update(['status' => $request->status]);

        // Eager‑load the related users now that status (and by extension
        // acknowledged_by / resolved_by) may have changed.
        $alert->load(['acknowledgedByUser', 'resolvedByUser']);

        return response()->json([
            'ok' => true,
            'alert' => $alert,
        ]);
    }
}
