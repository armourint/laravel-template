<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AgentHeartbeatController extends Controller
{
    public function store(Request $request, string $uuid)
    {
        $agent = Agent::byUuid($uuid);

        $data = $request->validate([
            'version' => ['nullable', 'string', 'max:50'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'watchers' => ['nullable', 'integer', 'min:0'],
            'statuses' => ['nullable', 'array'],
        ]);

        $agent->fill([
            'version' => $data['version'] ?? $agent->version,
            'hostname' => $data['hostname'] ?? $agent->hostname,
            'watcher_count' => $data['watchers'] ?? $agent->watcher_count,
            'last_payload' => $data['statuses'] ?? $agent->last_payload,
            'last_seen_at' => Carbon::now(),
        ])->save();

        return response()->json(['ok' => true]);
    }
}
