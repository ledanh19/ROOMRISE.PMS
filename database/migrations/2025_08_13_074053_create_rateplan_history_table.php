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
        Schema::create('rateplan_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_plan_id')->constrained('rate_plans')->onDelete('cascade');
            $table->date('effective_date');
            $table->integer('primary_occupancy')->nullable();

            $table->decimal('rate', 10, 2);

            $table->decimal('children_fee', 15, 2)->nullable();
            $table->decimal('infant_fee', 15, 2)->nullable();

            // Restrictions
            $table->json('min_stay_arrival');
            $table->json('min_stay_through');
            $table->json('max_stay');
            $table->json('closed_to_arrival');
            $table->json('closed_to_departure');
            $table->json('stop_sell');
            $table->json('metadata')->nullable();
            $table->json('occupancy_options')->nullable();

            $table->timestamps();

            // Index quan trọng cho performance
            $table->index(['rate_plan_id', 'effective_date']);
            $table->unique(['rate_plan_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rateplan_history');
    }
};
