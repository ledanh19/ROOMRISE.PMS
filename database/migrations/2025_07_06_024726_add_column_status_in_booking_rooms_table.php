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
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->foreignId('property_id')->nullable()->after('booking_id')->constrained()->onDelete('cascade');
            $table->string('room_status')->nullable()->after('note');
            $table->string('nights')->nullable()->after('check_out_date');
            $table->text('price_date')->nullable()->after('nights');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            //
        });
    }
};
