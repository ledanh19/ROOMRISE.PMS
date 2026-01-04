<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'income_expense_id',
        'action_type',
        'performed_by',        
        'performed_at',
        'source_type',

    ];
    public function incomeExpense()
    {
        return $this->belongsTo(IncomeExpense::class);
    }
}
