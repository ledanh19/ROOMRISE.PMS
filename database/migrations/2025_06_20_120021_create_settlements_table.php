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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->integer('total_booking')->default(0);
            $table->decimal('total_net_estimate', 15, 2)->default(0);
            $table->decimal('total_payout', 15, 2)->default(0);
            $table->decimal('total_difference', 15, 2)->default(0);
            $table->string('status')->default('Chờ xác nhận');
            $table->date('settlement_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
