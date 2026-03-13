<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Release;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function metadata()
    {
        $release = Release::where('is_active', true)->orderBy('release_date', 'desc')->first();

        if (!$release) {
            return response()->json(['error' => 'No active release found'], 404);
        }

        return response()->json([
            'version' => $release->version_number,
            'date' => $release->release_date->format('Y-m-d'),
            'require' => $release->minimum_required_version,
            'archive' => url("/updates/{$release->version_number}.zip")
        ]);
    }

    public function download($version)
    {
        $release = Release::where('version_number', $version)->first();

        if (!$release || !Storage::exists($release->file_path)) {
            abort(404, 'Release file not found');
        }

        return Storage::download($release->file_path, "{$version}.zip");
    }
}
