<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Serves files from the public disk. Many shared hosts block symlink(), so
 * `php artisan storage:link` isn't an option — this route replaces it.
 */
class MediaController extends Controller
{
    public function __invoke(string $path)
    {
        $path = ltrim(str_replace(['..', '\\'], '', $path), '/');
        $disk = Storage::disk('public');
        abort_unless($path !== '' && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
