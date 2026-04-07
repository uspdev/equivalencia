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

            // CAMPOS USP
            $table->tinyInteger('verdis')->nullable();

            // CAMPOS COMPARTILHADOS
            $table->string('coddis', 7);
            $table->string('nomdis', 240)->nullable();
            $table->tinyInteger('creditos')->nullable()->default(0);
            $table->smallInteger('carga_horaria')->nullable();

            $table->string('ies')->nullable(); // Externa(nome) ou USP
            $table->string('sglund')->nullable(); // Sigla da unidade usp ou null

            // DISCIPLINA EXTERNA
            $table->integer('ano')->nullable();
            $table->integer('semestre')->nullable();
            $table->decimal('frequencia', 5, 2)->nullable();
            $table->decimal('nota', 5, 2)->nullable();


            // Campo para vincular um pedido de equivalência a um aluno
            // seguindo a lógica aluno -> entra com cursada (c) e requerida (r) que ele quer equivalente
            $table->foreignId('criado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('alterado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

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
