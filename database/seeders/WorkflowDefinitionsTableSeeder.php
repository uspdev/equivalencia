<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Uspdev\Workflow\Services\WorkflowSyncService;

class WorkflowDefinitionsTableSeeder extends Seeder
{
    public function __construct(private readonly WorkflowSyncService $workflows) {}

    public function run(): void
    {
        $this->workflows->sync((string) config('equivalencia.workflow.definition_path'));
    }
}
