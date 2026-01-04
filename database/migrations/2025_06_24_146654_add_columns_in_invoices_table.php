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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreignId('customer_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->string('created_by')->nullable()->after('total_amount');
            $table->text('note')->nullable()->after('created_by');
            $table->date('issued_date')->nullable()->after('note');
            $table->foreignId('partner_id')->nullable()->after('customer_id');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            //
        });
    }
};
