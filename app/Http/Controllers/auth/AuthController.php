<?php

namespace App\Http\Controllers\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Queue;
use Hash;
use Auth;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    public function signUp(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',       
            // 'last_name'  => 'required|string|max:255',      
            'phone'      => 'required|max:15|unique:users',  
            'address'    => 'required|string|max:500',       
            'email'      => 'required|email|unique:users,email|max:255',  
            'password'   => 'required|string|min:6|',
            'city'       => 'required|string|max:500',
            'country'    => 'required',   
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
       $user = new User;
       $user->first_name = $request->firstName;
       $user->last_name = $request->lastName;
       $user->phone = $request->phone;
       $user->address = $request->address;
       $user->city = $request->city;
       $user->email = $request->email;
       $user->password = Hash::make($request->password);
       $user->role = 'user'; 
       $user->country_id = $request->country;
       $user->save();
       event(new Registered($user));
    //    Log::info('Registered event triggered for user', [
    //         'email' => $user->email,
    //         'id' => $user->id,
    //     ]);
       
       return response()->json([
           'message' => 'Registration successful. Please verify your email.', 'userId'=> $user->id,
       ], 201);
    //    $credentials = $request->only('email', 'password');
    //    if (Auth::attempt($credentials)) {
    //         $user = Auth::user();
    //         $token = $user->createToken('login_token')->plainTextToken;
    //         return response()->json([
    //             'message' => 'Registration and login successful',
    //             'token'   => $token,
    //             'role'    => $user->role,
    //             'userId'  => $user->id,
    //         ], 200);
    //     }
    }

    //************************LOGIN FUNCTION*********************//
    // public function login(Request $request)
    // {
    //     $validate = Validator::make($request->all(),[
    //         'email' => 'required',
    //         'password'  =>  'required',
    //        ]);
    //        if($validate->fails()){
    //             return response()->json([
    //                'success' => false,
    //                'message' => 'validation error',
    //                'error'  =>  $validate->errors(),
    //             ],422);
    //        };
    //        $credential = $request->only('email','password');
    //        if(!Auth::attempt($credential)){
    //          return response()->json(['message' => 'Invalid login details'],401);
    //        }
    //        $user = Auth::user();
    //        $token = $user->createToken('login_token')->plainTextToken;
    //        return response()->json(['message' => 'Login successfully','token' =>$token,'role' => $user->role,'userId' =>$user->id]);
    // }

    public function login(Request $request)
    {
        // Get the Authorization header
        $authHeader = $request->header('Authorization');
   

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return response()->json(['message' => 'Missing or invalid authorization header'], 401);
        }

        // Extract and decode the Base64 credentials
        $base64Credentials = substr($authHeader, 6);
        $decodedCredentials = base64_decode($base64Credentials);

        if (!$decodedCredentials || !str_contains($decodedCredentials, ':')) {
            return response()->json(['message' => 'Invalid credentials format'], 401);
        }

        // Split into email and password
        list($email, $password) = explode(':', $decodedCredentials, 2);

        // Validate credentials
        $validate = Validator::make(['email' => $email, 'password' => $password], [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'error' => $validate->errors(),
            ], 422);
        }

        // Attempt login
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        // Get authenticated user
        $user = Auth::user();
        $token = $user->createToken('login_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'role' => $user->role,
            'userId' => $user->id,
        ]);
    }


    //**********************LOGOUT FUNCTION********************//
    public function logout(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user->tokens()->delete();
        return response()->json(['success' => 'Logout successfully','status' => 200], 200);
    }

    // public function checkEmailVerification(Request $request)
    // {
    //     $user = User::findOrFail($request->userId);
    //     $token = $user->createToken('login_token')->plainTextToken;
    //     if ($user->hasVerifiedEmail()) {
    //         return response()->json([
    //             'verified' => true,
    //             'message' => 'Email is verified.',
    //             'role'    => $user->role,
    //             'userId'  => $user->id,
    //         ]);
    //     }
    
    //     return response()->json([
    //         'verified' => false,
    //         'message' => 'Email is not verified yet. Please check your inbox.',
    //     ], 403);
    // }
    
    //*********************EMAIL VERIFY FUNCTION*****************************//
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect(env('FRONTEND_URL') . '/email-verification-failed?error=user_not_found');
        }

        // Validate hash manually
        $expectedHash = sha1($user->getEmailForVerification());
        if (!hash_equals((string) $hash, $expectedHash)) {
            return redirect(env('FRONTEND_URL') . '/email-verification-failed?error=invalid_hash');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONTEND_URL') . '/email-verification-success?message=already_verified');
        }

        // Verify the email
        $user->markEmailAsVerified();
        event(new Verified($user));

        // Generate authentication token
        $token = $user->createToken('login_token')->plainTextToken;

        // Redirect with token and user data
        return redirect(env('FRONTEND_URL') . "/email-verification-success?message=verified&userId={$user->id}&role={$user->role}&token={$token}");
    }
    

    // //************************RESEND VERIFICATION FUNCTION******************//
    // public function resendVerificationEmail(Request $request)
    // {
    //     if ($request->user()->hasVerifiedEmail()) {
    //         return response()->json(['message' => 'Email already verified.'], 400);
    //     }

    //     $request->user()->sendEmailVerificationNotification();

    //     return response()->json(['message' => 'Verification link sent.'], 200);
    // }
}
