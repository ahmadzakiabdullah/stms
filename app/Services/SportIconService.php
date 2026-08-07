<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SportIconService
{
    private const DISK = 'public';

    private const PUBLIC_PREFIX = '/storage/';

    public function __construct(private readonly SafePublicAssetService $assets) {}

    public function store(UploadedFile $file): string
    {
        return $this->assets->store($file, 'sport-icons', 'icon_file');
    }

    /**
     * Delete a previously stored icon. No-ops for external URLs.
     */
    public function delete(string $url): void
    {
        $path = $this->pathFromUrl($url);

        if ($path === null) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($path);
        } catch (Throwable) {
            // Ignore deletion failures; the icon is no longer referenced.
        }
    }

    private function pathFromUrl(string $url): ?string
    {
        if (! str_starts_with($url, self::PUBLIC_PREFIX)) {
            return null;
        }

        return substr($url, strlen(self::PUBLIC_PREFIX));
    }
}
