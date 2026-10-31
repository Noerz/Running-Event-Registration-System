<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'participant_id',
        'payment_method',
        'amount',
        'payment_proof',
        'payment_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
    ];
    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'verified_at' => 'datetime',
        ];
    }
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
    public function proof(): HasOne
    {
        return $this->hasOne(PaymentProof::class)->latestOfMany();
    }
    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }
}
