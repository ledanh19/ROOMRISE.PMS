<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_test_revenue_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');

            $table->decimal('revenue', 15, 2);
            $table->date('date');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_test_revenue_histories');
    }
};
