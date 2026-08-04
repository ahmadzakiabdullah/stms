<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class SettingAssetService
{
    public function store(UploadedFile $file): string
    {
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to open the uploaded settings asset.');
        }

        try {
            $path = 'settings/'.$file->hashName();

            if (! Storage::disk('public')->put($path, $stream)) {
                throw new RuntimeException('Unable to store the uploaded settings asset.');
            }
        } finally {
            fclose($stream);
        }

        return Storage::disk('public')->url($path);
    }
}
