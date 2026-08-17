<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas de Forms e Workflow aprovadas para regeneração controlada.
     *
     * A migration não cria tabelas nem altera a tabela de migrations do
     * Laravel. A criação do schema novo pertence aos pacotes proprietários.
     */
    private const DEFAULT_CONNECTION_TABLES = [
        'workflow_history',
        'workflow_role_users',
        'workflow_role_emails',
        'workflow_objects',
        'user_workflow_definition',
        'workflow_definitions',
        'form_submissions',
        'form_definitions',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::DEFAULT_CONNECTION_TABLES as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->activitySchema()->dropIfExists($this->activityTable());
    }

    public function down(): void
    {
        throw new \LogicException(
            'A migration de transição de Forms e Workflow é deliberadamente irreversível.'
        );
    }

    private function activitySchema(): \Illuminate\Database\Schema\Builder
    {
        $connection = config('activitylog.database_connection') ?: config('database.default');

        return Schema::connection($connection);
    }

    private function activityTable(): string
    {
        return (string) config('activitylog.table_name', 'activity_log');
    }
};
