<?php

namespace App\Http\Controllers\API;

use App\Services\ApiService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockManagementRequest;
use App\Http\Resources\StockManagementCollection;
use App\Http\Resources\StockManagementResource;
use App\Models\ComboProduct;
use App\Models\Customer;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesPayment;
use App\Models\Warehouse;
use App\Repositories\StockManagementRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Exception;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;


class StockManagementAPIController extends AppBaseController
{
    protected  $apiService;
    public function __construct(StockManagementRepository $stockManagementRepository, ApiService $apiService)
    {
        $this->stockManagementRepository = $stockManagementRepository;
        $this->apiService = $apiService;
    }
    public  function getStockByCode(Request $request)
    {
       try{
           // Find the product by its code
           $product = Product::where('code', $request->code)->first();


           // Find the warehouse by its country code
           $warehouse = Warehouse::where('country_code', $request->wearhouse)->first();


           // Find the stock information
           $stock = ManageStock::whereWarehouseId($warehouse->id)->whereProductId($product->id)->first();


           $stockManage = $this->stockManagementRepository->find($stock->id);

           // Return the stock quantity
           return response()->json([
               'status' => 'success',
               'quantity' => $stockManage->quantity,
           ], 200);

       }catch (Exception $e){
           return response()->json([
               'status' => 'failed',
               'quantity' => 0,
           ]);
       }
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
        DB::beginTransaction();
        try {

            // Define validation rules
            $rules = [
                'warehouse' => 'required|string',
                'order_no' => 'required|string|unique:sales',
                'tax_rate' => 'required|numeric',
                'discount' => 'required|numeric',
                'shipping' => 'required|numeric',
                'grand_total' => 'required|numeric',
                'received_amount' => 'required|numeric',
                'paid_amount' => 'required|numeric',
                'payment_type' => 'required|integer',
                'currency' => 'required|string|max:3',
                'date' => 'required|date',
                'customer' => 'required|array',
                'customer.name' => 'required|string|max:255',
                'customer.email' => 'required|email',
                'customer.phone' => 'required|string',
                'customer.country' => 'required|string',
                'customer.city' => 'required|string',
                'customer.address' => 'required|string',
                'customer.dob' => 'nullable|date',
                'items' => 'required|array',
                'items.*.code' => 'required|string',
                'items.*.quantity' => 'required|integer',
                'items.*.price' => 'required|numeric',
            ];

            // Validate the request
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create the customer
            // Check if the customer already exists by email
            $existCustomer = Customer::where('email', $request->input('customer.email'))->first();
            if ($existCustomer) {
                // Use the existing customer
                $customer = $existCustomer;
            } else {
                // Create a new customer
                $customer = Customer::create([
                    'name' => $request->input('customer.name'),
                    'email' => $request->input('customer.email'),
                    'phone' => $request->input('customer.phone'),
                    'country' => $request->input('customer.country'),
                    'city' => $request->input('customer.city'),
                    'address' => $request->input('customer.address'),
                    'dob' => $request->input('customer.dob'),
                ]);
            }

            // Fetch the warehouse by country code
            $warehouse = Warehouse::where('country_code', $request->warehouse)->first();

            if (!$warehouse) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Warehouse not found.'
                ], 404);
            }

            // Additional fields not directly available in the request
            $additionalFields = [
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id
            ];

            // Extract sale input data and merge additional fields
            $saleInputArray = array_merge(
                Arr::only($request->all(), [
                    'tax_rate', 'tax_amount', 'discount', 'shipping', 'grand_total',
                    'received_amount', 'paid_amount', 'status', 'payment_type', 'payment_status', 'note', 'date',
                    'market_place', 'order_no', 'country', 'currency'
                ]),
                [
                    'cod' => $request->input('cod', 0) // If currency exists, store it; otherwise, null
                ],
                [
                    'order_type' => $request->input('order_type', 1) // If order_type exists, store it; otherwise, null
                ],
                $additionalFields
            );

            // Create the sale record
            $sale = Sale::create($saleInputArray);
            // Check if the sale was created successfully
            if (!$sale || !$sale->id) {
                throw new UnprocessableEntityHttpException('Sale record could not be created.');
            }

            // Array to store sale items data
            $saleItemsData = [];
            $processedProductIds = [];
            $preparedItems = [];
            $comboRelatedItems = [];
            $operation = 'inventory';
            // Process each item
            foreach ($request->items as $item) {
                // Extract the first 5 characters from the code
                $firstFiveChars = substr($item['code'], 0, 5);

                if ($firstFiveChars === 'COMBO') {
                    // Handle combo products
                    $comboProductIds = ComboProduct::where('code', $item['code'])
                        ->where('warehouse_id', $warehouse->id)
                        ->pluck('product_id');

                    $relatedProductData = [];
                    $smallestQuantity = []; // To store the smallest quantity from related products

                    foreach ($comboProductIds as $productId) {
                        $product = Product::find($productId);

                        // Perform stock management locally for each related product
                            $this->managedStock($product, $item, $sale, $warehouse, $saleItemsData, $managedType = null);

                            // Prepare and save sale item
                            $saleItem = $this->prepareAndSaveSaleItem($product, $item, $sale);
                            $processedProductIds[] = $product->id;


                        // Get the stock quantity for this product in the warehouse
                        $manageStockProduct = ManageStock::where('warehouse_id', $warehouse->id)
                            ->where('product_id', $productId)
                            ->first();

                        if ($manageStockProduct) {
                            // Calculate the smaller quantity between requested and available
                            $smallestQuantity[] = $manageStockProduct->quantity;

                            // Store each related product SKU and its own quantity for the second operation
                            $comboRelatedItems[] = [
                                'sku' => $product->code,
                                'quantity' => $manageStockProduct->quantity, // Use stock quantity here for each related product
                            ];

                            // Determine the smallest quantity for the combo as a whole


                        }
                    }

                    // First Operation: Add the combo SKU with the smallest quantity
                    if ($smallestQuantity !== null) {
                        $preparedItems[] = [
                            'sku' => $item['code'], // Combo SKU
                            'quantity' => min($smallestQuantity), // Smallest quantity among the related products
                        ];
                    }

                } else {

                    // Handle regular products
                    $product = Product::where('code', $item['code'])->first();
                     // Perform stock management locally
                        $this->managedStock($product, $item, $sale, $warehouse, $saleItemsData, $managedType = null);

                        // Prepare and save sale item
                        $saleItem = $this->prepareAndSaveSaleItem($product, $item, $sale);
                        $processedProductIds[] = $product->id;

                    $manageStockProduct = ManageStock::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $product->id)
                        ->first();

                    if ($product && $manageStockProduct) {
                        // Add the regular product to the preparedItems array
                        $preparedItems[] = [
                            'sku' => $product->code, // Product SKU
                            'quantity' => $manageStockProduct->quantity, // Stock quantity
                        ];

                        // Check if the regular product is part of any combo
                        $comboProductIds = ComboProduct::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouse->id)
                            ->pluck('combo_id'); // Retrieve all combo IDs where this product is included

                        if ($comboProductIds->isNotEmpty()) {
                            foreach ($comboProductIds as $comboId) {
                                // Get all products in this combo
                                $comboRelatedProductIds = ComboProduct::where('combo_id', $comboId)
                                    ->where('warehouse_id', $warehouse->id)
                                    ->pluck('product_id');

                                $smallestQuantity = null; // To store the smallest quantity of combo-related products

                                foreach ($comboRelatedProductIds as $comboProductId) {
                                    $comboProduct = Product::find($comboProductId);
                                    $comboStockProduct = ManageStock::where('warehouse_id', $warehouse->id)
                                        ->where('product_id', $comboProductId)
                                        ->first();

                                    if ($comboProduct && $comboStockProduct) {
                                        // Get the smaller quantity between requested and available
                                        $currentComboProductQuantity = min($item['quantity'], $comboStockProduct->quantity);

                                        // Determine the smallest quantity for the combo-related products
                                        if ($smallestQuantity === null || $currentComboProductQuantity < $smallestQuantity) {
                                            $smallestQuantity = $currentComboProductQuantity;
                                        }

                                        // Add each combo-related product's SKU and its own quantity
                                        $comboRelatedItems[] = [
                                            'sku' => $comboProduct->code,
                                            'quantity' => $comboStockProduct->quantity, // Use the stock quantity of the combo-related product
                                        ];
                                    }
                                }

                                // After checking all related products, store the combo's smallest quantity if applicable
                                if ($smallestQuantity !== null) {
                                    $preparedItems[] = [
                                        'sku' => 'COMBO: ' . $comboId, // Combo SKU (you can use a relevant naming convention)
                                        'quantity' => $smallestQuantity, // Smallest quantity among related products
                                    ];
                                }
                            }
                        }


                    }
                }
            }

            // First call: Combo products (first operation) and regular products
            if (!empty($preparedItems)) {
//                return response()->json([
//                    'warehouse' => $warehouse->country_code,
//                    'operation' => $operation,
//                    'items' => $preparedItems,
//                ]);
                $this-> manageStock(
                    $warehouse->country_code,
                    $operation,
                    $preparedItems
                );
            }

