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
        Schema::create('user_workflow_definition', function (Blueprint $table) {
            $table->id();
            
            $table->string('workflow_definition_name');
            $table->foreign('workflow_definition_name')->references('name')->on('workflow_definitions')->onDelete('cascade');
            
            $table->integer('user_codpes');
            $table->foreign('user_codpes')->references('codpes')->on('users')->onDelete('cascade');

            $table->string('place', 20);
            
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_workflow_definition');
    }
};
