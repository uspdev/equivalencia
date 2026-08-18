<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowQueryResult
{
    private function __construct(
        public bool $available,
        public ?WorkflowProcess $process,
        public string $message,
    ) {}

    public static function available(WorkflowProcess $process): self
    {
        return new self(true, $process, '');
    }

    public static function unavailable(string $message): self
    {
        return new self(false, null, $message);
    }

    public function isAvailable(): bool
    {
        return $this->available && $this->process !== null;
    }
}
