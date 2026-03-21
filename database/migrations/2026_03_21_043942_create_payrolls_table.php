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
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->id();
            $table->string('entity');
            $table->string('registration')->nullable();
            $table->string('name')->nullable();
            $table->string('role')->nullable();
            $table->date('admission_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->string('employment_regime')->nullable();
            $table->string('workplace')->nullable();
            $table->integer('workload_hours')->nullable();
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->decimal('allowances', 12, 2)->nullable();
            $table->decimal('deductions', 12, 2)->nullable();
            $table->decimal('taxes', 12, 2)->nullable();
            $table->decimal('net_salary', 12, 2)->nullable();
            $table->smallInteger('month');
            $table->smallInteger('year');
            $table->timestamps();

            $table->unique(['entity', 'registration', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
