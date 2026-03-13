<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only event log — no updated_at
        Schema::create('site_update_events', function (Blueprint $table) {
            $table->id();
            $table->string('siteid', 20)->index();
            $table->string('event_type', 50); // manifest_check, install_report
            $table->string('status', 50);     // checked, installed, failed, unknown
            $table->string('current_version', 50)->nullable();
            $table->string('target_version', 50)->nullable();
            $table->string('archive', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('siteid')->references('siteid')->on('sites')->onDelete('cascade');
            $table->index(['siteid', 'created_at']);
        });

        // FK from sites back to last_event_id
        Schema::table('sites', function (Blueprint $table) {
            $table->foreign('last_event_id')
                  ->references('id')
                  ->on('site_update_events')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['last_event_id']);
        });
        Schema::dropIfExists('site_update_events');
    }
};
