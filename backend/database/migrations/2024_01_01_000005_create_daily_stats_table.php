<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('new_users')->default(0);
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('conversations_count')->default(0);
            $table->unsignedInteger('messages_count')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('ai_cost', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stats');
    }
};
