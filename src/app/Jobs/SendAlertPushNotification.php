<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\DeviceToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAlertPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Alert $alert;

    /**
     * Create a new job instance.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Refresh the Alert instance
        $alert = $this->alert->fresh();

        // Build a list of Expo tokens to notify
        $tokens = DeviceToken::with('user')->get()
            ->filter(function ($dt) use ($alert) {
                if ($dt->user->role === 'admin') {
                    return true;
                }

                return $dt->user->role === 'engineer'
                    && $dt->user->sites->contains($alert->site_id);
            })
            ->pluck('expo_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        // Chunk into groups of 100
        foreach (array_chunk($tokens, 100) as $batch) {
            $messages = array_map(fn ($t) => [
                'to' => $t,
                'sound' => 'default',
                'title' => "🔥 New Alert: {$alert->label}",
                'body' => "Temperature {$alert->maxT}° exceeded at {$alert->camera_ip}",
                'data' => ['alert_id' => $alert->id],
            ], $batch);

            try {
                // POST array of messages to Expo Push API
                $response = Http::post('https://exp.host/--/api/v2/push/send', $messages);
                Log::info('Expo push response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if (! $response->successful()) {
                    Log::error('Expo push failure', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Push HTTP error: '.$e->getMessage());
            }
        }
    }
}
