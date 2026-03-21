<?php

declare(strict_types=1);

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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique(['entity', 'registration', 'month', 'year']);
            $table->unique(['entity', 'registration', 'role', 'month', 'year'], 'payrolls_unique_worker_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('payrolls_unique_worker_entry');
            $table->unique(['entity', 'registration', 'month', 'year']);
        });
    }
};
