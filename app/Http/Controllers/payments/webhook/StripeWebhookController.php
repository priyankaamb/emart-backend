<?php

namespace App\Http\Controllers\payments\webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Cart;
class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log::info('🔔 Stripe Webhook Received', ['headers' => $request->headers->all(), 'body' => $request->getContent()]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        if (!$sig_header) {
            // Log::error('❌ Missing Stripe Signature');
            return response()->json(['error' => 'Missing Stripe Signature'], 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            // Log::error('❌ Invalid Payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid Payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Log::error('❌ Invalid Signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        // ✅ Checkout Session Completed
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            // Log::info('✅ Payment successful for session: ' . $session->id);

            // Payment lookup
            $payment = Payment::where('transaction_id', $session->id)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'completed'
                ]);

                // Order lookup
                $order = $payment->order;
                if ($order) {
                    $order->update(['status' => 'delivered']);
                    // Log::info('✅ Order delivered, Order ID: ' . $order->id);

                    // 🛒 Remove user cart items
                    $userId = $order->user_id;
                    Cart::where('user_id', $userId)->delete();
                    // Log::info('🗑️ Cart cleared for user ID: ' . $userId);
                }
            } else {
                Log::warning('⚠️ No Payment found for checkout session: ' . $session->id);
            }
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }

   
    
}

