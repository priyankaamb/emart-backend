<?php

namespace App\Http\Controllers\admin\product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Product;
class AdminProductController extends Controller
{
     // **************************************ADD PRODUCT FUNCTION***********************************//
    //  public function addProduct(Request $request,$id =null){

    //     $isUpdate = $id !== null;
    //     $validator = Validator::make($request->all(),[
    //         'selectedSubcategory' => $isUpdate ? 'nullable':'required',
    //         'name'                => 'required|string|max:255',
    //         'price'               => 'required|numeric|min:0',
    //         'discountPrice'       => 'required|numeric|min:0|lt:price',
    //         'stockQuantity'       => 'required|integer|min:1',
    //         'image'               => $isUpdate ?'nullable':'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    //         'description'         => 'required|string',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'errors' => $validator->errors(),
    //         ], 400);
    //     }
    //     $imageUrl = null;
    //     if($request->hasFile('image')){
    //         $image = $request->image;
    //         $imageName = time() . '.' . $image->getClientOriginalExtension();
    //         $image->move(public_path('/products/image'),$imageName);
    //         $imageUrl = 'products/image/'. $imageName;
    //     }
    //     $product = $isUpdate ? Product::find($id): new Product
    //     $sku = strtoupper(substr($request->name, 0, 3)) . rand(100, 999); 
    //     $slugGenerate = strtolower(substr($request->name, 0, 3)); 
    //     $uniqueSlug = $slugGenerate.'-'.Str::slug($request->name);
    //     $product = new Product;
    //     $product->name = $request->name;
    //     $product->slug = $uniqueSlug;
    //     $product->price = $request->price;
    //     $product->subcategory_id = $request->selectedSubcategory;
    //     $product->discount_price = $request->discountPrice;
    //     $product->description     = $request->description;
    //     $product->stock           = $request->stockQuantity;
    //     $product->sku             = $sku;
    //     $product->image           = $imageUrl;
    //     $product->save();
    //     return response()->json(['success' => true ,'msg' => 'Product created successfully'], 201);
    // }
    public function addProduct(Request $request, $id = null)
    {   

        $isUpdate = $id !== null;

        try {
            $validator = Validator::make($request->all(), [
                'selectedSubcategory' => $isUpdate ? 'nullable' : 'required',
                'name'                => 'required|string|max:255',
                'price'               => 'required|numeric|min:0',
                'discountPrice'       => 'required|numeric|min:0|lt:price',
                'stockQuantity'       => 'required|integer|min:1',
                'image'               => $isUpdate ? 'nullable' :'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description'         => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors(),
                ], 400);
            }
            if ($request->price < $request->discountPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount price cannot be higher than the regular price.'
                ], 400);
            }
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $image = $request->image;
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('/products/image'),$imageName);
                $imageUrl = 'products/image/' . $imageName;
            }
            $product = $isUpdate ? Product::findOrFail($id) : new Product;
            $sku = strtoupper(substr($request->name, 0, 3)) . rand(100, 999);
            $slugGenerate = strtolower(substr($request->name, 0, 3));
            $uniqueSlug = $slugGenerate . '-' . Str::slug($request->name);
            $product->name = $request->name;
            $product->slug = $uniqueSlug;
            $product->price = $request->price;
            $product->discount_price = $request->discountPrice;
            $product->description = $request->description;
            $product->stock = $request->stockQuantity;
            $product->sku = $sku;
            if (!$isUpdate) {
                $product->subcategory_id = $request->selectedSubcategory;
            }
            if ($imageUrl) {
                $product->image = $imageUrl;
            }
            $product->save();
            return response()->json(['success' => true, 'msg' => 'Product created/updated successfully'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
    // **************************************GET PRODUCTS  FUNCTION***********************************//
    public function products(){
        $products = Product::all();
        if ($products->isNotEmpty()) {
            return response()->json(['success' => true, 'products' => $products, 'minPrice'=> $products->min('price'), 'maxPrice' => $products->max('price')], 200);
        }
        return response()->json(['error' => 'Product not found or products are empty'], 404);
    }
    // **************************************REMOVE SUB CATEGORIES FUNCTION***********************************//
    public function removeProduct($id){
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json(['success' => true,'message' => 'Product removed successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false,'message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false,'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
