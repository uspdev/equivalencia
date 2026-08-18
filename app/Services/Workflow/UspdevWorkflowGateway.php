<?php

namespace App\Services\Workflow;

use App\Contracts\WorkflowGateway;
use App\Models\Aproveitamento;
use App\Models\User;
use App\Services\Workflow\Data\WorkflowAction;
use App\Services\Workflow\Data\WorkflowHistoryEntry;
use App\Services\Workflow\Data\WorkflowPlace;
use App\Services\Workflow\Data\WorkflowProcess;
use App\Services\Workflow\Data\WorkflowQueryResult;
use App\Services\Workflow\Data\WorkflowStartResult;
use App\Services\Workflow\Data\WorkflowTransitionResult;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use UnexpectedValueException;
use Uspdev\Workflow\Exceptions\TransitionNotAllowedException;
use Uspdev\Workflow\WorkflowService;

final class UspdevWorkflowGateway implements WorkflowGateway
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly string $workflowName,
    ) {}

    public function start(Aproveitamento $requerimento): WorkflowStartResult
    {
        try {
            $existing = $this->workflow->find($requerimento);
            if ($existing !== null) {
                return WorkflowStartResult::alreadyStarted($existing->getKey());
            }

            $workflowObject = $this->workflow->start($this->workflowName, $requerimento);

            return WorkflowStartResult::started($workflowObject->getKey());
        } catch (Throwable $exception) {
            $this->logFailure('start', $requerimento, $exception);

            return WorkflowStartResult::failure('Não foi possível iniciar o workflow.');
        }
    }

    public function query(Aproveitamento $requerimento, ?User $actor = null): WorkflowQueryResult
    {
        try {
            $workflowObject = $this->workflow->find($requerimento);
            if ($workflowObject === null) {
                return WorkflowQueryResult::unavailable(
                    'O processo de workflow ainda não está disponível para este requerimento.'
                );
            }

            $state = $workflowObject->workflowState();
            if (! is_array($state)) {
                throw new UnexpectedValueException('O estado retornado pelo workflow não é um array.');
            }

            $places = $workflowObject->currentPlaces()
                ->map(fn ($place): WorkflowPlace => new WorkflowPlace(
                    name: $place->name,
                    label: $place->label,
                    roles: $place->roles,
                ))
                ->values()
                ->all();

            $actions = collect($state['transitions'] ?? [])
                ->map(fn (array $transition): WorkflowAction => new WorkflowAction(
                    name: (string) $transition['name'],
                    label: (string) ($transition['label'] ?? $transition['name']),
                    formName: is_string($transition['form'] ?? null) ? $transition['form'] : null,
                    from: $this->stringList($transition['from'] ?? []),
                    to: $this->stringList($transition['tos'] ?? []),
                ))
                ->all();

            $history = $workflowObject->getHistory()
                ->map(fn ($entry): WorkflowHistoryEntry => new WorkflowHistoryEntry(
                    transition: $entry->transition_name,
                    from: $entry->from_places,
                    to: $entry->to_places,
                    actor: $entry->user?->name,
                    occurredAt: $this->dateValue($entry->created_at),
                ))
                ->all();

            return WorkflowQueryResult::available(new WorkflowProcess(
                id: $workflowObject->getKey(),
                definitionName: $workflowObject->definition->name ?? $this->workflowName,
                currentPlaces: $places,
                actions: $actions,
                history: $history,
            ));
        } catch (Throwable $exception) {
            $this->logFailure('query', $requerimento, $exception);

            return WorkflowQueryResult::unavailable(
                'Não foi possível consultar o processo agora. Tente novamente mais tarde.'
            );
        }
    }

    public function transition(
        Aproveitamento $requerimento,
        string $transition,
        array $input = [],
        ?User $actor = null,
    ): WorkflowTransitionResult {
        try {
            $workflowObject = $this->workflow->find($requerimento);
            if ($workflowObject === null) {
                return WorkflowTransitionResult::failure(
                    'Não há um processo de workflow disponível para executar essa ação.'
                );
            }

            return $workflowObject->apply($transition, $input, $actor)
                ? WorkflowTransitionResult::success()
                : WorkflowTransitionResult::failure('Não foi possível executar essa ação.');
        } catch (ValidationException $exception) {
            $this->logFailure('transition-validation', $requerimento, $exception);

            return WorkflowTransitionResult::failure(
                'Verifique os dados informados para executar essa ação.',
                $exception->errors(),
            );
        } catch (TransitionNotAllowedException $exception) {
            $this->logFailure('transition-not-allowed', $requerimento, $exception);

            return WorkflowTransitionResult::failure('Essa ação não está disponível para o processo atual.');
        } catch (Throwable $exception) {
            $this->logFailure('transition', $requerimento, $exception);

            return WorkflowTransitionResult::failure('Não foi possível executar essa ação.');
        }
    }

    /** @return array<int, string> */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return $value === '' ? [] : [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_scalar($item) ? (string) $item : '',
            $value,
        )));
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y H:i');
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function logFailure(string $operation, Aproveitamento $requerimento, Throwable $exception): void
    {
        Log::error('Workflow integration failed.', [
            'operation' => $operation,
            'aproveitamento_id' => $requerimento->getKey(),
            'exception' => $exception,
        ]);
    }
}
