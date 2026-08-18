<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowStartResult
{
    private function __construct(
        public bool $successful,
        public bool $alreadyStarted,
        public ?string $processId,
        public string $message,
    ) {}

    public static function started(string|int|null $processId = null): self
    {
        return new self(true, false, $processId === null ? null : (string) $processId, 'Workflow iniciado.');
    }

    public static function alreadyStarted(string|int|null $processId = null): self
    {
        return new self(true, true, $processId === null ? null : (string) $processId, 'O workflow já estava iniciado.');
    }

    public static function failure(string $message): self
    {
        return new self(false, false, null, $message);
    }

    public function succeeded(): bool
    {
        return $this->successful;
    }
}
