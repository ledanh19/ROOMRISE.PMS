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
        Schema::table('inventories', function (Blueprint $table) {
            $table->integer('min_stay_arrival')->nullable()->after('availability');
            $table->integer('min_stay_through')->nullable()->after('min_stay_arrival');
            $table->integer('max_stay')->nullable()->after('min_stay_through');
            $table->boolean('closed_to_arrival')->nullable()->after('max_stay');
            $table->boolean('closed_to_departure')->nullable()->after('closed_to_arrival');
            $table->boolean('stop_sell')->nullable()->after('closed_to_departure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                'min_stay_arrival',
                'min_stay_through',
                'max_stay',
                'closed_to_arrival',
                'closed_to_departure',
                'stop_sell',
            ]);
        });
    }
};
