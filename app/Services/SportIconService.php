<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class SportIconService
{
    private const DISK = 'public';

    private const PUBLIC_PREFIX = '/storage/';

    public function store(UploadedFile $file): string
    {
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to open the uploaded sport icon.');
        }

        try {
            $path = 'sport-icons/'.$file->hashName();

            if (! Storage::disk(self::DISK)->put($path, $stream)) {
                throw new RuntimeException('Unable to store the uploaded sport icon.');
            }
        } finally {
            fclose($stream);
        }

        return Storage::disk(self::DISK)->url($path);
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
