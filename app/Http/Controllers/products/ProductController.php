<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Product;
class ProductController extends Controller
{
    //******************************FETCH PRODUCT FUNCTION******************************* //
    public function fetchProduct($id)
    {
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid subcategory ID'
            ], 400);
        }
        $products = Product::where('subcategory_id', $id)->get();
        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'msg' => 'Product not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'msg' => 'Products fetched successfully',
            'products' => $products,
            'maxPrice' => $products->max('price'),
            'minPrice' => $products->min('price')
        ], 200);
    }
    // **************************************GET PRODUCTS  FUNCTION***********************************//
    public function products(){
        $products = Product::all();
        if ($products->isNotEmpty()) {
            return response()->json(['success' => true, 'products' => $products, 'minPrice'=> $products->min('price'), 'maxPrice' => $products->max('price')], 200);
        }
        return response()->json(['error' => 'Product not found or products are empty'], 404);
    }
    // **************************************GET PRODUCTS  FUNCTION***********************************//
    public function productDetails($slug = null, $id = null)
    {
        if ($slug) {
            $product = Product::where('slug', $slug)->first();
        } elseif ($id) {
            $product = Product::find($id);
        }
        if (isset($product)) {
            return response()->json(['success' => true, 'product' => $product], 200);
        }
        if (!$slug && !$id) {
            $products = Product::all();
            if ($products->isNotEmpty()) {
                return response()->json(['success' => true, 'products' => $products], 200);
            }
        }
        return response()->json(['error' => 'Product not found or products are empty'], 404);
    }

    // **************************************FILTER PRODUCTS BY SUB CATEGORIES PRICE FUNCTION***********************************//
    public function filterPrice($id, $min, $max)
    {
        if (!is_numeric($min) || !is_numeric($max) || !is_numeric($id)) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters.'], 400);
        }
        if ($min > $max) {
            return response()->json(['success' => false, 'message' => 'Minimum price cannot be greater than maximum price.'], 400);
        }
        try {
            $products = Product::where('subcategory_id', $id)
                ->whereBetween('price', [(float) $min, (float) $max])
                ->get();
            if ($products->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No products found in this price range.'], 404);
            }
            return response()->json([
                'success' => true,
                'products' => $products
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // **************************************LATEST PRODUCT FUNCTION***********************************//
    public function latestProducts(){

        $products = Product::orderBy('created_at','desc')->take(4)->get();
        if($products->isEmpty()){
            return response()->json(['success' => false, 'message' => 'latest product not found']);
        }
        if($products){
            return response()->json([
                'success' => true,
                'products' => $products
            ], 200);
        }
    }

    // **************************************PRODUCTS FILTER PRICE FUNCTION***********************************//
    public function productsFilterPrice($min,$max){
        $products = Product::whereBetween('price', [$min, $max])->get();
        if($products->isEmpty()){
            return response()->json(['success' => false, 'message' =>'No products found in this price range.']);
        }
        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }
}
