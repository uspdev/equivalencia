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
        Schema::create('equivalencias', function (Blueprint $table) {
            $table->id();

            // Número de grupo
            $table->unsignedBigInteger('grupo'); // auto incremento, mas não é chave primária
            // pq pode ter registros com mesmo grupo (equivalências múltiplas, 2 disciplinas cursadas equivalem a 1 requerida)
            // essa logica pode ser feita no Model

            $table->string('estado')->nullable(); // deferida, negada, etc

            $table->foreignId('requerida_id')
                ->constrained('disciplinas')
                ->cascadeOnDelete();

            $table->foreignId('cursada_id')
                ->constrained('disciplinas')
                ->cascadeOnDelete();

            // 'a' = automática (cadastrada pela secretaria/svgrad)
            // 'r' = solicitação do aluno
            $table->char('tipo', 1)->default('r');

            $table->integer('codcur')->nullable();
            $table->smallInteger('codhab')->nullable();

            $table->foreignId('criado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('alterado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            // Para evitar que tenhamos mais de uma equivalência com a mesma disciplina cursada no mesmo grupo.
            // Esse unique não impede que tenhamos a mesma disciplina cursada em grupos diferentes,
            // o que é permitido (ex: 2 equivalências diferentes, ambas com a mesma disciplina cursada, mas em grupos diferentes).
            // Ele só impede que tenhamos 2 equivalências iguais (mesmo grupo, mesma disciplina cursada).
            $table->unique(['grupo', 'cursada_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencias');
    }
};
