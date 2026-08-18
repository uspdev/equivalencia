<?php

namespace App\Services\Workflow\Data;

final readonly class WorkflowTransitionResult
{
    /** @param array<string, mixed> $errors */
    private function __construct(
        public bool $successful,
        public string $message,
        public array $errors = [],
    ) {}

    public static function success(string $message = 'Ação executada com sucesso.'): self
    {
        return new self(true, $message);
    }

    /** @param array<string, mixed> $errors */
    public static function failure(string $message, array $errors = []): self
    {
        return new self(false, $message, $errors);
    }

    public function succeeded(): bool
    {
        return $this->successful;
    }
}
