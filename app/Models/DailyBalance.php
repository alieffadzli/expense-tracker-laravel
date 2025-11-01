<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyBalance extends Model
{
    protected $fillable = [
        'opening_balance', 'closing_balance', 'branch_id', 'transaction_id'
    ];

    public function transaction ()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    public function branch ()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }
}
