<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentConfigController extends Controller
{
    public function show(Request $request, string $uuid)
    {
        $agent = Agent::byUuid($uuid);

        $poll = config('flir.poll_interval', 1);
        $status = config('flir.status_interval', 15);

        $cameras = $agent->cameras()
            ->where('enabled', true)
            ->orderBy('label')
            ->get(['id', 'label', 'ip', 'api_key', 'enabled'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->label,
                'ip' => $c->ip,
                'api_key' => $c->api_key, // decrypted by cast
                'enabled' => (bool) $c->enabled,
            ]);

        return response()->json([
            'agent' => ['uuid' => $agent->uuid, 'name' => $agent->name],
            'poll_interval' => (int) $poll,
            'status_interval' => (int) $status,
            'cameras' => $cameras,
        ]);
    }
}
