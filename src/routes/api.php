<?php

use App\Http\Controllers\Api\AgentConfigController;
use App\Http\Controllers\Api\AgentHeartbeatController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\CameraController;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TEMP: bootstrap a super admin (remove in production)
|--------------------------------------------------------------------------
*/
Route::get('/setup-super-admin', function () {
    $user = User::where('email', 'admin@example.com')->first();

    if (! $user) {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // change in prod
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        return response()->json(['message' => 'Super admin created.', 'user' => $user]);
    }

    return response()->json(['message' => 'Super admin already exists.']);
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (! Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();
    $token = $user->createToken('mobile')->plainTextToken;

    // Return RBAC info to the app
    $siteIds = $user->role === 'engineer'
        ? $user->sites()->pluck('sites.id')->all()
        : [];

    return response()->json([
        'token' => $token,
        'user' => $user,
        'role' => $user->role,
        'siteIds' => $siteIds,
    ]);
});

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logged out']);
});

/*
|--------------------------------------------------------------------------
| Admin-only agent endpoints (use same Sanctum auth + IsAdmin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', \App\Http\Middleware\IsAdmin::class])->group(function () {
    Route::get('/agents/{uuid}/config', [AgentConfigController::class, 'show']);
    Route::post('/agents/{uuid}/heartbeat', [AgentHeartbeatController::class, 'store']);

    Route::get('/cameras', [CameraController::class, 'index']);
    Route::post('/cameras', [CameraController::class, 'store']);
    Route::get('/cameras/{camera}', [CameraController::class, 'show']);
    Route::put('/cameras/{camera}', [CameraController::class, 'update']);
    Route::patch('/cameras/{camera}', [CameraController::class, 'update']);
    Route::delete('/cameras/{camera}', [CameraController::class, 'destroy']);
    
});

/*
|--------------------------------------------------------------------------
| Protected API Endpoints (any authenticated user)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts', [AlertController::class, 'store']);
    Route::patch('/alerts/{alert}/status', [AlertController::class, 'updateStatus']);

    // Optional: quick status check
    Route::get('/alerts/{alert}/status', function (Alert $alert) {
        return response()->json(['status' => $alert->status ?? 'unknown']);
    });

    // Expo Push Token registration
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);

    // Health check
    Route::get('/ping', fn () => ['pong' => true]);
});
