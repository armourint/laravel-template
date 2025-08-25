<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Store or update this user’s Expo push token.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'platform' => 'nullable|string|max:32',
        ]);

        // Upsert by token—attach to current user
        DeviceToken::updateOrCreate(
            ['expo_token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? 'expo',
            ]
        );

        return response()->json(['ok' => true]);
    }
}
