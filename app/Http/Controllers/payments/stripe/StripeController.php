<?php

namespace App\Http\Controllers\payments\stripe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Carbon\Carbon;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.last_name' => 'required|string',
            'shipping_address.email' => 'required|email',
            'shipping_address.phone' => 'required|string',
            'shipping_address.country' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.address' => 'required|string',

            'billing_address' => 'required|array',
            'billing_address.first_name' => 'required|string',
            'billing_address.last_name' => 'required|string',
            'billing_address.email' => 'required|email',
            'billing_address.phone' => 'required|string',
            'billing_address.country' => 'required|string',
            'billing_address.city' => 'required|string',
            'billing_address.address' => 'required|string',

            'cart' => 'required|array',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Shipping Address Save
            $shipping = Address::create([
                'user_id' => auth()->id(),
                'type' => 'shipping',
                'full_name' => $validated['shipping_address']['first_name'] . ' ' . $validated['shipping_address']['last_name'],
                'street_address' => $validated['shipping_address']['address'],
                'city' => $validated['shipping_address']['city'],
                'country_id' => $validated['shipping_address']['country'],
                'phone' => $validated['shipping_address']['phone'],
            ]);

            // Billing Address Save
            $billing = Address::create([
                'user_id' => auth()->id(),
                'type' => 'billing',
                'full_name' => $validated['billing_address']['first_name'] . ' ' . $validated['billing_address']['last_name'],
                'street_address' => $validated['billing_address']['address'],
                'city' => $validated['billing_address']['city'],
                'country_id' => $validated['billing_address']['country'],
                'phone' => $validated['billing_address']['phone'],
            ]);

            // Order Create
            $order = Order::create([
                'user_id' => auth()->id(),
                'billing_address_id' => $billing->id,
                'shipping_address_id' => $shipping->id,
                'subtotal' => $validated['total'],
                'tax' => 0,
                'shipping_fee' => 0,
                'discount' => 0,
                'total' => $validated['total'],
                'status' => 'pending',
                'shipping_method' => 'standard',
                'created_at' => Carbon::now()
            ]);

            // Order Items Save
            foreach ($validated['cart'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            // Stripe Payment Session
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => array_map(function ($item) {
                    return [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => ['name' => "Product " . $item['product_id']],
                            'unit_amount' => $item['price'] * 100,
                        ],
                        'quantity' => $item['quantity'],
                    ];
                }, $validated['cart']),
                'mode' => 'payment',
                'success_url' => env('FRONTEND_URL') . '/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('FRONTEND_URL') . '/checkout/cancel',
            ]);

            // Payment Save
            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'credit_card',
                'transaction_id' => $session->id,
                'amount' => $order->total,
                'status' => 'pending',
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'message' => 'Order placed successfully, redirecting to payment',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error processing order', 'error' => $e->getMessage()], 500);
        }
    }
}