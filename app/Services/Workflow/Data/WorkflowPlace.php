<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowPlace
{
    public function __construct(
        public string $name,
        public string $label,
        public array $roles = [],
    ) {}
}
