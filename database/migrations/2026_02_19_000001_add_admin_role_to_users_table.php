<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('cashier', 'registrar', 'admin') NOT NULL DEFAULT 'cashier'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'cashier']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('cashier', 'registrar') NOT NULL DEFAULT 'cashier'");
    }
};
