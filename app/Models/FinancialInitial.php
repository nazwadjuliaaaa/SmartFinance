<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialInitial extends Model
{
    protected $fillable = [
        'user_id',
        'starting_capital',
        'fixed_assets',
        'raw_materials',
        'initial_liabilities',
    ];

    protected $casts = [
        'fixed_assets' => 'array',
        'raw_materials' => 'array',
    ];
}
