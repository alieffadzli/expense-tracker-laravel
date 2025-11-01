<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name', 'description', 'author_id', 'organization_id'
    ];

    public function organization ()
    {
        return $this->belongsTo(Organization::class, 'organization_id', 'id');
    }

    public function author () 
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function initialBalance ()
    {
        return $this->belongsTo(initialBalance::class, 'branch_id', 'id');
    }

    public function dailyBalances ()
    {
        return $this->hasMany(DailyBalance::class, 'branch_id', 'id');
    }
}
