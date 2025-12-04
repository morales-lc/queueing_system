<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('service_type', ['cashier', 'registrar']);
            $table->enum('priority', ['pwd_senior_pregnant', 'student', 'parent']);
            $table->enum('status', ['pending', 'serving', 'done', 'on_hold'])->default('pending');
            $table->foreignId('counter_id')->nullable()->constrained('counters')->nullOnDelete();
            $table->unsignedInteger('hold_count')->default(0);
            $table->unsignedInteger('called_times')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
