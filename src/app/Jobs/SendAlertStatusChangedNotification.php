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

class SendAlertStatusChangedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Alert $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function handle(): void
    {
        $alert = $this->alert->fresh();

        // Gather recipient tokens
        $tokens = DeviceToken::with('user')->get()
            ->filter(fn ($dt) => $dt->user->role === 'admin'
                || ($dt->user->role === 'engineer' && $dt->user->sites->contains($alert->site_id))
            )
            ->pluck('expo_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $title = "🔁 Alert “{$alert->label}” status changed";
        $body = "New status: {$alert->status}";

        foreach (array_chunk($tokens, 100) as $batch) {
            $messages = array_map(fn ($t) => [
                'to' => $t,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => ['alert_id' => $alert->id],
            ], $batch);

            try {
                $response = Http::post('https://exp.host/--/api/v2/push/send', $messages);
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
