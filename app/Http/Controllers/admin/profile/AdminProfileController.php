<?php

namespace App\Http\Controllers\admin\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminProfile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
class AdminProfileController extends Controller
{
    public function updateProfileImage(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        try {
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('profile/admin/'), $imageName);
                $imageUrl = 'profile/admin/' . $imageName;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No image file provided',
                ], 400);
            }
    
            $userId = auth()->user()->id;
    
            // Update or create admin profile
            $profile = AdminProfile::updateOrCreate(
                ['user_id' => $userId],
                ['image' => $imageUrl]
            );
    
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'profile' => $profile,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function profile()
    {
        try {
            $userId = auth()->user()->id;
            $profile = AdminProfile::with('user')->where('user_id', $userId)->first();
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found',
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'profile' => $profile,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user(); 
            if (!Hash::check($request->currentPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                ], 400);
            }
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Password change successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing the password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user(); 
            $user->first_name =$request->firstName;
            $user->last_name =$request->lastName;
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Update profile details successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while update profile.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}