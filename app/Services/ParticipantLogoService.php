<?php

namespace App\Services;

use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParticipantLogoService
{
    public function store(UploadedFile $logo): string
    {
        $temporaryPath = $logo->getPathname();

        if ($temporaryPath === '' || ! is_readable($temporaryPath)) {
            throw ValidationException::withMessages([
                'logo' => 'The uploaded logo could not be read. Please select the file again.',
            ]);
        }

        if (strtolower($logo->getClientOriginalExtension()) !== 'svg') {
            $extension = strtolower($logo->extension() ?: $logo->getClientOriginalExtension());
            $path = 'logos/'.Str::uuid().'.'.$extension;
            $stream = fopen($temporaryPath, 'rb');

            try {
                $stored = is_resource($stream)
                    && Storage::disk('public')->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if (! $stored) {
                throw ValidationException::withMessages([
                    'logo' => 'The logo could not be stored. Please try again.',
                ]);
            }

            return $path;
        }

        $svg = file_get_contents($temporaryPath);

        try {
            $sanitized = is_string($svg) ? (new Sanitizer)->sanitize($svg) : false;
        } catch (\Throwable) {
            $sanitized = false;
        }

        if (! is_string($sanitized) || trim($sanitized) === '') {
            throw ValidationException::withMessages([
                'logo' => 'The SVG logo contains unsafe or invalid content.',
            ]);
        }

        $path = 'logos/'.Str::uuid().'.svg';

        if (! Storage::disk('public')->put($path, $sanitized)) {
            throw ValidationException::withMessages([
                'logo' => 'The logo could not be stored. Please try again.',
            ]);
        }

        return $path;
    }
}
