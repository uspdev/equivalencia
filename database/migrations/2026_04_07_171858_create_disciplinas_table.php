<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disciplinas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aproveitamento_id')
                ->nullable()
                ->constrained('aproveitamentos')
                ->cascadeOnDelete();

            $table->string('role', 20); // app/Enums/DisciplinaRole.php

            // CAMPOS USP
            $table->tinyInteger('verdis')->nullable();

            // CAMPOS COMPARTILHADOS
            // Codigo da disciplina -> USP usa 7 caracteres, mas outras instituições podem usar mais
            $table->string('coddis', 15);
            $table->string('nomdis', 240)->nullable();
            $table->tinyInteger('creditos')->nullable()->default(0);
            $table->smallInteger('carga_horaria')->nullable();

            $table->string('ies')->nullable(); // Externa(nome) ou USP
            $table->string('sglund')->nullable(); // Sigla da unidade usp ou null

            $table->boolean('disciplina_ativa')->nullable();

            $table->integer('ano')->nullable();
            $table->integer('semestre')->nullable();
            $table->string('codtur', 5)->nullable();
            $table->decimal('frequencia', 5, 2)->nullable();
            $table->decimal('nota', 5, 2)->nullable();

            $table->foreignId('ementa_id')
                ->nullable()
                ->constrained('arquivos')
                ->nullOnDelete();

            $table->foreignId('criado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('alterado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->index(['aproveitamento_id', 'role']);
            $table->index(['ies', 'coddis', 'verdis']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disciplinas');
    }
};
