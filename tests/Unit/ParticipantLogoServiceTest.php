<?php

namespace Tests\Unit;

use App\Services\ParticipantLogoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParticipantLogoServiceTest extends TestCase
{
    public function test_it_stores_a_raster_logo_using_its_temporary_path(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('faculty.png', 100, 100);

        $path = app(ParticipantLogoService::class)->store($logo);

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function test_it_sanitizes_and_stores_a_safe_svg_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->createWithContent(
            'faculty.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="8"/></svg>'
        );

        $path = app(ParticipantLogoService::class)->store($logo);

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.svg', $path);
    }

    public function test_it_removes_executable_content_from_svg_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->createWithContent(
            'unsafe.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script><circle r="8"/></svg>'
        );

        $path = app(ParticipantLogoService::class)->store($logo);
        $stored = Storage::disk('public')->get($path);

        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
    }

    public function test_it_rejects_an_svg_with_no_safe_content(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->createWithContent('invalid.svg', '<script>alert(1)</script>');

        $this->expectException(ValidationException::class);

        app(ParticipantLogoService::class)->store($logo);
    }

    public function test_laravel_validation_accepts_an_svg_logo(): void
    {
        $logo = UploadedFile::fake()->createWithContent(
            'faculty.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><circle r="8"/></svg>'
        );

        $validator = Validator::make(
            ['logo' => $logo],
            ['logo' => [File::image(allowSvg: true)->max('2mb')]]
        );

        $this->assertTrue($validator->passes(), $validator->errors()->first('logo'));
    }
}
