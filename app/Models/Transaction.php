<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'payer_id',
        'payee_id',
        'amount',
        'type',
        'status',
        'reversed_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
    public function payee()
    {
        return $this->belongsTo(User::class, 'payee_id');
    }
    public function reversedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reversed_transaction_id');
    }
}
