<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company',
        'cnpj',
        'amount',
        'due_date',
        'paid',
        'deleted_at',
        'created_at',
        'updated_at',
        'paid_at',
        'bill_path',
        'invoice',
        'installment'
    ];
}
