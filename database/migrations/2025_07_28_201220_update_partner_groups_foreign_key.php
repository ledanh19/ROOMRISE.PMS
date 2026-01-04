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
        Schema::table('partner_groups', function (Blueprint $table) {
            $table->dropForeign(['partner_admin_id']);
            $table->foreign('partner_admin_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partner_groups', function (Blueprint $table) {
            $table->dropForeign(['partner_admin_id']);
            $table->foreign('partner_admin_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
