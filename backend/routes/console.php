<?php

use App\Models\User;
use App\Services\PlanValidator;
use Illuminate\Support\Facades\Schedule;

// Codziennie o 00:05 — reset dziennych limitów wiadomości
Schedule::call(function () {
    User::where('messages_day', '<', now()->toDateString())
        ->orWhereNull('messages_day')
        ->update([
            'messages_today' => 0,
            'messages_day'   => now()->toDateString(),
        ]);
})->dailyAt('00:05')->name('reset-daily-limits');

// 1. dnia miesiąca — reset miesięcznych tokenów
Schedule::call(function () {
    User::query()->update(['tokens_this_month' => 0]);
})->monthlyOn(1, '00:10')->name('reset-monthly-tokens');
