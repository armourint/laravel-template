<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CameraController extends Controller
{
    /**
     * GET /api/cameras
     * Filters: q (label/ip), agent_uuid, enabled (0/1), per_page
     * By default hides api_key; include it with ?with_key=1
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $agentUuid = $request->query('agent_uuid');
        $enabled = $request->has('enabled') ? (bool) $request->boolean('enabled') : null;
        $perPage = (int) $request->integer('per_page', 15);
        $withKey = $request->boolean('with_key', false);

        $cameras = Camera::query()
            ->when($q !== '', function ($s) use ($q) {
                $s->where(function ($x) use ($q) {
                    $x->where('label', 'like', "%{$q}%")
                        ->orWhere('ip', 'like', "%{$q}%");
                });
            })
            ->when($agentUuid, fn ($s) => $s->where('assigned_agent_uuid', $agentUuid))
            ->when(! is_null($enabled), fn ($s) => $s->where('enabled', $enabled))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $payload = $cameras->through(function (Camera $c) use ($withKey) {
            return [
                'id' => $c->id,
                'label' => $c->label,
                'ip' => $c->ip,
                'enabled' => (bool) $c->enabled,
                'assigned_agent_uuid' => $c->assigned_agent_uuid,
                'status' => $c->status,
                'notes' => $c->notes,
                // Only include the decrypted api_key if explicitly requested
                'api_key' => $withKey ? $c->api_key : null,
            ];
        });

        return response()->json($payload);
    }

    /**
     * GET /api/cameras/{camera}
     * By default hides api_key; include it with ?with_key=1
     */
    public function show(Request $request, Camera $camera)
    {
        $withKey = $request->boolean('with_key', false);

        return response()->json([
            'id' => $camera->id,
            'label' => $camera->label,
            'ip' => $camera->ip,
            'enabled' => (bool) $camera->enabled,
            'assigned_agent_uuid' => $camera->assigned_agent_uuid,
            'status' => $camera->status,
            'notes' => $camera->notes,
            'api_key' => $withKey ? $camera->api_key : null,
        ]);
    }

    /**
     * POST /api/cameras
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'ip'],
            'api_key' => ['required', 'string'],
            'enabled' => ['sometimes', 'boolean'],
            'assigned_agent_uuid' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $camera = Camera::create([
            'label' => $data['label'],
            'ip' => $data['ip'],
            'api_key' => $data['api_key'], // encrypted by cast
            'enabled' => (bool) ($data['enabled'] ?? true),
            'assigned_agent_uuid' => $data['assigned_agent_uuid'] ?? null,
            'status' => $data['status'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'id' => $camera->id,
        ], 201);
    }

    /**
     * PUT/PATCH /api/cameras/{camera}
     * api_key is optional; only updated if provided and not empty.
     */
    public function update(Request $request, Camera $camera)
    {
        $rules = [
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'ip' => ['sometimes', 'required', 'ip'],
            'enabled' => ['sometimes', 'boolean'],
            'assigned_agent_uuid' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];

        // Only validate api_key if present in payload
        if ($request->has('api_key')) {
            $rules['api_key'] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        // Preserve existing api_key unless explicitly provided (even empty to clear)
        if (! array_key_exists('api_key', $data)) {
            // no-op
        } else {
            // If they explicitly send empty string, keep existing (avoid accidental wipe)
            if ($data['api_key'] === '' || $data['api_key'] === null) {
                unset($data['api_key']);
            }
        }

        $camera->update($data);

        return response()->json(['ok' => true]);
    }

    /**
     * DELETE /api/cameras/{camera}
     */
    public function destroy(Camera $camera)
    {
        $camera->delete();

        return response()->json(['ok' => true]);
    }
}
