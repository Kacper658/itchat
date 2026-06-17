<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('currency', 3)->default('PLN');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'chat_cost', 'refund', 'subscription_fee', 'bonus']);
            $table->decimal('amount', 12, 2);   // + wpływ / − obciążenie
            $table->string('currency', 3)->default('PLN');
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('provider', 50)->default('system');
            $table->string('provider_transaction_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('message_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->unique(['provider', 'provider_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
    }
};
