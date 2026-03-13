<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Models\Site;
use App\Models\SiteUpdateEvent;

class DashboardController extends Controller
{
    public function index()
    {
        $latestRelease = Release::where('is_active', true)->latest('published_at')->first();

        $totalSites      = Site::count();
        $onLatestVersion = $latestRelease
            ? Site::where('current_version', $latestRelease->version)->count()
            : 0;
        $failedSites = Site::where('last_status', 'failed')->count();
        $neverReported = Site::whereNull('last_checked_at')->count();

        // Version adoption breakdown
        $adoption = Site::selectRaw('current_version, count(*) as site_count')
            ->whereNotNull('current_version')
            ->groupBy('current_version')
            ->orderByDesc('site_count')
            ->get();

        $recentEvents = SiteUpdateEvent::with('site')
            ->latest('created_at')
            ->limit(25)
            ->get();

        return view('dashboard', compact(
            'latestRelease',
            'totalSites',
            'onLatestVersion',
            'failedSites',
            'neverReported',
            'adoption',
            'recentEvents'
        ));
    }
}
