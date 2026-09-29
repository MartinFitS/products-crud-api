<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API working correctly:)',
            'data' => [
                'service' => 'TAP Admission test API.',
            ],
        ]);
    }
}
