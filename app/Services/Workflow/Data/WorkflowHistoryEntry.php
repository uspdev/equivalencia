<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowHistoryEntry
{
    public function __construct(
        public string $transition,
        public array $from,
        public array $to,
        public ?string $actor = null,
        public ?string $occurredAt = null,
    ) {}
}
