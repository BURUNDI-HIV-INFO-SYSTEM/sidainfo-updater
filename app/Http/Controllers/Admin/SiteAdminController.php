<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteUpdateEvent;
use Illuminate\Http\Request;

class SiteAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Site::query();

        if ($request->filled('province')) {
            $query->where('province', $request->input('province'));
        }
        if ($request->filled('district')) {
            $query->where('district', $request->input('district'));
        }
        if ($request->filled('status')) {
            $s = $request->input('status');
            if ($s === 'never') {
                $query->whereNull('last_checked_at');
            } else {
                $query->where('last_status', $s);
            }
        }
        if ($request->filled('version')) {
            $query->where('current_version', $request->input('version'));
        }
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('siteid', 'like', $term)
                  ->orWhere('site_name', 'like', $term);
            });
        }

        $sites = $query->orderBy('province')->orderBy('district')->orderBy('site_name')->paginate(50)->withQueryString();

        $provinces = Site::distinct()->orderBy('province')->pluck('province')->filter()->values();
        $versions  = Site::distinct()->whereNotNull('current_version')->orderBy('current_version')->pluck('current_version')->values();

        return view('sites.index', compact('sites', 'provinces', 'versions'));
    }

    public function show(Site $site)
    {
        $events = SiteUpdateEvent::where('siteid', $site->siteid)
            ->latest('created_at')
            ->paginate(30);

        return view('sites.show', compact('site', 'events'));
    }
}
