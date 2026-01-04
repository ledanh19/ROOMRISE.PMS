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
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('note');
            $table->unsignedBigInteger('booking_id')->nullable()->after('id');
            $table->unsignedBigInteger('settlement_id')->nullable()->after('booking_id');

            $table->foreign('booking_id')
                ->references('id')->on('bookings')
                ->onDelete('set null');

            $table->foreign('settlement_id')
                ->references('id')->on('settlements')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['settlement_id']);
            $table->dropColumn(['booking_id', 'settlement_id', 'created_by']);
        });
    }
};
