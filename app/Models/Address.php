<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    protected $fillable = [
        'street',
        'number',
        'neighborhood',
        'city',
        'state',
        'zipcode',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
