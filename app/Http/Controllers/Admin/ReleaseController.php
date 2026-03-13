<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReleaseController extends Controller
{
    public function index()
    {
        $releases = Release::latest('published_at')->get();
        $totalSites = Site::count();

        return view('releases.index', compact('releases', 'totalSites'));
    }

    public function create()
    {
        return view('releases.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'version'                  => 'required|string|max:50|unique:releases,version',
            'zip_file'                 => 'required|file|mimes:zip|max:2097152', // 2 GB
            'notes'                    => 'nullable|string',
            'minimum_required_version' => 'nullable|string|max:50',
            'published_at'             => 'nullable|date',
        ]);

        $file = $request->file('zip_file');
        $version = $request->input('version');
        $archiveName = "RELEASE-{$version}.zip";

        // Store under releases/
        $path = $file->storeAs('releases', $archiveName);

        $sha256    = hash_file('sha256', $file->getRealPath());
        $sizeBytes = $file->getSize();

        Release::create([
            'version'                  => $version,
            'archive_name'             => $archiveName,
            'file_path'                => $path,
            'sha256'                   => $sha256,
            'size_bytes'               => $sizeBytes,
            'minimum_required_version' => $request->input('minimum_required_version'),
            'notes'                    => $request->input('notes'),
            'is_active'                => false,
            'published_at'             => $request->input('published_at') ?? now(),
        ]);

        return redirect()->route('releases.index')
            ->with('success', "Release {$version} uploaded successfully.");
    }

    public function show(Release $release)
    {
        $sites = Site::where('current_version', $release->version)->get();
        $totalSites = Site::count();

        return view('releases.show', compact('release', 'sites', 'totalSites'));
    }

    public function activate(Release $release)
    {
        // Deactivate all, then activate this one
        Release::query()->update(['is_active' => false]);
        $release->update(['is_active' => true]);

        return redirect()->route('releases.index')
            ->with('success', "Release {$release->version} is now the active release.");
    }

    public function destroy(Release $release)
    {
        if ($release->is_active) {
            return back()->with('error', 'Cannot delete the active release.');
        }

        Storage::delete($release->file_path);
        $release->delete();

        return redirect()->route('releases.index')
            ->with('success', "Release {$release->version} deleted.");
    }
}
