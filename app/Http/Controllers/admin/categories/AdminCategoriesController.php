<?php

namespace App\Http\Controllers\admin\categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categories;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\SubCategory;
class AdminCategoriesController extends Controller
{
    // **************************************ADD CATEGORIES AND UPDATE CATEGORIES FUNCTION***********************************//
    public function addCategory(Request $request, $id = null)
    {
        try {
            $isUpdate = $id !== null;
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                // 'image' => 'required|mimes:jpeg,png,jpg|max:2048',
                'image' => $isUpdate ? 'nullable|image' : 'required|mimes:jpeg,png,jpg|max:2048',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            // Process image if present
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageUrl = $this->uploadCategoryImage($image);
            }
            // Find or create the category
            $category = $isUpdate ? Categories::findOrFail($id) : new Categories;
            $category->name = $request->name;
            $category->slug = Str::slug($request->name);
            // Update image only if a new one is provided
            if ($imageUrl) {
                $category->image = $imageUrl;
            }
            $category->save();
            return response()->json([
                'success' => true,
                'message' => $isUpdate ? 'Category updated successfully' : 'Category added successfully',
            ], 200);
        } catch (\Exception $e) {
            // Catch unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
                'error' => $e->getMessage(), // Optional: Hide in production
            ], 500);
        }
    }
    private function uploadCategoryImage($image)
    {
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('categories/image'), $imageName);
        return 'categories/image/' . $imageName;
    }

    // **************************************GET CATEGORIES FUNCTION***********************************//
    public function getCategories(){
        $allCategories = Categories::all();
        if($allCategories){
            return response()->json(['allCategories' => $allCategories, 'success' =>true],200);
        }else{
            return response()->json(['success' => false, 'message' => 'categories not found'],200);
        }
    }
    // **************************************REMOVE  CATEGORIES FUNCTION***********************************//
    public function removeCategory($id)
    {
        try {
            $category = Categories::findOrFail($id);  // This automatically handles "not found"
            $category->delete();
            return response()->json(['success' => true,'message' => 'Category removed successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false,'message' => 'Category not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error removing category: ' . $e->getMessage());
            return response()->json(['success' => false,'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
     // **************************************UPDATE CATEGORIE STATUS FUNCTION***********************************//
     public function updateCategoryStatus(Request $request,$id =null){
        $id = $request->id;
        // return $id;
        if(!$id){
            return response()->json(['success'=> false,'message' => 'Empty API data provided']);
        }
        $category = Categories::find($id);
   
        if (!$category) {
            return response()->json(['success' => false,'message' => 'Category not found'], 404); // Return 404 Not Found
        }
        $category->status = $category->status ? 0 : 1;
     
        $category->save();
        return response()->json([
            'success' => true,
            'message' => 'Category status updated successfully',
        ], 200); // Return 200 OK
    }
     // **************************************ADD AND UPDATE SUB CATEGORIES FUNCTION***********************************//
     public function addSubCategory(Request $request, $id = null)
     {
         $isUpdate = $id !== null;
         // Validation rules
         $rules = [
             'name' => 'required|string|max:255',
             'description' => 'required|string',
             'image' => $isUpdate ? 'nullable|image' : 'required|image', // Image is optional during updates
             'category_id' => $isUpdate ? 'nullable' : 'required|integer|exists:categories,id',
         ];
     
         // Validate the request
         $validator = Validator::make($request->all(), $rules);
         if ($validator->fails()) {
             return response()->json([
                 'errors' => $validator->errors()
             ], 422);
         }
     
         // Handle image upload if provided
         $imageUrl = null;
         if ($request->hasFile('image')) {
             $image = $request->file('image');
             $imageName = time() . '.' . $image->getClientOriginalExtension();
             $image->move(public_path('sub-categories/image'), $imageName);
             $imageUrl = 'sub-categories/image/' . $imageName;
         }
 
         $subCategory = $isUpdate ? SubCategory::find($id) : new SubCategory;
 
         if ($isUpdate && !$subCategory) {
             return response()->json(['error' => 'SubCategory not found'], 404);
         }
         // Assign data to SubCategory
         $subCategory->name = $request->name;
         $subCategory->slug = Str::slug($request->name);
         $subCategory->description = $request->description;
         if ($imageUrl) {
             $subCategory->image = $imageUrl;
         }
         if (!$isUpdate) {
             $subCategory->category_id = $request->category_id;
         }
         $subCategory->save();
         $message = $isUpdate ? 'Subcategory updated successfully' : 'Subcategory added successfully';
         return response()->json(['success' => true, 'message' => $message], $isUpdate ? 200 : 201);
     }
    // **************************************GET SUB CATEGORIES FUNCTION***********************************//
    public function SubCategories()
    { 
        $allSubCategories = SubCategory::with('products')->get();
        if(!$allSubCategories->isEmpty()){
            return response()->json(['success' => true , 'allSubCategories' => $allSubCategories],200);
        }
        return response()->json(['error' => 'sub categoires are empty'],404);
    }
      // **************************************REMOVE SUB CATEGORIES FUNCTION***********************************//
     public function removeSubCategory($id){
        try {
            $subCategory = SubCategory::findOrFail($id);
            $subCategory->delete();
            return response()->json(['success' => true,'message' => 'Sub category removed successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false,'message' => 'Sub category not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error removing category: ' . $e->getMessage());
            return response()->json(['success' => false,'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
    // **************************************UPDATE CATEGORIE STATUS FUNCTION***********************************//
    public function updateSubcategoryStatus(Request $request,$id =null){
        $id = $request->id;
        // return $id;
        if(!$id){
            return response()->json(['success'=> false,'message' => 'Empty API data provided']);
        }
        $subCategory = SubCategory::find($id);
    
        if (!$subCategory) {
            return response()->json(['success' => false,'message' => 'SubCategory not found'], 404); // Return 404 Not Found
        }
        $subCategory->status = $subCategory->status ? 0 : 1;
        
        $subCategory->save();
        return response()->json([
            'success' => true,
            'message' => 'subcategory status updated successfully',
        ], 200); // Return 200 OK
    }
    
}
