<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Site;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function installStatus(Request $request)
    {
        $validated = $request->validate([
            'site_url' => 'required|url',
            'site_name' => 'nullable|string',
            'version' => 'required|string',
            'status' => 'required|in:success,failed',
            'error' => 'nullable|string',
        ]);

        // Find or create the site based on URL
        $site = Site::firstOrCreate(
            ['url' => $validated['site_url']],
            [
                'name' => $validated['site_name'] ?? null,
                'installation_key' => Str::random(32), // Generate a unique key for new sites
            ]
        );

        // Update the site details
        $site->last_checked_at = now();
        if ($validated['status'] === 'success') {
            $site->current_version = $validated['version'];
        }
        $site->save();

        // Log the update event
        $site->updateLogs()->create([
            'target_version' => $validated['version'],
            'status' => $validated['status'],
            'error_message' => $validated['error'] ?? null,
        ]);

        return response()->json(['message' => 'Status recorded successfully']);
    }
}
