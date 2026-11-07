<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = true;

    protected $table = 'order';

    protected $fillable = [
        'customer_id',
        'user_id',
        'status',
        'noresi',
        'kurir',
        'layanan_ongkir',
        'biaya_ongkir',
        'estimasi_ongkir',
        'total_berat',
        'total_harga',
        'alamat',
        'pos',
    ];

    protected $casts = [
        'biaya_ongkir' => 'integer',
        'total_berat' => 'integer',
        'total_harga' => 'float',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