// Second call: Combo-related products (second operation)
            if (!empty($comboRelatedItems)) {
//                return response()->json([
//                    'warehouse' => $warehouse->country_code,
//                    'operation' => $operation,
//                    'items' => $comboRelatedItems,
//                ]);
                $this->manageStock(
                    $warehouse->country_code,
                    $operation,
                    $comboRelatedItems
                );
            }
            //Payment
            if ($sale->payment_status == Sale::PAID) {
                $sale->paid_amount = $sale->grand_total;
                SalesPayment::create([
                    'sale_id' => $sale->id,
                    'payment_date' => Carbon::now(),
                    'payment_type' => $sale->payment_type,
                    'amount' => $sale->paid_amount,
                    'received_amount' => $sale->paid_amount,
                ]);
            } elseif ($sale->payment_status == Sale::UNPAID) {
                $sale->paid_amount = 0;
            }

            // Generate reference code
            $sale->reference_code = getSettingValue('sale_code') . '_111' . $sale->id;
            $sale->save();  // Update the sale record

            // Fetch updated stock information
            $stocks = ManageStock::where('warehouse_id', $warehouse->id)
                ->whereIn('product_id', $processedProductIds)
                ->get();


            DB::commit();
            // Final response
            return response()->json([
                'status' => 'success',
                'message' => 'Webhook Order processed successfully',
                'data' => [
                    'customer' => $customer,
                    'sale' => $sale,
                    'sale_items' => $saleItemsData,
                    'warehouse' => $warehouse,
                    'stocks' => $stocks
                ]
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            // Log the error for more detailed debugging
            \Log::error('Webhook Order Processing Error: ' . $e->getMessage(), [
                'stack_trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            $errorResponse = [
                'error' => true,
                'message' => $e->getMessage(),
                'code' => 422, // or any other relevant HTTP status code
            ];

            return response()->json($errorResponse, 422);
        }

    }

    /**
     * Process each product and manage stock
     */


    /**
     * Prepare sale item input and save it
     */
    private function prepareAndSaveSaleItem($product, $item, $sale)
    {
        // Prepare sale item input
        $input = [
            'product_id' => $product->id,
            'sale_id' => $sale->id,
            'product_price' => $item['price'],
            'net_unit_price' => $item['price'],
            'tax_type' => $item['tax_type']??0, // 1:Exclusive, 2:Inclusive
            'tax_value' => $item['tax_value']??0,
            'tax_amount' => $item['tax_amount']??0,
            'discount_type' => $item['discount_type']??2, //1:percentage, 2:Fixed
            'discount_value' => $item['discount_value']??0,
            'discount_amount' => $item['discount_amount']??0,
            'sale_unit' => $item['sale_unit']??1,   //1:piece, 2:meter, 3:kilogram
            'quantity' => $item['quantity'],
            'sub_total' => $item['quantity'] * $item['price'],
        ];

        // Create a new SaleItem instance
        $saleItem = new SaleItem($input);

        // Save the sale item using the relationship
        $sale->saleItems()->save($saleItem);



        // Return the saved sale item if needed
        return $saleItem;
    }

    public function webHookOrderCancel(Request $request){
        $input = $request->all();

        $sale = Sale::where('order_no', $input['order_no'])->first();

    }

    public function webHookOrderReturn(Request $request){
            $input = $request->all();

            $sale = Sale::where('order_no', $input['order_no'])->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook Order return successfully',
                'data' => $sale,
            ]);
    }




   //   Central Stock Managed Function
    private function managedStock($product, $item, $sale, $warehouse, $saleItemsData, $managedType)
    {
        if (!$product) {
            throw new UnprocessableEntityHttpException('Product not found.');
        }

        if (isset($product->quantity_limit) && $item['quantity'] > $product->quantity_limit) {
            throw new UnprocessableEntityHttpException('Please enter less than ' . $product->quantity_limit . ' quantity of ' . $product->name . ' product.');
        }

        $manageStockProduct = ManageStock::whereWarehouseId($warehouse->id)->whereProductId($product->id)->first();
        if ($manageStockProduct && $manageStockProduct->quantity >= $item['quantity']) {
            $totalQuantity = $manageStockProduct->quantity - $item['quantity'];
            $manageStockProduct->update([
                'quantity' => $totalQuantity,
            ]);
        } else {
            throw new UnprocessableEntityHttpException('Quantity must be less than available quantity.');
        }


    }




