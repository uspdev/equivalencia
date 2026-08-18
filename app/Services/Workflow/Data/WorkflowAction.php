<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowAction
{
    public function __construct(
        public string $name,
        public string $label,
        public ?string $formName = null,
        public array $from = [],
        public array $to = [],
    ) {}

    public function requiresForm(): bool
    {
        return $this->formName !== null && $this->formName !== '';
    }
}
