<?php

namespace Tests\Feature;

use App\Contracts\WorkflowGateway;
use App\Models\Aproveitamento;
use App\Services\Workflow\UspdevWorkflowGateway;
use Database\Seeders\FormDefinitionsTableSeeder;
use Database\Seeders\WorkflowDefinitionsTableSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Uspdev\Forms\Facades\Forms;
use Uspdev\Forms\FormsManager;
use Uspdev\Workflow\WorkflowService;

class PackagesV2IntegrationTest extends TestCase
{
    public function test_application_boots_with_the_public_v2_services_and_local_adapter(): void
    {
        $this->assertSame(12, (int) explode('.', $this->app->version())[0]);
        $this->assertInstanceOf(FormsManager::class, $this->app->make(FormsManager::class));
        $this->assertInstanceOf(WorkflowService::class, $this->app->make(WorkflowService::class));
        $this->assertInstanceOf(UspdevWorkflowGateway::class, $this->app->make(WorkflowGateway::class));
    }

    public function test_real_packages_sync_and_execute_a_basic_integration_through_public_contracts(): void
    {
        $this->usePackageSchemas();

        $this->app->make(FormDefinitionsTableSeeder::class)->run();
        $this->app->make(WorkflowDefinitionsTableSeeder::class)->run();

        $definition = Forms::definition('tr_conferencia_indica');
        $this->assertSame(1, $definition->version);
        $this->assertStringContainsString('Informações para o departamento', Forms::render($definition->name));

        $request = Request::create('/', 'POST', [
            'obs' => 'Documentação conferida.',
            'form_key' => 'integration-test',
        ]);
        $this->assertSame(['obs' => 'Documentação conferida.'], Forms::validate($request, $definition->name));

        $submission = Forms::submit($request, $definition->name);
        $this->assertSame($submission->id, Forms::submission($submission->id)?->id);

        $requerimento = Aproveitamento::create();
        $gateway = $this->app->make(WorkflowGateway::class);
        $started = $gateway->start($requerimento);
        $process = $gateway->query($requerimento);

        $this->assertTrue($started->succeeded(), $started->message);
        $this->assertTrue($process->isAvailable(), $process->message);
        $this->assertSame(['aluno_inicio'], array_map(
            static fn ($place): string => $place->name,
            $process->process->currentPlaces,
        ));
    }

    public function test_adapter_translates_package_failures_without_reporting_success(): void
    {
        $this->usePackageSchemas();

        $requerimento = Aproveitamento::create();
        $gateway = $this->app->make(WorkflowGateway::class);

        $started = $gateway->start($requerimento);
        $process = $gateway->query($requerimento);

        $this->assertFalse($started->succeeded());
        $this->assertSame('Não foi possível iniciar o workflow.', $started->message);
        $this->assertFalse($process->isAvailable());
        $this->assertNotSame('', $process->message);
    }

    private function usePackageSchemas(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'activitylog.enabled' => false,
        ]);
        DB::purge();
        DB::reconnect();

        foreach ([
            base_path('vendor/uspdev/forms/database/migrations/v2/2026_08_17_000000_create_forms_v2_schema.php'),
            base_path('vendor/uspdev/workflow/database/migrations/v2/2026_08_17_000000_create_workflow_definitions_v2_table.php'),
            base_path('vendor/uspdev/workflow/database/migrations/v2/2026_08_17_000001_create_workflow_objects_v2_table.php'),
            base_path('vendor/uspdev/workflow/database/migrations/v2/2026_08_17_000002_create_workflow_history_v2_table.php'),
        ] as $migration) {
            (require $migration)->up();
        }

        Schema::create('aproveitamentos', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }
}
