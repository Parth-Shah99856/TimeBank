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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->nullable()->constrained('ideas')->nullOnDelete();
            $table->foreignId('lead_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('target_hours', 7, 2);
            $table->decimal('hours_contributed', 7, 2)->default(0);
            $table->enum('status', ['planning', 'active', 'completed', 'on_hold'])->default('planning');
            $table->timestamps();

            $table->index(['lead_user_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
