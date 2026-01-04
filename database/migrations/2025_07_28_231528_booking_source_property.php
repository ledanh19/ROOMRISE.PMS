<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_source_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_source_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['property_id', 'booking_source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_source_property');
    }
};
