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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('status_2')->nullable();

            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('room_unit_id')->nullable()->constrained('room_units')->nullOnDelete();

            $table->decimal('room_price_at_booking', 10, 2)->default(0);

            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->time('check_in_time');
            $table->time('check_out_time');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
