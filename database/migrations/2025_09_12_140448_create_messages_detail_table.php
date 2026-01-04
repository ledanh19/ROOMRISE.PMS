<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages_detail', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->text('content')->nullable();

            $table->enum('type', ['TEXT', 'IMAGE'])->default('TEXT');

            $table->foreignId('message_history_id')
                ->constrained('messages_histories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->boolean('is_admin_send')->default(false);

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['message_history_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages_detail');
    }
};
