<?php

namespace App\Observers;

use App\Jobs\SendAlertStatusChangedNotification;
use App\Models\Alert;
use Illuminate\Support\Facades\Auth;

class AlertObserver
{
    /**
     * Handle the Alert “updated” event.
     */
    public function updated(Alert $alert): void
    {

        // Only act when the status really changed
        if (! $alert->isDirty('status')) {
            return;
        }

        // Only fire when the `status` field actually changed
        if ($alert->isDirty('status')) {
            SendAlertStatusChangedNotification::dispatch($alert);
        }

        // If it just became Acknowledged, stamp the user & time
        if ($alert->status === Alert::STATUS_ACKNOWLEDGED) {
            // saveQuietly so we don't re‑fire this observer
            $alert->forceFill([
                'acknowledged_by' => Auth::id(),
                'acknowledged_at' => now(),
            ])->saveQuietly();
        }

        // If it became Resolved, set resolved fields
        if ($alert->status === Alert::STATUS_RESOLVED) {
            $alert->forceFill([
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
            ])->saveQuietly();
        }

    }
}
