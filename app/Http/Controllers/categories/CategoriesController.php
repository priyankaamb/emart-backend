<?php

namespace App\Http\Controllers\categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categories;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\SubCategory;
use App\Models\Product;
class CategoriesController extends Controller
{
   
    // **************************************FIND SUB CATEGORIES FUNCTION***********************************//
    public function fetchSubCategory($id)
    {
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Empty API data provided'], 400);
        }
        $subCategory = SubCategory::where([['category_id', $id ],['status',1]])->get();
        if($subCategory->isEmpty()){
            return response()->json(['error' => 'subcategory not found'],404);
        }else{
            return response()->json(['success' => true , 'subcategory' => $subCategory],200);
        }
    }
    // **************************************GET CATEGORIES FUNCTION***********************************//
    public function Categories(){
        $allCategories = Categories::where('status',1)->get();
        if(!$allCategories->isEmpty()){
            return response()->json(['allCategories' => $allCategories, 'success' =>true],200);
        }else{
            return response()->json(['success' => false, 'message' => 'categories not found'],200);
        }
    }
    // **************************************SEARCH CATEGORIES FUNCTION***********************************//
    public function searchCategories($query){
        if(!$query){
            return response()->json(['success' => false, 'message' => 'Empty API query']);
        }
        $categories = Categories::where('name','like',"%{$query}%")->get();
        if($categories->isEmpty()){
            return response()->json(['success' => false, 'message' => 'categories not found'],404);
        };
        return response()->json(['success' => true, 'message' => 'categories find successfully', 'categories' => $categories],200);
    }

    // **************************************SEARCH SUB CATEGORIES FUNCTION***********************************//
    public function searchSubCategories($query,$catId){
        if (!trim($query) || !$catId) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid search parameters'
            ], 400);
        }
        $subCategories = SubCategory::where('category_id', $catId)
                                    ->where('name', 'like', "%{$query}%")
                                    ->get();
        if ($subCategories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategories not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Subcategories found successfully',
            'subCategories' => $subCategories
        ], 200);
    }

   
}
