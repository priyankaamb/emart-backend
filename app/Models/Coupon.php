<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';

    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'usage_limit', 'user_usage_limit',
        'used_count', 'product_id', 'start_date', 'end_date', 'is_active'
    ];

    public function products()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }
}
