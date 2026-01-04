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
            $table->json('meta')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('channel_id', 36)->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_collect', 20)->nullable();
            $table->string('payment_type_original', 50)->nullable();
            $table->json('rooms')->nullable();
            $table->longText('raw_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'meta',
                'currency',
                'channel_id',
                'notes',
                'payment_collect',
                'payment_type_original',
                'rooms',
                'raw_message'
            ]);
        });
    }
};
