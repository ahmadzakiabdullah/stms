<?php

namespace App\Logging;

use Monolog\LogRecord;

/**
 * Redacts sensitive request and domain values before they reach a log handler.
 */
class RedactSensitiveContext
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(context: $this->redact($record->context));
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private function redact(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/(?:email|e_mail|phone|telephone|mobile|password|token|secret|cookie|authorization|remember[_-]?token)/i', $key) === 1;
    }
}
