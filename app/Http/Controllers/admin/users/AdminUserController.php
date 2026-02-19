<?php

namespace App\Http\Controllers\admin\users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
class AdminUserController extends Controller
{
    public function users(){
        $totalCount = User::where('role', 'user')->get()->count();
            // Get today's users with the 'user' role
        $todayCount = User::where('role', 'user')
                          ->whereDate('created_at', Carbon::today())
                          ->count();
    
        return response()->json([
            'success' => true,
            'totalCount' => $totalCount,
            'todayCount' => $todayCount
        ]);
    }
}
