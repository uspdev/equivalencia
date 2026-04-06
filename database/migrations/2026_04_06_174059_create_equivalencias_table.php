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
            $table->unsignedBigInteger('equivalencia_id');

            $table->foreignId('requerida_id')
                ->constrained('disciplinas')
                ->cascadeOnDelete();

            $table->foreignId('cursada_id')
                ->constrained('disciplinas')
                ->cascadeOnDelete();

            // 'a' = automática (cadastrada pela secretaria/svgrad)
            // 'r' = solicitação do aluno
            $table->char('tipo', 1)->default('r');

            $table->foreignId('criado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('alterado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unique(['equivalencia_id', 'cursada_id']);
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
