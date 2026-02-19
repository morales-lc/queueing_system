<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->string('program')->nullable()->after('service_type');
            $table->foreignId('designated_counter_id')->nullable()->after('counter_id')->constrained('counters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('designated_counter_id');
            $table->dropColumn('program');
        });
    }
};
