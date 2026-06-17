<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('billing_type', ['free', 'per_ticket', 'monthly'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('PLN');
            $table->unsignedInteger('max_messages_day')->default(10);
            $table->unsignedBigInteger('max_tokens_month')->default(100000);
            $table->unsignedInteger('max_prompt_length')->default(4000);
            $table->unsignedInteger('max_response_length')->default(4000);
            $table->unsignedInteger('tickets_per_charge')->default(10);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->enum('status', ['active', 'expired', 'pending_payment', 'cancelled'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
