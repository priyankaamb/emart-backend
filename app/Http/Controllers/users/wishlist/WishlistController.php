<?php

namespace App\Http\Controllers\users\wishlist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
class WishlistController extends Controller
{
    //***************************ADD WISHLIST FUNCTION*********************//
    public function addWishlist(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|exists:products,id',
            ]);

            $userId = auth()->id();
            $existingWishlist = Wishlist::where('user_id', $userId)
                ->where('product_id', $validatedData['id'])
                ->first();

            if ($existingWishlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product is already in your wishlist.',
                ], 409);
            }
            $wishlist = Wishlist::create([
                'user_id' => $userId,
                'product_id' => $validatedData['id'],
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Product added to your wishlist successfully.',
                'data' => $wishlist,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding to the wishlist.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //***************************USER WISHTLIST ITEMS FUNCTION*********************//
    public function wishlists()
    {
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));
        try {
            $userId = auth()->id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access. Please log in.'
                ], 401);
            }
            $wishlists = Wishlist::with('product')->where('user_id', $userId)->get();
            $products = $wishlists->pluck('product');
        
            foreach ($products as $product) {
                $id = $product->id;
                $activeDeal = Deal::whereHas('products', function ($query) use ($id) {
                                    $query->where('product_id', $id);
                                })
                                ->where('start_date', '<=', $currentDateTime)
                                ->where('end_date', '>=', $currentDateTime)
                                ->where('is_active', 1)
                                ->first();
                if ($activeDeal) {
                    $discountValue = $activeDeal->discount_value;
                    $basePrice = $product->price ?? $product->discount_price;
            
                    if ($activeDeal->discount_type === 'percentage') {
                        $discountedPrice = $basePrice - ($basePrice * ($discountValue / 100));
                    } elseif ($activeDeal->discount_type === 'fixed') {
                        $discountedPrice = max(0, $basePrice - $discountValue);
                    }
                    $product->discount_price = $discountedPrice;
                } else {
                    $product->discount_price = $product->discount_price ?? $product->price;
                }
            }
            if ($wishlists->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your wishlist is empty.',
                    'wishlists' => []
                ], 200);
            }
            return response()->json([
                'success' => true,
                'wishlists' => $wishlists
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching the wishlist.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //***************************REMOVE USER WISHTLIST ITEMS FUNCTION*********************//
    public function removeUserWishlistItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:wishlists,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        try {
            $userId = auth()->id();
            $wishlist = Wishlist::where('id', $request->id)
                                ->where('user_id', $userId)
                                ->firstOrFail();

            $wishlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist item removed successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove wishlist item',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
}
