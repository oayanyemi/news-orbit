<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function show(string $clientId): JsonResponse
    {
        $preference = UserPreference::query()->firstOrCreate(
            ['client_id' => $clientId],
            ['sources' => [], 'categories' => [], 'authors' => []]
        );

        return response()->json($preference);
    }

    public function update(Request $request, string $clientId): JsonResponse
    {
        $validated = $request->validate([
            'sources' => ['sometimes', 'array'],
            'sources.*' => ['string', 'max:100'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['string', 'max:100'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['string', 'max:255'],
        ]);

        $preference = UserPreference::query()->updateOrCreate(
            ['client_id' => $clientId],
            [
                'sources' => $validated['sources'] ?? [],
                'categories' => $validated['categories'] ?? [],
                'authors' => $validated['authors'] ?? [],
            ]
        );

        return response()->json($preference);
    }
}
