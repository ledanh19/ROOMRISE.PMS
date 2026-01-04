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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->date('date');

            // Correctly reference the 'rooms' table
            $table->foreignId('room_type_id')->constrained('rooms')->onDelete('cascade');

            // This is nullable because an inventory entry for availability (AVL)
            // does not belong to a specific rate plan.
            $table->foreignId('rate_plan_id')->nullable()->constrained()->onDelete('cascade');

            // Store the rate/price. We use decimal for monetary values.
            // This will be NULL for an availability entry.
            $table->decimal('rate', 10, 2)->nullable();

            // Store the number of rooms available. 
            // This will be NULL for a rate entry.
            $table->integer('availability')->nullable();

            $table->timestamps();

            // Add a unique constraint to prevent duplicate entries for the same day/item
            $table->unique(['date', 'property_id', 'room_type_id', 'rate_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
