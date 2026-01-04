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
        Schema::create('room_availability_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('total_rooms')->default(0);
            $table->integer('available_rooms')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_availability_summaries');
    }
};
