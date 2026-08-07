<?php

namespace App\Services;

use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SafePublicAssetService
{
    public function store(UploadedFile $file, string $directory, string $field): string
    {
        $temporaryPath = $file->getPathname();

        if ($temporaryPath === '' || ! is_readable($temporaryPath)) {
            throw ValidationException::withMessages([$field => 'The uploaded file could not be read.']);
        }

        $isSvg = strtolower($file->getClientOriginalExtension()) === 'svg';
        $extension = $isSvg ? 'svg' : strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;

        if ($isSvg) {
            $source = file_get_contents($temporaryPath);

            try {
                $contents = is_string($source) ? (new Sanitizer)->sanitize($source) : false;
            } catch (\Throwable) {
                $contents = false;
            }

            if (! is_string($contents) || trim($contents) === '') {
                throw ValidationException::withMessages([$field => 'The SVG contains unsafe or invalid content.']);
            }

            $stored = Storage::disk('public')->put($path, $contents);
        } else {
            $stream = fopen($temporaryPath, 'rb');

            try {
                $stored = is_resource($stream) && Storage::disk('public')->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        if (! $stored) {
            throw ValidationException::withMessages([$field => 'The uploaded file could not be stored.']);
        }

        return Storage::disk('public')->url($path);
    }
}
