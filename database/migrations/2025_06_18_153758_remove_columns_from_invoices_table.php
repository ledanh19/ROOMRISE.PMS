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
            if (Schema::hasColumn('invoices', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('invoices', 'issued_date')) {
                $table->dropColumn('issued_date');
            }

            if (Schema::hasColumn('invoices', 'note')) {
                $table->dropColumn('note');
            }

            if (Schema::hasColumn('invoices', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
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
