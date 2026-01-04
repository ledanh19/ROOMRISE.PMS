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
        Schema::table('settlements', function (Blueprint $table) {
            $table->string('settlement_officer')->nullable()->after('settlement_date');
            $table->string('payment_method')->nullable()->after('settlement_officer');
            $table->string('reconciliation_status')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            $table->string('settlement_officer')->nullable()->after('settlement_date');
            $table->string('payment_method')->nullable()->after('settlement_officer');
            $table->string('reconciliation_status')->nullable()->after('payment_method');
        });
    }
};
