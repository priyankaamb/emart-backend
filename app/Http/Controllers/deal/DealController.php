<?php

namespace App\Http\Controllers\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Deal;
use Illuminate\Support\Facades\Validator;
class DealController extends Controller
{
    public function createDeal(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'discount_type'   => 'required|in:percentage,fixed',
            'discount_value'  => 'required|numeric|min:0',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'is_active'       => 'required|boolean',
            'products'        => 'required|array',
            'products.*'      => 'exists:products,id',
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        };
        $start_date_ist = convertToIST($request->start_date);
        $end_date_ist   = convertToIST($request->end_date);
        // Create the deal
        $deal = Deal::create([
            'name'            => $request->name,
            'description'     => $request->description,
            'discount_type'   => $request->discount_type,
            'discount_value'  => $request->discount_value,
            'start_date'      => $start_date_ist,
            'end_date'        => $end_date_ist,
            'is_active'       => $request->is_active,
        ]);

        $deal->products()->sync($request->products);
        return response()->json([
            'success' => true,
            'message' => 'Deal created successfully',
        ],201);
    }
    public function getCurrentDeals()
    {
        try {
            // Get current time in IST this function define hepler.php file inside
            $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));

            $deals = Deal::where('start_date', '<=', $currentDateTime)
                         ->where('end_date', '>=', $currentDateTime)
                         ->where('is_active', 1)
                         ->with('products')
                         ->get();
            return response()->json([
                'success' => true,
                'deals' => $deals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching current deals',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
