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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id')->unique();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();

            // Log message / action details
            $table->string('log', 255);
            $table->string('mat_no', 50)->nullable();
            $table->timestamps();
            $table->timestamp('scanned_at');
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->json('meta')->nullable();

            $table->index(['mat_no', 'service_id', 'campus_id', 'scanned_at']);

            // Optional: prevent same user from logging same service twice at same time
            // $table->unique(['user_id', 'service_id', 'campus_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
