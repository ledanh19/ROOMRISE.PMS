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

            $table->string('business_type')->nullable()->after('note');
            $table->string('source_business_code')->nullable()->after('business_type');
            $table->string('source_business_type')->nullable()->after('source_business_code');
            $table->string('room_payment_method')->nullable()->after('source_business_type');
            $table->string('payment_status')->nullable()->after('room_payment_method');
            $table->string('payment_source')->nullable()->after('payment_status');
            $table->string('payment_object')->nullable()->after('payment_source');
            $table->string('file')->nullable()->after('payment_object');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            //
        });
    }
};
