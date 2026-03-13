<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Site;
use App\Models\SiteUpdateEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpdateController extends Controller
{
    /**
     * GET /laraupdater.json
     * Returns the active release manifest and optionally logs a manifest_check event.
     */
    public function metadata(Request $request)
    {
        $release = Release::where('is_active', true)
            ->latest('published_at')
            ->first();

        if (!$release) {
            return response()->json(['error' => 'No active release found'], 404);
        }

        // Log manifest check if siteid provided and site exists
        $siteid = $request->query('siteid');
        if ($siteid) {
            $site = Site::find($siteid);
            if ($site) {
                $event = SiteUpdateEvent::create([
                    'siteid'          => $siteid,
                    'event_type'      => 'manifest_check',
                    'status'          => 'checked',
                    'current_version' => $request->query('current_version'),
                    'target_version'  => $release->version,
                    'source_ip'       => $request->ip(),
                    'payload_json'    => $request->query(),
                ]);

                $site->update([
                    'last_checked_at' => now(),
                    'last_status'     => 'checked',
                    'last_event_id'   => $event->id,
                ]);
            }
        }

        $payload = [
            'version'     => $release->version,
            'archive'     => $release->archive_name,
            'description' => $release->notes ?? '',
        ];

        if ($release->sha256) {
            $payload['sha256'] = $release->sha256;
        }
        if ($release->size_bytes) {
            $payload['size_bytes'] = $release->size_bytes;
        }
        if ($release->published_at) {
            $payload['published_at'] = $release->published_at->toIso8601String();
        }
        if ($release->minimum_required_version) {
            $payload['minimum_supported_version'] = $release->minimum_required_version;
        }

        return response()->json($payload);
    }

    /**
     * GET /RELEASE-{version}.zip
     * Streams the release archive with Range (resumable download) support.
     */
    public function download(Request $request, string $version)
    {
        $release = Release::where('version', $version)->first();

        if (!$release || !Storage::exists($release->file_path)) {
            abort(404, 'Release not found');
        }

        $fullPath  = Storage::path($release->file_path);
        $fileSize  = filesize($fullPath);
        $mimeType  = 'application/zip';
        $fileName  = $release->archive_name;

        // Range support for resumable downloads
        $start = 0;
        $end   = $fileSize - 1;
        $status = 200;

        $headers = [
            'Content-Type'              => $mimeType,
            'Content-Disposition'       => "attachment; filename=\"{$fileName}\"",
            'Accept-Ranges'             => 'bytes',
            'Content-Length'            => $fileSize,
        ];

        if ($request->hasHeader('Range')) {
            preg_match('/bytes=(\d*)-(\d*)/', $request->header('Range'), $matches);
            $start  = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end    = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
            $length = $end - $start + 1;

            $headers['Content-Range']  = "bytes {$start}-{$end}/{$fileSize}";
            $headers['Content-Length'] = $length;
            $status = 206;
        }

        $chunkStart = $start;
        $chunkEnd   = $end;

        return new StreamedResponse(function () use ($fullPath, $chunkStart, $chunkEnd) {
            $handle = fopen($fullPath, 'rb');
            fseek($handle, $chunkStart);
            $remaining = $chunkEnd - $chunkStart + 1;
            while ($remaining > 0 && !feof($handle)) {
                $chunk = fread($handle, min(8192, $remaining));
                echo $chunk;
                $remaining -= strlen($chunk);
                flush();
            }
            fclose($handle);
        }, $status, $headers);
    }
}
