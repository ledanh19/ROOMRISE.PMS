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
        Schema::create('income_expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('date')->nullable();
            $table->string('staff_name')->nullable();
            $table->string('code_booking')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_expenses');
    }
};
