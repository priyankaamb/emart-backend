<?php

namespace App\Http\Controllers\coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Deal;
use App\Models\Product;
use App\Models\CouponUsage;
use Auth;
use App\Models\Cart;
use App\Models\CartItem;

class CouponController extends Controller
{

    public function createCoupon(Request $request)
    {
     
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:coupons',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'user_usage_limit' => 'nullable|integer|min:1',
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $start_date_ist = convertToIST($request->start_date);
        $end_date_ist   = convertToIST($request->end_date);

        try {
            \Log::info('Received Coupon Data:', $request->all());
        
            $coupon = Coupon::create([
                'code' => $request->code,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'usage_limit' => $request->usage_limit,
                'user_usage_limit' => $request->user_usage_limit,
                'product_id' => $request->product_id,
                'start_date' => $start_date_ist,
                'end_date' => $end_date_ist,
                'is_active' => $request->has('is_active') ? $request->is_active : 1, // Default to 1 if not provided
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Coupon created successfully',
                'coupon' => $coupon
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating the coupon',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getCoupon($id =null){
    $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));
    if($id){
       $coupon = Coupon::find($id);
       if ($coupon) { 
        $product = Product::where('id',$coupon->product_id)->first();
        if($product){
            return response()->json(['success' => true,'product' =>$product],200);
        }
    } else {
        return response()->json(['message' => 'Coupon not found'], 404);  // Return an error if coupon is not found
    }
    }
    $coupon = Coupon::with('products')->where('is_active', 1)
                    ->where('end_date', '>=', $currentDateTime)
                    ->whereColumn('usage_limit', '>', 'used_count')
                    ->first(); // Fetch only the first valid coupon
    if (!$coupon) {
        return response()->json(['message' => 'No valid coupons available','success' => false], 200);
    }
    return response()->json(['success' => true, 'coupon'=> $coupon]);
    }

    public function applyCoupon(Request $request)
    {
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Fetch active coupon
        $coupon = Coupon::where('code', $request->code)
                        ->where('is_active', 1)
                        ->first();
        if (!$coupon) {
            return response()->json(['message' => 'Invalid or expired coupon'], 400);
        }

        // Check overall and user-specific coupon usage limits
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'Coupon usage limit reached.'], 400);
        }

        $userUsage = CouponUsage::where('user_id', $user->id)
                                ->where('coupon_id', $coupon->id)
                                ->first();

        if ($userUsage && $userUsage->times_used >= $coupon->user_usage_limit) {
            return response()->json(['message' => 'You have already used this coupon.'], 400);
        }

        // Check for an active deal
        $activeDeal = Deal::whereHas('products', function ($query) use ($coupon) {
                            $query->where('product_id', $coupon->product_id);
                        })
                        ->where('start_date', '<=', $currentDateTime)
                        ->where('end_date', '>=', $currentDateTime)
                        ->where('is_active', 1)
                        ->first();

        // Fetch user's cart and relevant cart item
        $cart = Cart::where('user_id', $user->id)->first();
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartProduct = CartItem::with('product')
                            ->where('product_id', $coupon->product_id)
                            ->where('cart_id', $cart->id)
                            ->first();

        if (!$cartProduct) {
            return response()->json(['message' => 'Coupon does not apply to any product in your cart.'], 400);
        }

        $product = $cartProduct->product;
        $price = $cartProduct->price / $cartProduct->quantity;
        $totalPrice = $price * $cartProduct->quantity;
        
        // Update usage count
        $coupon->increment('used_count');
        $userUsage ? $userUsage->increment('times_used') : CouponUsage::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'times_used' => 1
        ]);

        // Calculate discount amount
        if ($activeDeal) {
            if ($coupon->discount_type == 'percentage' && $activeDeal->discount_type == 'percentage') {
                $discountedPrice = $product->price - ($product->price * $activeDeal->discount_value / 100);
                $discountAmount = $discountedPrice * $coupon->discount_value / 100;
                //  this is completed
            } elseif ($coupon->discount_type == 'fixed' && $activeDeal->discount_type == 'fixed') {
                $discountedPrice = max(0, $product->price - $activeDeal->discount_value);
                $flexDiscountPrice  = max(0, $discountedPrice - $coupon->discount_value);
                $discountAmount = max(0, $discountedPrice - $flexDiscountPrice);
                //  this is completed
            }
            elseif ($coupon->discount_type == 'fixed' && $activeDeal->discount_type == 'percentage') {
                $discountedPrice = $product->price - ($product->price * $activeDeal->discount_value / 100);
                $flexDiscountPrice  = max(0, $discountedPrice - $coupon->discount_value);
                $discountAmount = $discountedPrice - $flexDiscountPrice;
            //  this is completed
            }
             elseif ($coupon->discount_type == 'percentage' && $activeDeal->discount_type == 'fixed') {
                $discountedPrice = max(0, $product->price - $activeDeal->discount_value);
                $discountAmount = $discountedPrice * $coupon->discount_value / 100;
            //  this is completed
            }
        } else {
            $discountAmount = ($coupon->discount_type == 'percentage') 
                            ? (($totalPrice * $coupon->discount_value) / 100) / $cartProduct->quantity
                            : $coupon->discount_value;
        }
        // Update cart item price
   
        $finalCartPrice = max(0, $totalPrice - $discountAmount);
  
        $cartProduct->update(['price' => $finalCartPrice]);
        // CartItem::where('id', $cartProduct->id)->update([
        //     'discount_applied' => $discountAmount
        // ]);
        return response()->json(['message' => 'Coupon applied successfully','success' => true]);
    }
  
}
