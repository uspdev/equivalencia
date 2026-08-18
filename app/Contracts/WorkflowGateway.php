<?php

namespace App\Contracts;

use App\Models\Aproveitamento;
use App\Models\User;
use App\Services\Workflow\Data\WorkflowQueryResult;
use App\Services\Workflow\Data\WorkflowStartResult;
use App\Services\Workflow\Data\WorkflowTransitionResult;

interface WorkflowGateway
{
    public function start(Aproveitamento $requerimento): WorkflowStartResult;

    public function query(Aproveitamento $requerimento, ?User $actor = null): WorkflowQueryResult;

    public function transition(
        Aproveitamento $requerimento,
        string $transition,
        array $input = [],
        ?User $actor = null,
    ): WorkflowTransitionResult;
}
