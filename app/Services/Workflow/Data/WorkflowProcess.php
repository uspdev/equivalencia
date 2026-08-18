<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowProcess
{
    /**
     * @param array<int, WorkflowPlace> $currentPlaces
     * @param array<int, WorkflowAction> $actions
     * @param array<int, WorkflowHistoryEntry> $history
     */
    public function __construct(
        public string|int $id,
        public string $definitionName,
        public array $currentPlaces = [],
        public array $actions = [],
        public array $history = [],
    ) {}
}
