<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['financial_record_id', 'product_id', 'quantity', 'total_price'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function financialRecord()
    {
        return $this->belongsTo(FinancialRecord::class);
    }
}
