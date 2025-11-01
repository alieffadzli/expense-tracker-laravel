<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'amount', 'notes', 'edit_history', 'initial_balance_id', 'user_id', 'branch_id'
    ];

    public function initialBalance ()
    {
        return $this->belongsTo(InitialBalance::class, 'initial_balance_id', 'id');
    }

    public function user ()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function branch ()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function dailyBalances ()
    {
        return $this->hasMany(DailyBalance::class, 'transaction_id', 'id');
    }
}
