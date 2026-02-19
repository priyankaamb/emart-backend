<?php

namespace App\Http\Controllers\users\cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Deal;
use Carbon\Carbon;
use App\Models\Coupon;
use App\Models\CouponUsage;
class CartController extends Controller
{
    // **************************************ADD TO CAT FUNCTION***********************************//
    public function addTocart(Request $request){
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));
        $validator = Validator::make($request->all(),[
            'productId' => 'required',
        ]);
        if($validator->fails()){
            return response()->json(['error' => 'validation failed'],400);
        }
        $id = $request->productId;
        $quantity = 1;
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        // **Check if product is in an active deal**
        $activeDeal = Deal::whereHas('products', function ($query) use ($id) {
                                $query->where('product_id', $id);
                            })
                            ->where('start_date', '<=', $currentDateTime)
                            ->where('end_date', '>=', $currentDateTime)
                            ->where('is_active', 1)
                            ->first();
   
        $price = $product ? $product->discount_price : $product->price;
        if ($activeDeal) {
            // Calculate the discounted price
            $discountValue = $activeDeal->discount_value;
            if ($activeDeal->discount_type === 'percentage') {
                $discountedPrice = $product->price - ($product->price * ($discountValue / 100));
            } else {
                $discountedPrice = max(0, $product->price - $discountValue);
            }
            $price = round($discountedPrice, 2);
        }
        DB::beginTransaction();

        try {
            // Handle authenticated user cart
            $cart = null;
            if (Auth::check()) {
                $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            }
    
            // Check if the product is already in the cart
            $cartItem = CartItem::where('cart_id', $cart->id)
                                ->where('product_id', $product->id)
                                ->first();
    
            if ($cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is already in the cart.',
                    'cart' => $cart->load('items.product'),
                    'count' => $cart->items()->count(),
                ]);
            }
    
            // Add product to cart if not already there
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
            ]);
          
            // Commit transaction
            DB::commit();
    
            // Reload cart and return success response
            $cart->load('items.product');
            $itemCount = $cart->items()->count();
    
            return response()->json([
                'success' => true,
                'message' => 'Product added to the cart successfully.',
                'cart' => $cart,
                'count' => $itemCount,
            ]);
    
        } catch (\Exception $e) {
            // Rollback transaction on failure
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
        }
    }

    // **************************************USER CART PRODUCT FUNCTION***********************************//
    public function UserCartProducts($userId) {
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Empty API data provided'], 400);
        }
        $cart = Cart::where('user_id', $userId)->first();
        
        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'Cart not found'], 404);
        }
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));
    
        $cartItems = CartItem::where('cart_id',$cart->id)->with('product')->get();
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            $activeDeal = Deal::whereHas('products', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->where('start_date', '<=', $currentDateTime)
                ->where('end_date', '>=', $currentDateTime)
                ->where('is_active', 1)
                ->first();
            
            if ($activeDeal) {
                if ($activeDeal->discount_type == 'percentage') {
                    $discountedPrice = $product->price - ($product->price * $activeDeal->discount_value / 100);
                } else {
                    $discountedPrice = $product->price - $activeDeal->discount_value;
                }
            } else {
                $discountedPrice = $product->discount_price ?? $product->price;
            }
            // Ensure price is not negative
            $discountedPrice = max(0, $discountedPrice);
            $totalPrice = $cartItem->price / $cartItem->quantity;
            
            $cartItem->price = $totalPrice * $cartItem->quantity;
            $cartItem->save();
        }
        $totalPrice = $cartItems->sum('price');
        return response()->json([
            'success' => true,
            'cartProducts' => $cartItems,
            'totalPrice' => $totalPrice
        ], 200);
    }
    // **************************************CART PRODCUT INCREASE AND DECREASE FUNCTION***********************************//
    public function updateCartQuantity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cartItemId' => 'required',
            'type' => 'required|in:increase,decrease',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed'], 400);
        }

        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $cartItem = CartItem::with('product')->find($request->cartItemId);
        if (!$cartItem || !$cartItem->product) {
            return response()->json(['success' => false, 'message' => 'Cart item or product not found'], 404);
        }

        $product = $cartItem->product;
       
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));

        // Set the base price (discount price if available, otherwise original price)
        $originalPrice = $product->discount_price ?? $product->price;
        $discountedPrice = $originalPrice;

        // Get active deal
        $activeDeal = Deal::whereHas('products', fn($query) => $query->where('product_id', $product->id))
                        ->where('start_date', '<=', $currentDateTime)
                        ->where('end_date', '>=', $currentDateTime)
                        ->where('is_active', 1)
                        ->first();

        // Get active coupon and usage
        $coupon = Coupon::where('product_id', $product->id)->where('is_active', 1)->first();
 
        $couponUsage = CouponUsage::with('coupon')->where('user_id', $userId)->first();

     
        if ($couponUsage && $activeDeal && $coupon) {
            if ($activeDeal) {
                if ($coupon->discount_type == 'percentage' && $activeDeal->discount_type == 'percentage') {
                    $discountedPrice = $product->price - ($product->price * $activeDeal->discount_value / 100);
                    $discountAmount = ($discountedPrice * $coupon->discount_value) / 100;
                    $finalPrice = $discountedPrice - $discountAmount;
                //  this is completed
                } elseif ($coupon->discount_type == 'fixed' && $activeDeal->discount_type == 'fixed') {
                    $discountedPrice = max(0, $product->price - $activeDeal->discount_value);
                    $discountAmount = max(0, $discountedPrice - $coupon->discount_value);
                    $finalPrice   = $discountAmount;
                //  this is completed
                } elseif ($coupon->discount_type == 'fixed' && $activeDeal->discount_type == 'percentage') {
                    // Apply active deal first (percentage), then fixed discount
                    $discountedPrice = $product->price - ($product->price * $activeDeal->discount_value / 100);
                    $discountAmount  = max(0, $discountedPrice - $coupon->discount_value);
                    $finalPrice   = $discountAmount;
                //  this is completed
                }
                 elseif ($coupon->discount_type == 'percentage' && $activeDeal->discount_type == 'fixed') {
                    $discountedPrice = max(0, $product->price - $activeDeal->discount_value);
                    $discountAmount = $discountedPrice * $coupon->discount_value / 100;
                    $finalPrice   = $discountedPrice - $discountAmount;
                //  this is completed
                }
            }
        } else {
            if ($activeDeal) {
                $discountedPrice = $activeDeal->discount_type == 'percentage'
                    ? $product->price - ($product->price * $activeDeal->discount_value / 100)
                    : max(0, $product->price - $activeDeal->discount_value);
            } elseif ($coupon && $couponUsage) {
                $discountAmount = $coupon->discount_type == 'percentage'
                    ? ($originalPrice * $coupon->discount_value) / 100
                    : $coupon->discount_value;
              
                $discountedPrice = max(0, $originalPrice - $discountAmount);
            }
        }

        // Ensure the price is not negative
        $discountedPrice = max(0, $discountedPrice);

        // Handle quantity update
        if ($request->type === 'increase') {
            $cartItem->quantity += 1;
        } elseif ($request->type === 'decrease' && $cartItem->quantity > 1) {
            $cartItem->quantity -= 1;
        } else {
            return response()->json(['success' => false, 'message' => 'Quantity cannot be less than 1'], 400);
        }

        // Calculate total price
        if (isset($activeDeal) && isset($couponUsage) && isset($coupon)) {
            $totalPrice = $cartItem->quantity > 1
                ? $finalPrice + ($cartItem->quantity - 1) * $discountedPrice
                : $finalPrice;
        } elseif (isset($activeDeal)) {
            $totalPrice = $discountedPrice * $cartItem->quantity;
        } else {
            $totalPrice = $cartItem->quantity > 1
                ? $discountedPrice + (($cartItem->quantity - 1) * $originalPrice)
                : $discountedPrice;
        }

        // Update cart item price
        $cartItem->price = $totalPrice;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart item updated successfully',
            'cartItem' => $cartItem
        ]);
    }
    //***************************REMOVE USER CART ITEMS FUNCTION*********************//
    public function removeUserCartItems(Request $request){
        if (!$request->id) {
            return response()->json(['success' => false, 'message' => 'Empty API data provided'], 400);
        }
        $userId = auth()->id();
        $cartItem = CartItem::where('id', $request->id)->first();
        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Cart item not found'], 400);
        }

        $couponUsage = CouponUsage::where('user_id', $userId)->first();
        if ($couponUsage) {
            $coupon = Coupon::where('id', $couponUsage->coupon_id)->first();
            if ($coupon) {
                $coupon->decrement('used_count');
                $couponUsage->delete();
            }
        }
        $cartItem->delete();
        $cart = Cart::where('user_id', $userId)->first();
        if (!$cart || $cart->items()->count() === 0) {
            return response()->json(['success' => true, 'count' => '']);
        }
        $itemCount =  $cart->items()->count();
        return response()->json([
            'success' => true,
            'message' => 'Cart item removed successfully',
            'count' => $itemCount
        ], 200);
    }
    //**************************USER CART ITEMS COUNT**********************//
    public function CartItemCount(){
        $userId = auth()->id();
       
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart) {
            $itemCount = $cart->items()->count();
    
            return response()->json([
                'success' => true,
                'message' => 'Cart items fetched successfully.',
                'count' => $itemCount,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cart not found.',
            ], 404);
        }
    }
    
}
