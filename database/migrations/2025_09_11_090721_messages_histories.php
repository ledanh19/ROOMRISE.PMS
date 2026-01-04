<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages_histories', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->text('last_content')->nullable();

            $table->enum('type', ['TEXT', 'IMAGE'])->nullable();
            $table->enum('status', ['NEW', 'MODIFIED', 'CANCELLED'])->default('NEW');
            $table->enum('tag', ['BOOKING', 'CHECK_IN', 'COMPLAINT'])->nullable();
            $table->string('ota_name', 255)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('property_name', 255)->nullable();
            $table->string('external_booking_id')->nullable();
            $table->string('message_thread_id')->nullable();
            $table->boolean('is_admin_send')->default(false);
            $table->boolean('is_new')->default(false);
            $table->enum('message_status', ['ACTIVE', 'CLOSED'])->nullable();

            $table->unsignedSmallInteger('nights_count')->default(0);
            $table->unsignedSmallInteger('rooms_count')->default(0);

            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();

            $table->foreignId('booking_id')->nullable()
                ->constrained('bookings')->cascadeOnUpdate()->nullOnDelete();

            $table->foreignId('property_id')->nullable()
                ->constrained('properties')->cascadeOnUpdate()->nullOnDelete();

            $table->timestamps();

            $table->index(['booking_id', 'property_id']);
            $table->index(['status', 'message_status']);
            $table->index('check_in_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages_histories');
    }
};
