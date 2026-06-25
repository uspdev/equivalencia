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
        Schema::create('aproveitamentos', function (Blueprint $table) {
            $table->id();

            $table->string('estado')->nullable(); // app/Enums/EquivalenciaEstado.php

            $table->string('tipo', 20); // app/Enums/EquivalenciaTipo.php

            $table->integer('codcur')->nullable();
            $table->smallInteger('codhab')->nullable();

            $table->integer('numero_reuniao')->nullable();
            $table->date('data_reuniao')->nullable();
            $table->text('observacoes')->nullable();

            // aceita nulo para viabilizar dois casos específicos: rascunhos ainda em edição e aproveitamentos automáticos, que não possuem histórico de aluno
            $table->foreignId('historico_id')
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

            $table->index(['tipo', 'codcur', 'codhab']);
            $table->index(['criado_por_id', 'estado', 'tipo']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aproveitamentos');
    }
};
