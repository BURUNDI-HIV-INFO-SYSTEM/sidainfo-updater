<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver', 20);
            $table->boolean('is_active')->default(true);
            $table->longText('config');
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_backup_filename')->nullable();
            $table->unsignedBigInteger('last_backup_size_bytes')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->text('last_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_destinations');
    }
};
