<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('regular', 'driver', 'admin') DEFAULT 'regular'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('regular', 'driver') DEFAULT 'regular'");
    }
};
