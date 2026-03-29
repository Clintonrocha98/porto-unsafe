<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_summaries', function (Blueprint $table): void {
            $table->id();
            $table->date('expense_date')->nullable();
            $table->integer('empenho_number')->nullable();
            $table->string('element_code')->nullable();
            $table->string('element_description')->nullable();
            $table->string('creditor')->nullable();
            $table->string('creditor_document')->nullable();
            $table->decimal('committed', 12, 2)->nullable();
            $table->decimal('annulled', 12, 2)->nullable();
            $table->decimal('reinforced', 12, 2)->nullable();
            $table->decimal('liquidated', 12, 2)->nullable();
            $table->decimal('paid', 12, 2)->nullable();
            $table->string('expense_type');
            $table->string('bidding_modality')->nullable();
            $table->string('process_number')->nullable();
            $table->smallInteger('month');
            $table->smallInteger('year');
            $table->timestamps();

            $table->unique(['expense_type', 'empenho_number', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_summaries');
    }
};
