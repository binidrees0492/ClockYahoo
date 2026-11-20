<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items'           => 'array',
        'issue_date'      => 'date',
        'expiration_date' => 'date',
        'subtotal'        => 'float',
        'tax_rate'        => 'float',
        'tax_amount'      => 'float',
        'total_amount'    => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'job_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
