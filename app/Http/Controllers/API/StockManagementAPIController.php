<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockManagementRequest;
use App\Http\Resources\StockManagementCollection;
use App\Http\Resources\StockManagementResource;
use App\Models\ComboProduct;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\Warehouse;
use App\Repositories\StockManagementRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Illuminate\Support\Facades\Validator;

class StockManagementAPIController extends AppBaseController
{

    public function __construct(StockManagementRepository $stockManagementRepository)
    {
        $this->stockManagementRepository = $stockManagementRepository;
    }
    public  function getStockByCode($code)
    {
        // Find the product by its code
        $product = Product::where('code', $code)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Find the stock information
        $stock = ManageStock::where('product_id', $product->id)->first();
        if (!$stock) {
            return response()->json(['error' => 'Stock information not found'], 404);
        }
        $stockManage = $this->stockManagementRepository->find($stock->id);

        if (!$stockManage) {
            return response()->json(['error' => 'Stock information not found'], 404);
        }

        // Return the stock quantity
        return new StockManagementResource($stockManage);
    }



    public  function  updateStock(StockManagementRequest $request)
    {
       $stock = $this->stockManagementRepository->updateSingleProductStock(
           $request->all()
       );
       return new StockManagementResource($stock);
    }

    public function webHookOrder(Request $request)
    {
        // Define validation rules
        $rules = [
            'warehouse' => 'required|string',
            'tax_rate' => 'required|numeric',
            'discount' => 'required|numeric',
            'shipping' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'received_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'payment_type' => 'required|integer',
            'date' => 'required|date',
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'customer.country' => 'required|string',
            'customer.city' => 'required|string',
            'customer.address' => 'required|string',
            'customer.dob' => 'required|date',
            'items' => 'required|array',
            'items.*.code' => 'required|string',
            'items.*.warehouse' => 'required|string',
            'items.*.quantity' => 'required|integer',
        ];

         $warehouse = Warehouse::where('country_code', $request->warehouse)->first();
        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $response = [];
        // Loop through each item to check the code
        foreach ($request->items as $item) {
            // Extract the first 4 characters from the code
            $firstFourChars = substr($item['code'], 0, 4);

            // Check if the first four characters are 'COMB'
            if ($firstFourChars === 'COMB') {
                // If true, perform some logic for codes starting with 'COMB'
                // e.g., handle combo products
                // Add your logic here for combo products
                $combos = ComboProduct::where('code', $item['code'])->pluck('product_id')->get();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Combo product detected: ' . $item['code']
                ], 200);
            }
                // If false, perform different logic for other codes
                // Add your logic here for regular products
                return response()->json([
                    'status' => 'success',
                    'message' => 'Regular product detected: ' . $item['code'],
                    'data'=> $warehouse
                ], 200);

        }
        // Process the data (you would typically save it to the database here)

    }

}
