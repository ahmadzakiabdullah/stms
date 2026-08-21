<?php

namespace Tests\Unit;

use App\Logging\RedactSensitiveContext;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class RedactSensitiveContextTest extends TestCase
{
    public function test_sensitive_context_values_are_redacted_recursively(): void
    {
        $record = new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'test',
            level: Level::Info,
            message: 'user event',
            context: [
                'email' => 'person@example.test',
                'organization_id' => 'organization-1',
                'payload' => [
                    'phone' => '+60123456789',
                    'participant_id' => 'participant-1',
                ],
            ],
        );

        $redacted = (new RedactSensitiveContext)($record);

        $this->assertSame('[REDACTED]', $redacted->context['email']);
        $this->assertSame('[REDACTED]', $redacted->context['payload']['phone']);
        $this->assertSame('organization-1', $redacted->context['organization_id']);
        $this->assertSame('participant-1', $redacted->context['payload']['participant_id']);
    }
}
