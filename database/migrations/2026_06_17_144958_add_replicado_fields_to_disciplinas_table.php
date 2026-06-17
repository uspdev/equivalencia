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
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->text('programa')->nullable()->after('nota');
            $table->text('programa_resumo')->nullable()->after('programa');
            $table->text('objetivo')->nullable()->after('programa_resumo');
            $table->boolean('disciplina_ativa')->nullable()->after('objetivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disciplinas', function (Blueprint $table) {
            $table->dropColumn([
                'programa',
                'programa_resumo',
                'objetivo',
                'disciplina_ativa',
            ]);
        });
    }
};
