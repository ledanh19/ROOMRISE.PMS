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
        Schema::table('properties', function (Blueprint $table) {
            $table->uuid('external_id')->nullable()->index()->after('max_rooms'); // ID từ Channex
            $table->string('currency')->nullable()->after('external_id');
            $table->boolean('is_active')->default(true)->after('currency');
            $table->string('website')->nullable()->after('is_active');
            $table->string('category')->nullable()->after('website');
            $table->string('max_count_of_rate_plans')->nullable()->after('website');
            // $table->decimal('latitude', 10, 7)->nullable();
            // $table->decimal('longitude', 10, 7)->nullable();
            // $table->string('zip_code')->nullable();
            // $table->string('timezone')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'external_id',
                'currency',
                'is_active',
                'website',
                'category',
            ]);
        });
    }
};
