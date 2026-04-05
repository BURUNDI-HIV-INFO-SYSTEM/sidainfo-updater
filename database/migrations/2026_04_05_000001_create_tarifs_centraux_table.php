<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifs_centraux', function (Blueprint $table) {
            $table->id();
            $table->string('code_examen', 30);
            $table->unsignedSmallInteger('annee');
            $table->decimal('prix', 10, 2)->default(0.00);
            $table->string('devise', 10)->default('BIF');
            $table->timestamps();
            $table->unique(['code_examen', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifs_centraux');
    }
};
