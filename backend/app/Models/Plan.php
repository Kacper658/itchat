<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    public const BILLING_FREE      = 'free';
    public const BILLING_PER_TICKET = 'per_ticket';
    public const BILLING_MONTHLY   = 'monthly';

    protected $fillable = [
        'name', 'slug', 'billing_type', 'price', 'currency',
        'max_messages_day', 'max_tokens_month',
        'max_prompt_length', 'max_response_length',
        'tickets_per_charge', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->billing_type === self::BILLING_FREE;
    }

    public function isMonthly(): bool
    {
        return $this->billing_type === self::BILLING_MONTHLY;
    }

    public function isPerTicket(): bool
    {
        return $this->billing_type === self::BILLING_PER_TICKET;
    }
}
