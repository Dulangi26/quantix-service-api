<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_number', 30)->unique();

            $table->foreignId('employee_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title', 150);
            $table->text('description');

            $table->string('priority', 20)->default('medium');

            $table->string('status', 30)->default('draft')->index();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
