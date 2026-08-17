<?php

namespace Tests\Unit;

use App\Http\Requests\Participant\StoreParticipantRequest;
use App\Http\Requests\Participant\UpdateParticipantRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ParticipantRequestNormalizationTest extends TestCase
{
    public static function requestClasses(): array
    {
        return [
            [StoreParticipantRequest::class],
            [UpdateParticipantRequest::class],
        ];
    }

    #[DataProvider('requestClasses')]
    public function test_it_normalizes_form_data_boolean_strings(string $requestClass): void
    {
        $request = $requestClass::create('/', 'POST', [
            'organization_id' => '019fa709-0000-7000-8000-000000000000',
            'is_active' => 'true',
        ]);

        (function (): void {
            $this->prepareForValidation();
        })->call($request);

        $this->assertTrue($request->input('is_active'));
    }

    public function test_update_request_normalizes_logo_removal_flags(): void
    {
        $request = UpdateParticipantRequest::create('/', 'POST', [
            'organization_id' => '019fa709-0000-7000-8000-000000000000',
            'remove_logo' => 'false',
            'remove_inverse_logo' => 'true',
        ]);

        (function (): void {
            $this->prepareForValidation();
        })->call($request);

        $this->assertFalse($request->input('remove_logo'));
        $this->assertTrue($request->input('remove_inverse_logo'));
    }
}
