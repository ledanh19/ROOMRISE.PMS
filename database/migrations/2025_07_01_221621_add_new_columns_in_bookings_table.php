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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('customer_payment_amount', 10, 2)->nullable()->after('total_amount');
            $table->string('room_payment_method')->nullable()->after('customer_id');
            $table->decimal('commission_fee', 10, 2)->nullable()->after('room_payment_method');
            $table->integer('adults')->nullable()->after('commission_fee');
            $table->integer('children')->nullable()->after('adults');
            $table->integer('newborn')->nullable()->after('children');
            $table->string('payment_content')->nullable()->after('newborn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
        });
    }
};
