<?php

namespace App\Http\Controllers\users\orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
class UserOrderController extends Controller
{
    public function orderStatus(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'status' => 'invalid_request',
                'message' => 'Session ID is required'
            ], 400);
        }
        // ✅ Match transaction_id with Payment Intent ID
        $payment = Payment::where('transaction_id', $sessionId)->first();
    
        if ($payment) {
            return response()->json([
                'success' => true,
                'status' => $payment->status,
            ]);
        }
        return response()->json([
            'success' => false,
            'status' => 'not_found',
            'message' => 'No payment found for this session ID'
        ], 404);
    }
    public function userOrders()
    {
        $userId = auth()->id();

        $orders = Order::with('orderItems.product') // Load order items and related product details
            ->where('user_id', $userId)
            ->get();
        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

}
