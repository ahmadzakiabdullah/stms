<?php

namespace App\Imports;

use App\Actions\Participants\RegisterParticipantToEvent;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class EventParticipantImport implements ToCollection, WithCustomCsvSettings, WithHeadingRow
{
    private array $errors = [];

    private int $created = 0;

    public function __construct(
        public Participant $participant,
        public string $organizationId
    ) {}

    public function getCsvSettings(): array
    {
        return ['delimiter' => ',', 'input_encoding' => 'UTF-8'];
    }

    public function collection(Collection $rows): void
    {
        $register = app(RegisterParticipantToEvent::class);

        foreach ($rows as $index => $row) {
            $eventName = trim((string) ($row['event_name'] ?? ''));

            if (empty($eventName)) {
                $this->errors[] = 'Row '.($index + 2).': event name is required.';

                continue;
            }

            $event = Event::query()
                ->where('organization_id', $this->organizationId)
                ->where('name', $eventName)
                ->first();

            if (! $event) {
                $this->errors[] = 'Row '.($index + 2).": event '{$eventName}' was not found.";

                continue;
            }

            try {
                $register->handle($this->participant, $event->id);
                $this->created++;
            } catch (ValidationException $e) {
                $this->errors[] = 'Row '.($index + 2).": {$e->getMessage()}";
            } catch (Throwable) {
                $this->errors[] = 'Row '.($index + 2).": failed to register for '{$eventName}'.";
            }
        }
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
