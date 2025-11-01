<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active', 'author_id'
    ];

    public function author () 
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function branches ()
    {
        return $this->hasMany(Branch::class, 'organization_id', 'id');
    }
}
