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
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained();
            $table->foreignId('room_unit_id')->nullable()->constrained();
            $table->foreignId('rate_plan_id')->nullable()->constrained('rate_plans')->onDelete('set null');
            $table->decimal('room_price_at_booking', 10, 2)->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_rooms');
    }
};
