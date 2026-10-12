<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'client_id',
        'total_amount',
        'payment_method',
        'status',
        'city',
        'district',
        'delivery_address',
        'lat',
        'lng',
        'delivery_driver',
        'items_summary',
        'notes',
        'delivery_date',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'total_amount' => 'float',
        'delivery_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
