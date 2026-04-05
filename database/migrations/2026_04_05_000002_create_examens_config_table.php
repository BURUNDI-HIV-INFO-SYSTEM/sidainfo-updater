<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examens_config', function (Blueprint $table) {
            $table->string('code', 30)->primary();
            $table->string('nom_examen', 100);
            $table->decimal('valeur_usuelle1', 10, 4)->nullable();
            $table->decimal('valeur_usuelle2', 10, 4)->nullable();
            $table->decimal('limite1', 10, 4)->nullable();
            $table->decimal('limite2', 10, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examens_config');
    }
};
