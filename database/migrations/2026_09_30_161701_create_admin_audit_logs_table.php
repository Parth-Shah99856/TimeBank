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
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            // The admin who performed the action (nullable so logs survive admin deletion)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_name', 255); // Snapshot of name at time of action
            $table->string('action', 100);     // e.g. 'user.deleted', 'service.deleted'
            $table->string('target_type', 100); // e.g. 'user', 'service'
            $table->unsignedBigInteger('target_id');
            $table->string('target_label', 255); // Snapshot of target name/title
            $table->json('meta')->nullable();    // Extra context (e.g. email, counts)
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
