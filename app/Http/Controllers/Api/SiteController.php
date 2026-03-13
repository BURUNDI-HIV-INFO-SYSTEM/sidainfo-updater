<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteUpdateEvent;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * POST /api/site-install-status
     * Accepts installation status callbacks from site instances.
     * Authenticated by Bearer token (LARAUPDATER_STATUS_REPORT_TOKEN).
     */
    public function installStatus(Request $request)
    {
        // Bearer token authentication
        $expectedToken = config('laraupdater.status_report_token');
        if ($expectedToken) {
            $bearer = $request->bearerToken();
            if (!$bearer || !hash_equals($expectedToken, $bearer)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $validated = $request->validate([
            'siteid'          => 'required|string|max:20',
            'status'          => 'required|string|in:installed,failed,checked,unknown',
            'message'         => 'nullable|string|max:1000',
            'current_version' => 'nullable|string|max:50',
            'target_version'  => 'nullable|string|max:50',
            'archive'         => 'nullable|string|max:255',
            'checked_at'      => 'nullable|date',
            'installed_at'    => 'nullable|date',
        ]);

        // Find site — only accept known sites
        $site = Site::find($validated['siteid']);
        if (!$site) {
            return response()->json(['error' => 'Unknown site'], 422);
        }

        $event = SiteUpdateEvent::create([
            'siteid'          => $site->siteid,
            'event_type'      => 'install_report',
            'status'          => $validated['status'],
            'current_version' => $validated['current_version'] ?? null,
            'target_version'  => $validated['target_version'] ?? null,
            'archive'         => $validated['archive'] ?? null,
            'message'         => $validated['message'] ?? null,
            'source_ip'       => $request->ip(),
            'payload_json'    => $validated,
        ]);

        // Update materialized status on sites row
        $update = [
            'last_status'   => $validated['status'],
            'last_event_id' => $event->id,
        ];

        if (!empty($validated['checked_at'])) {
            $update['last_checked_at'] = $validated['checked_at'];
        } elseif (in_array($validated['status'], ['checked', 'installed', 'failed'])) {
            $update['last_checked_at'] = now();
        }

        if ($validated['status'] === 'installed') {
            $update['current_version']    = $validated['current_version'] ?? $validated['target_version'];
            $update['last_installed_at']  = $validated['installed_at'] ?? now();
        }

        $site->update($update);

        return response()->json(['ok' => true], 201);
    }
}
