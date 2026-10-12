<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'surname',
        'business_name',
        'phone',
        'city',
        'district',
        'address',
        'lat',
        'lng',
        'zone',
        'notes',
        'state',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'state' => 'integer',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
