<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpaceSyncController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $space = $request->attributes->get('space')->fresh();

        return response()->json([
            'space_id' => $space->id,
            'version' => (int) $space->sync_version,
            'family' => $space->type === 'family',
        ])->header('Cache-Control', 'no-store, private');
    }
}
