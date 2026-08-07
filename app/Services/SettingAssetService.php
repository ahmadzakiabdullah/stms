<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

final class SettingAssetService
{
    public function __construct(private readonly SafePublicAssetService $assets) {}

    public function store(UploadedFile $file): string
    {
        return $this->assets->store($file, 'settings', 'asset');
    }
}
