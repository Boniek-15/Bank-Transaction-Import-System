<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['transaction_id','account_number', 'transaction_date', 'amount', 'currency'])]
class Transaction extends Model
{
    public const UPDATED_AT = null;

    protected $casts = ['transaction_date' => 'date:Y-m-d','amount' => 'decimal:2'];
}
