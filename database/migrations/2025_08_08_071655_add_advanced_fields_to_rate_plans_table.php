<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_plans', function (Blueprint $table) {
            // Fees
            $table->decimal('children_fee', 15, 2)->nullable()->after('meal_type');
            $table->decimal('infant_fee', 15, 2)->nullable()->after('children_fee');

            // Restrictions
            $table->json('max_stay')->nullable()->after('infant_fee');
            $table->json('min_stay_arrival')->nullable()->after('max_stay');
            $table->json('min_stay_through')->nullable()->after('min_stay_arrival');
            $table->json('closed_to_arrival')->nullable()->after('min_stay_through');
            $table->json('closed_to_departure')->nullable()->after('closed_to_arrival');
            $table->json('stop_sell')->nullable()->after('closed_to_departure');

            // Auto rate settings
            $table->json('auto_rate_settings')->nullable()->after('stop_sell');

            // Inheritance flags
            $table->boolean('inherit_rate')->default(false)->after('auto_rate_settings');
            $table->boolean('inherit_closed_to_arrival')->default(false)->after('inherit_rate');
            $table->boolean('inherit_closed_to_departure')->default(false)->after('inherit_closed_to_arrival');
            $table->boolean('inherit_stop_sell')->default(false)->after('inherit_closed_to_departure');
            $table->boolean('inherit_min_stay_arrival')->default(false)->after('inherit_stop_sell');
            $table->boolean('inherit_min_stay_through')->default(false)->after('inherit_min_stay_arrival');
            $table->boolean('inherit_max_stay')->default(false)->after('inherit_min_stay_through');
            $table->boolean('inherit_max_sell')->default(false)->after('inherit_max_stay');
            $table->boolean('inherit_max_availability')->default(false)->after('inherit_max_sell');
            $table->boolean('inherit_availability_offset')->default(false)->after('inherit_max_availability');
        });
    }

    public function down(): void
    {
        Schema::table('rate_plans', function (Blueprint $table) {
            $table->dropColumn([
                'children_fee',
                'infant_fee',
                'max_stay',
                'min_stay_arrival',
                'min_stay_through',
                'closed_to_arrival',
                'closed_to_departure',
                'stop_sell',
                'auto_rate_settings',
                'inherit_rate',
                'inherit_closed_to_arrival',
                'inherit_closed_to_departure',
                'inherit_stop_sell',
                'inherit_min_stay_arrival',
                'inherit_min_stay_through',
                'inherit_max_stay',
                'inherit_max_sell',
                'inherit_max_availability',
                'inherit_availability_offset',
            ]);
        });
    }
};
