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
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('property_id'); // external from Channex
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->uuid('external_id')->nullable(); // channex rate_plan_id

            // $table->uuid('tax_set_id')->nullable();
            // $table->uuid('parent_rate_plan_id')->nullable();

            $table->char('currency', 3)->nullable();
            $table->enum('sell_mode', ['per_room', 'per_person'])->default('per_room');
            $table->enum('rate_mode', ['manual', 'derived', 'auto', 'cascade'])->default('manual');
            $table->string('meal_type')->nullable();

            // $table->decimal('children_fee', 10, 2)->nullable();
            // $table->decimal('infant_fee', 10, 2)->nullable();

            // $table->json('max_stay')->nullable();
            // $table->json('min_stay_arrival')->nullable();
            // $table->json('min_stay_through')->nullable();
            // $table->json('closed_to_arrival')->nullable();
            // $table->json('closed_to_departure')->nullable();
            // $table->json('stop_sell')->nullable();

            // $table->boolean('inherit_rate')->default(false);
            // $table->boolean('inherit_closed_to_arrival')->default(false);
            // $table->boolean('inherit_closed_to_departure')->default(false);
            // $table->boolean('inherit_stop_sell')->default(false);
            // $table->boolean('inherit_min_stay_arrival')->default(false);
            // $table->boolean('inherit_min_stay_through')->default(false);
            // $table->boolean('inherit_max_stay')->default(false);
            // $table->boolean('inherit_max_sell')->default(false);
            // $table->boolean('inherit_max_availability')->default(false);
            // $table->boolean('inherit_availability_offset')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
