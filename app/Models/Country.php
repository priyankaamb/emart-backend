<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name', 'code'];
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
