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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->enum('type', ['Sale', 'Sale TA', 'OTA', 'Social', 'Walk-in', 'Từ đối tác']);
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Identification
            $table->enum('id_type', ['CCCD/CMND', 'Hộ chiếu', 'Bằng lái xe', 'Khác'])->nullable();
            $table->string('id_number')->nullable();
            $table->date('dob')->nullable();
            $table->string('nationality')->nullable();
            $table->string('country')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
