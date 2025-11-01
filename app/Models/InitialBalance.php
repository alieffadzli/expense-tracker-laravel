<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InitialBalance extends Model
{
    protected $fillable = [
        'balance', 'balance_limit', 'notes', 'branch_id'
    ];

    public function branch ()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }
    
    public function transactions ()
    {
        return $this->hasMany(Transaction::class, 'initial_balance_id', 'id');
    }
}