//    public function manageStock(Request $request)
//    {
//        // Step 1: Log in and get the token
//        $loginResponse = $this->apiService->login('admin@gmail.com', 'Admin@123', 1);
//        return response()->json($loginResponse);
//        if ($loginResponse['isSuccess']) {
//            // Step 2: Prepare the data to send to the API
////            $warehouse = 'SI';
////            $operation = 'sell';
////            $items = [
////                [
////                    'sku' => 'GER-tar-2024',
////                    'quantity' => 5
////                ]
////            ];
////
////            // Step 3: Send the stock manage request
////            $stockResponse = $this->apiService->manageStockBySku($warehouse, $operation, $items);
//
//            // Step 4: Return the response
//            return response()->json($loginResponse);
//        }
//
//        return response()->json(['error' => 'Login failed']);
//    }



    public function prepareStockItems($warehouse_id, $warehouse_code, $items, $operation)
    {
        // Collect all product IDs from the input items
        $productIds = array_column($items, 'product_id');

        // Fetch all products in one query
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Fetch all manageStock records for the given warehouse in one query
        $manageStockRecords = ManageStock::whereWarehouseId($warehouse_id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $preparedItems = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $manageStockProduct = $manageStockRecords->get($item['product_id']);

            // Ensure the product and stock entry exist
            if ($product && $manageStockProduct) {
                $preparedItems[] = [
                    'sku' => $product->code, // Product SKU
                    'quantity' => $manageStockProduct->quantity, // Stock quantity
                ];
            }
        }

        if (empty($preparedItems)) {
            return response()->json(['error' => 'No valid items found for this operation.'], 400);
        }

        // Now call the manageStock function with the prepared data
        return $this->manageStock($warehouse_code, $operation, $preparedItems);
    }



    public function manageStock($warehouse, $operation, $items)
    {
        // Attempt to retrieve the cached token
        $token = Cache::get('api_token');

        // If token does not exist or is invalid, login to get a new token
        if (!$token) {
            $loginResponse = $this->apiService->login('admin@gmail.com', 'Admin@123', 1);
            if ($loginResponse['isSuccess']) {
                $token = Cache::get('api_token'); // Retrieve the newly cached token
            } else {
                // If login failed, return an error response
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Login failed'
                ], 401);
            }
        }

        // Proceed with the stock management API call using the token
        $stockResponse = $this->apiService->manageStockBySku($warehouse, $operation, $items);

        // Return the response from the stock management API call
        return response()->json($stockResponse);
    }




