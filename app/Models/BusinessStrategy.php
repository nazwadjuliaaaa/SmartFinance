<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessStrategy extends Model
{
    protected $fillable = [
        'user_id',
        'strategy_content',
        'based_on_profit',
        'status',
    ];

    protected $casts = [
        'strategy_content' => 'array',
    ];
}
