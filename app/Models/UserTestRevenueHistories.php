<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTestRevenueHistory extends Model
{
    protected $table = 'user_test_revenue_histories';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'revenue',
        'date',
    ];

    protected $casts = [
        'revenue' => 'decimal:2',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

