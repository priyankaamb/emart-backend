<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'billing_address_id',
        'shipping_address_id',
        'subtotal',
        'tax',
        'shipping_fee',
        'discount',
        'total',
        'status',
        'shipping_method',
        'order_notes'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function billingAddress() {
        return $this->belongsTo(Address::class);
    }
    
    public function shippingAddress() {
        return $this->belongsTo(Address::class);
    }
    
    public function orderItems() {
        return $this->hasMany(OrderItem::class);
    }
    
    public function payment() {
        return $this->hasOne(Payment::class);
    }
    
}
