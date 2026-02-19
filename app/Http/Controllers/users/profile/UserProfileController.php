<?php

namespace App\Http\Controllers\users\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserProfile;
use App\Models\User;
class UserProfileController extends Controller
{
    //*********************USER PROFILE IMAGE FUNCTION CREATE AND UPDATE************************//
    public function changeProfileImage(Request $request){
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
                $image->move(public_path('profile/users/'), $imageName);
                $imageUrl = 'profile/users/' . $imageName;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No image file provided',
                ], 400);
            }
            $userId = auth()->user()->id;
            $profile = UserProfile::updateOrCreate(
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
    //*********************USER PROFILE IMAGE  GET FUNCTION ***********************//
    public function userProfileImage()
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
            }
            $userProfileImage = UserProfile::where('user_id', $userId)->first();

            if (!$userProfileImage) {
                return response()->json(['success' => false, 'message' => 'Profile image not found'], 404);
            }
            return response()->json([
                'success' => true,
                'userProfile' => $userProfileImage,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }
    //*********************USER PROFILE DATA GET FUNCTION ***********************//
    public function userProfile()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access. Please log in again.'
                ], 401);
            }
            return response()->json([
                'success' => true,
                'userData' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching profile data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function UserUpdateProfile(Request $request) 
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'nullable|digits:10',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:255',
            ]);
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            $user->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'userData' => $user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}
