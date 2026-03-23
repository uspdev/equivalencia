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
            $table->string('verdis')->nullable();
            $table->string('codcur')->nullable();
            $table->string('codhab')->nullable();
            
            // CAMPOS COMPARTILHADOS
            $table->string('coddis');
            $table->string('nome_disciplina')->nullable();
            $table->integer('creditos')->nullable();
            $table->integer('carga_horaria')->nullable();
            $table->string('curso')->nullable();

            // DISCIPLINA EXTERNA
            $table->string('ies')->nullable();
            $table->integer('ano')->nullable();
            $table->enum('semestre', [1, 2])->nullable();
            $table->string('frequencia')->nullable();
            $table->decimal('nota', 4, 2)->nullable();

            // tipo (requerida ou cursada)
            $table->enum('tipo', ['requerida', 'cursada'])->nullable();

            // RELACIONAMENTO
            $table->foreignId('equivalencias_id')
                ->nullable()
                ->constrained('equivalencias')
                ->cascadeOnDelete();

            $table->string('pdf_path')->nullable();

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
