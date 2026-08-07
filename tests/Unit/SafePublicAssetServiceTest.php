<?php

namespace Tests\Unit;

use App\Services\SafePublicAssetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SafePublicAssetServiceTest extends TestCase
{
    public function test_svg_is_sanitized_and_stored_under_a_generated_name(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent(
            'attacker.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="10" height="10" /></svg>'
        );

        $url = app(SafePublicAssetService::class)->store($file, 'settings', 'asset');
        $path = str($url)->after('/storage/')->toString();

        Storage::disk('public')->assertExists($path);
        $this->assertStringNotContainsString('<script', Storage::disk('public')->get($path));
        $this->assertStringNotContainsString('attacker', $path);
    }

    public function test_invalid_svg_is_rejected(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('invalid.svg', '<script>alert(1)</script>');

        $this->expectException(ValidationException::class);
        app(SafePublicAssetService::class)->store($file, 'settings', 'asset');
    }
}
