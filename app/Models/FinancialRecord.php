<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRecord extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'cash_amount',
        'non_cash_amount',
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
