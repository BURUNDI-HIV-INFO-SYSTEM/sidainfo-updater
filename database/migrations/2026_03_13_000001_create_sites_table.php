<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->string('siteid', 20)->primary();
            $table->string('site_name');
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->string('current_version', 50)->nullable();
            $table->string('last_status', 50)->nullable(); // installed, failed, checked, unknown
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_installed_at')->nullable();
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
