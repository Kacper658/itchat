<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE           = 'active';
    public const STATUS_EXPIRED          = 'expired';
    public const STATUS_PENDING_PAYMENT  = 'pending_payment';
    public const STATUS_CANCELLED        = 'cancelled';

    protected $fillable = [
        'user_id', 'plan_id', 'status',
        'started_at', 'expires_at', 'auto_renew',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'expires_at'  => 'datetime',
        'auto_renew'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        return !$this->expires_at || $this->expires_at->isFuture();
    }
}
