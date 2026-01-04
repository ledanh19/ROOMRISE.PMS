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
        Schema::create('occupancy_options', function (Blueprint $table) {
            $table->id();

            $table->uuid('external_id')->nullable()->unique();
            $table->foreignId('rate_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('occupancy'); // e.g. 1, 2, 3 guests
            $table->boolean('is_primary')->default(false);
            $table->decimal('rate', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occupancy_options');
    }
};
