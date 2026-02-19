<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
class User extends Authenticatable implements MustVerifyEmail   
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */ 
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'address',
        'role',
        'status',
        'email',
        'password',
        'city',
        'email_verified_at',
        'country_id '

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }
    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class);
    }
    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class);
    }
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class);
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    // public function addresses() {
    //     return $this->hasMany(Address::class);
    // }
    
    // public function orders() {
    //     return $this->hasMany(Order::class);
    // }
}
