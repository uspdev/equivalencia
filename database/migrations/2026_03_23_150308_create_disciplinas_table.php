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

            // CAMPOS USP
            $table->tinyInteger('verdis')->nullable();
            $table->integer('codcur')->nullable();
            $table->smallInteger('codhab')->nullable();

            // CAMPOS COMPARTILHADOS
            $table->string('coddis', 7);
            $table->string('nomdis', 240)->nullable();
            $table->tinyInteger('creditos')->nullable()->default(0);
            $table->smallInteger('carga_horaria')->nullable();
            $table->string('nomcur', 100)->nullable();

            // DISCIPLINA EXTERNA
            $table->string('ies')->nullable();
            $table->integer('ano')->nullable();
            $table->integer('semestre')->nullable();
            $table->decimal('frequencia', 5, 2)->nullable();
            $table->decimal('nota', 5, 2)->nullable();


            $table->char('tipo', 1)->default('r'); // cursada ou requerida (c ou r ou a (automatica)) default a
            // svgrad -> add disciplina usp (a) e add disciplina externa (c)
            // aluno -> entra com cursada (c) e requerida (r) que ele quer equivalente

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



            // RELACIONAMENTO
            $table->foreignId('equivalencias_id')
                ->nullable()
                ->constrained('equivalencias')
                ->cascadeOnDelete();


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
