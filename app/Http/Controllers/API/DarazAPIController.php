<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DarazAPIController extends Controller
{
    public function handleCallback(Request $request)
    {
        // Log the incoming request for debugging
        Log::info('Daraz Callback Received', $request->all());

        // Validate the request (you can add more specific validation as needed)
        $validatedData = $request->validate([
            'order_id' => 'required|string',
            'status' => 'required|string',
            'product_data' => 'nullable|array',
            // Add more fields depending on what data Daraz sends
        ]);

        // Process the callback data (for example, update the order status)
        // $order = Order::where('daraz_order_id', $validatedData['order_id'])->first();
        // if ($order) {
        //     $order->status = $validatedData['status'];
        //     $order->save();
        // }

        return response()->json(['message' => 'Callback received and processed successfully'], 200);
    }
}
