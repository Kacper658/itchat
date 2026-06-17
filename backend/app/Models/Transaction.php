<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public const TYPE_DEPOSIT          = 'deposit';
    public const TYPE_CHAT_COST        = 'chat_cost';
    public const TYPE_REFUND           = 'refund';
    public const TYPE_SUBSCRIPTION_FEE = 'subscription_fee';
    public const TYPE_BONUS            = 'bonus';

    protected $fillable = [
        'wallet_id', 'user_id', 'type', 'amount', 'currency',
        'balance_after', 'status', 'provider', 'provider_transaction_id',
        'description', 'message_id',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }
}