//    function stockManagedbyWarehouse(){
////        $countryQuery = "not in (1)";
//        $countryQuery = "= 1";
//
//        $query = DB::connection('pgsql')->table('product_meta')
//            ->select('q.id', 'q.sku', 'q.variants')
//            ->fromSub(function ($subQuery) use ($countryQuery) {
//                $subQuery->select('id', 'variants', DB::raw("(jsonb_array_elements(details)->>'sku') AS sku"))
//                    ->fromSub(function ($innerQuery) use ($countryQuery) {
//                        $innerQuery->select('id', 'variants', 'country_id', DB::raw("jsonb_array_elements(variants)->'variantDetails' AS details"))
//                            ->from('product_meta')
//                            ->where('is_active', true)
//                            ->whereRaw("country_id {$countryQuery}");
//                    }, 'sub');
//            }, 'q')
////           ->where('q.sku', $item->sku)
//            ->groupBy('q.id', 'q.sku', 'q.variants')
//
//            ->get();
//
//
//
//        dd($query );
//
//    }






    function stockManagedbyWarehouse($warehouseId) {
      ini_set('max_execution_time', '0');
      ini_set('memory_limit', '-1');

        // Determine the country condition based on warehouse_id
        $countryCondition = ($warehouseId == 3) ? '=' : '!=';

        // Step 1: Retrieve manage_stocks data
        $manageStocks = DB::table('manage_stocks')
            ->join('products', 'manage_stocks.product_id', '=', 'products.id')
            ->where('manage_stocks.warehouse_id', $warehouseId)
            ->select('products.code', 'manage_stocks.quantity')
            ->get()
            ->keyBy('code'); // Map codes to quantities for quick lookup

        // Step 2: Retrieve product_meta data from pgsql based on country condition
        $productMetaItems = DB::connection('pgsql')->table('product_meta')
            ->select('id', 'variants')
            ->where('country_id', $countryCondition, 1)
            ->orderBy('id')

            ->get();

        // Step 3: Update quantities in variants JSON
        foreach ($productMetaItems as $productMeta) {
            $updatedVariants = json_decode($productMeta->variants, true);

            if (!is_array($updatedVariants)) {
                continue;
            }

            foreach ($updatedVariants as &$variant) {
                foreach ($variant['variantDetails'] as &$detail) {
                    $sku = $detail['sku'];

                    if (isset($manageStocks[$sku])) {
                        $detail['quantity'] = $manageStocks[$sku]->quantity;
                    }
                }
            }

            // Save the updated variants JSON back to pgsql
            DB::connection('pgsql')->table('product_meta')
                ->where('id', $productMeta->id)
                ->update(['variants' => json_encode($updatedVariants)]);
        }

        return "Stock quantities updated successfully.";
    }

  public function comboStockManagedBySku($warehouseId)
  {
      // Determine the country condition based on warehouse ID
      $countryCondition = ($warehouseId == 3) ? '=' : '!=';

      // Fetch minimum quantities for each combo product by warehouse
      $results = ComboProduct::select(
          'combo_products.warehouse_id',
          'combo_products.code AS combo_code',
          DB::raw('MIN(manage_stocks.quantity) AS min_quantity')
      )
          ->join('manage_stocks', function($join) {
              $join->on('combo_products.product_id', '=', 'manage_stocks.product_id')
                  ->on('combo_products.warehouse_id', '=', 'manage_stocks.warehouse_id');
          })
          ->where('combo_products.warehouse_id', $warehouseId) // Apply warehouse ID condition
          ->groupBy('combo_products.warehouse_id', 'combo_products.code')
          ->orderBy('combo_products.warehouse_id')
          ->orderBy('combo_products.code')
          ->get();

      // Structure results into a SKU-to-quantity map
      $comboData = [];
      foreach ($results as $row) {
          $comboCode = $row->combo_code;
          $quantity = (float) $row->min_quantity;
          $comboData[$comboCode] = $quantity;
      }

      // Retrieve product meta items from PostgreSQL based on country condition
      $productMetaItems = DB::connection('pgsql')->table('product_meta')
          ->select('id', 'variants')
          ->where('country_id', $countryCondition, 1)
          ->orderBy('id')
          ->get();

      // Update quantities in the `variants` JSON
      foreach ($productMetaItems as $productMeta) {
          $updatedVariants = json_decode($productMeta->variants, true);

          if (!is_array($updatedVariants)) {
              continue;
          }

          foreach ($updatedVariants as &$variant) {
              foreach ($variant['variantDetails'] as &$detail) {
                  $sku = $detail['sku'];

                  // Update quantity if SKU exists in comboData
                  if (isset($comboData[$sku])) {
                      $detail['quantity'] = $comboData[$sku];
                  }
              }
          }

          // Save the updated `variants` JSON back to PostgreSQL
          DB::connection('pgsql')->table('product_meta')
              ->where('id', $productMeta->id)
              ->update(['variants' => json_encode($updatedVariants)]);
      }

      return "Stock quantities updated successfully.";

  }


  public function purchaseProductStockManagedBySku($warehouseId, $items){
     dd("hello");
  }


}
