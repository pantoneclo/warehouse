<?php

namespace App\Repositories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesPayment;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\StockHistory;
use App\Services\Expedico\Expedico;
use App\Services\Parcel\GlsParcel;
use App\Jobs\SyncPostgresStock;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use App\Helpers\StockHelper;

use Illuminate\Support\Str;

/**
 * Class SaleRepository
 */
class SaleRepository extends BaseRepository
{
    /**
     * @var array
     */


    protected $fieldSearchable = [
        'date',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'tax_amount',
        'grand_total',
        'received_amount',
        'paid_amount',
        'payment_type',
        'note',
        'created_at',
        'reference_code',
        'order_no'
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'date',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'grand_total',
        'received_amount',
        'note',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Sale::class;
    }

    /**
     * @param $input
     * @return Sale
     */
    public function storeSale($input): Sale
    {
        // Allow enough time for large sale creation
        set_time_limit(0);

        try {
            DB::beginTransaction();

            $input['date'] = $input['date'] ?? date('Y/m/d');
            $input['is_sale_created'] = $input['is_sale_created'] ?? false;
            $QuotationId = $input['quotation_id'] ?? false;

            // Step 1: Create the customer
            $customerData = [
                'name'    => $input['name'],
                'email'   => $input['email'],
                'phone'   => $input['phone'],
                'address' => $input['address'],
                'city'    => $input['city'],
                'country' => $input['country'],
            ];

            $customer   = Customer::create($customerData);
            $customerId = $customer->id;

            // Generate order_no if not provided
            if (empty($input['order_no'])) {
                do {
                    $generatedOrderNo = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                } while (Sale::where('order_no', $generatedOrderNo)->exists());

                $input['order_no'] = $generatedOrderNo;
            }

            // Step 2: Prepare sale input array
            $saleInputArray = Arr::only($input, [
                'warehouse_id',
                'tax_rate',
                'tax_amount',
                'discount',
                'shipping',
                'grand_total',
                'received_amount',
                'paid_amount',
                'payment_type',
                'note',
                'date',
                'status',
                'payment_status',
                'market_place',
                'order_no',
                'country',
                'currency',
                'cod',
            ]);

            $saleInputArray['customer_id']      = $customerId;
            $saleInputArray['order_process_fee'] = 0.85;
            $saleInputArray['conversion_rate']   = Currency::where('code', $input['currency'])->value('conversion_rate') ?? 1;
            $saleInputArray['selling_value_eur'] = $input['grand_total'] * $saleInputArray['conversion_rate'];

            // Step 3: Create the sale
            /** @var Sale $sale */
            $sale = Sale::create($saleInputArray);

            // Marketplace commission
            if ($input['market_place'] == 'MIMOVRSTE' && $input['payment_type'] == '5') {
                $sale->marketplace_commission = ($sale->grand_total - 5) * 0.18;
                $sale->save();
            } elseif ($input['market_place'] == 'MIMOVRSTE' && $input['payment_type'] != '5') {
                $sale->marketplace_commission = ($sale->grand_total - 3) * 0.18;
                $sale->save();
            } elseif ($input['market_place'] == 'PIGU') {
                $sale->marketplace_commission = ($sale->grand_total - $sale->shipping) * 0.1;
                $sale->save();
            }

            // ── Invoice upload ────────────────────────────────────────────
            if (!empty($input['file'])) {
                try {
                    $base64_str = $input['file'];
                    if (!preg_match("/^data:(.*?);base64,/", $base64_str, $matches)) {
                        throw new \Exception('Invalid base64 file format');
                    }
                    $mimeType  = $matches[1];
                    $extension = $this->mimeToExtension($mimeType);
                    $allowedTypes = [
                        'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    ];
                    if (!in_array($mimeType, $allowedTypes)) {
                        throw new \Exception('Unsupported file type. Only documents and images are allowed.');
                    }
                    $base64_data = substr($base64_str, strpos($base64_str, ',') + 1);
                    $file_data   = base64_decode($base64_data);
                    if ($file_data === false) {
                        throw new \Exception('Base64 decoding failed');
                    }
                    if (strlen($file_data) > 5 * 1024 * 1024) {
                        throw new \Exception('File size exceeds maximum limit of 5MB');
                    }
                    if (strpos($mimeType, 'image/') === 0 && !@getimagesizefromstring($file_data)) {
                        throw new \Exception('Invalid image data');
                    }
                    $uploadPath = public_path('uploads/sales/invoices');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    $fileName = 'invoice_' . $input['country'] . '_' . $input['order_no'] . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                    $filePath = $uploadPath . '/' . $fileName;
                    if (file_put_contents($filePath, $file_data) === false) {
                        throw new \Exception('Failed to save file');
                    }
                    chmod($filePath, 0644);
                    unset($input['file']);
                    $sale->file = $fileName;
                    $sale->save();
                } catch (\Exception $e) {
                    if (isset($filePath) && file_exists($filePath)) {
                        unlink($filePath);
                    }
                    \Log::error('Invoice upload error: ' . $e->getMessage());
                    throw new \Exception('Invoice upload failed: ' . $e->getMessage());
                }
            }

            // ── Courier document upload ───────────────────────────────────
            if (!empty($input['courier_document'])) {
                try {
                    $base64_str = $input['courier_document'];
                    if (!preg_match("/^data:(.*?);base64,/", $base64_str, $matches)) {
                        throw new \Exception('Invalid base64 file format');
                    }
                    $mimeType  = $matches[1];
                    $extension = $this->mimeToExtension($mimeType);
                    $allowedTypes = [
                        'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    ];
                    if (!in_array($mimeType, $allowedTypes)) {
                        throw new \Exception('Unsupported file type for courier document.');
                    }
                    $base64_data = substr($base64_str, strpos($base64_str, ',') + 1);
                    $file_data   = base64_decode($base64_data);
                    if ($file_data === false) {
                        throw new \Exception('Base64 decoding failed');
                    }
                    if (strlen($file_data) > 5 * 1024 * 1024) {
                        throw new \Exception('Courier document size exceeds maximum limit of 5MB');
                    }
                    if (strpos($mimeType, 'image/') === 0 && !@getimagesizefromstring($file_data)) {
                        throw new \Exception('Invalid image data');
                    }
                    $uploadPath = public_path('uploads/sales/couriers');
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    $fileName = 'courier_' . $input['country'] . '_' . $input['order_no'] . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                    $filePath = $uploadPath . '/' . $fileName;
                    if (file_put_contents($filePath, $file_data) === false) {
                        throw new \Exception('Failed to save courier document');
                    }
                    chmod($filePath, 0644);
                    $sale->courier_document = $fileName;
                    if (!empty($input['courier_document_name'])) {
                        $sale->courier_document_name = $input['courier_document_name'];
                    }
                    $sale->save();
                } catch (\Exception $e) {
                    if (isset($filePath) && file_exists($filePath)) {
                        unlink($filePath);
                    }
                    \Log::error('Courier document upload error: ' . $e->getMessage());
                    throw new \Exception('Courier document upload failed: ' . $e->getMessage());
                }
            }

            // Mark quotation as converted
            if ($input['is_sale_created'] && $QuotationId) {
                Quotation::where('id', $QuotationId)->update(['is_sale_created' => true]);
            }

            // ── Bulk-save sale items (single INSERT) ──────────────────────
            $sale = $this->storeSaleItems($sale, $input);

            // ── Parcel ────────────────────────────────────────────────────
            if ((isset($input['parcel_number']) && !empty($input['parcel_number']))
                || (isset($input['parcel_company_id']) && !empty($input['parcel_company_id']))) {
                $this->ParcelStatusCreate($input, $sale);
            }

            // Barcode
            $reference_code = getSettingValue('sale_code') . '_111' . $sale->id;
            $this->generateBarcode($reference_code);
            $sale['barcode_image_url'] = Storage::url('sales/barcode-' . $reference_code . '.png');

            // ── Batch stock update (2 queries total) ──────────────────────
            $saleItemsList  = $input['sale_items'];
            $warehouseId    = $input['warehouse_id'];
            $productIds     = array_column($saleItemsList, 'product_id');

            // 1. Load all ManageStock rows in ONE query, keyed by product_id
            $stockMap = ManageStock::whereWarehouseId($warehouseId)
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            // 2. Validate all quantities upfront
            foreach ($saleItemsList as $saleItem) {
                $stock = $stockMap->get($saleItem['product_id']);
                if (!$stock || $stock->quantity < $saleItem['quantity']) {
                    throw new UnprocessableEntityHttpException(
                        'Quantity must be less than Available quantity for product ID ' . $saleItem['product_id'] . '.'
                    );
                }
            }

            // 3. Apply all deductions and build bulk history rows
            $now          = now();
            $userId       = Auth::id();
            $historyRows  = [];
            $skusToSync   = [];

            foreach ($saleItemsList as $saleItem) {
                $stock       = $stockMap->get($saleItem['product_id']);
                $oldQty      = $stock->quantity;
                $deduct      = (int) $saleItem['quantity'];
                $newQty      = max(0, $oldQty - $deduct);

                // Update stock row directly (no extra query per item)
                ManageStock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $saleItem['product_id'])
                    ->update(['quantity' => $newQty, 'updated_at' => $now]);

                $historyRows[] = [
                    'warehouse_id'   => $warehouseId,
                    'product_id'     => $saleItem['product_id'],
                    'quantity'       => -1 * ($oldQty - $newQty),
                    'old_quantity'   => $oldQty,
                    'new_quantity'   => $newQty,
                    'reference_type' => Sale::class,
                    'reference_id'   => $sale->id,
                    'action'         => 'sale',
                    'user_id'        => $userId,
                    'note'           => 'Sale Created',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                // Collect SKU for Postgres sync
                $product = $stockMap->get($saleItem['product_id']);
                if ($product && $product->relationLoaded('product')) {
                    $skusToSync[] = $product->product->code ?? null;
                }
            }

            // 4. Bulk insert all stock history rows in ONE query
            if (!empty($historyRows)) {
                StockHistory::insert($historyRows);
            }

            DB::commit();

            // ── Dispatch ONE Postgres sync job for all SKUs ───────────────
            // Resolve SKUs from product codes (batch query, outside transaction)
            $productCodes = Product::whereIn('id', $productIds)->pluck('code')->filter()->unique()->values()->toArray();
            if (!empty($productCodes)) {
                SyncPostgresStock::dispatch($warehouseId, $productCodes);
            }

            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }


    private function mimeToExtension($mimeType)
    {
        $mimeMap = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }

    /**
     * @param $saleItem
     * @return mixed
     */
    public function calculationSaleItems($saleItem)
    {
        $validator = Validator::make($saleItem, SaleItem::$rules);
        if ($validator->fails()) {
            throw new UnprocessableEntityHttpException($validator->errors()->first());
        }

        //discount calculation
        // $perItemDiscountAmount = 0;
        // $saleItem['net_unit_price'] = $saleItem['net_unit_price'];
        // if ($saleItem['discount_type'] == Sale::PERCENTAGE) {
        //     if ($saleItem['discount_value'] <= 100 && $saleItem['discount_value'] >= 0) {
        //         $saleItem['discount_amount'] = ($saleItem['discount_value'] * $saleItem['net_unit_price'] / 100) * $saleItem['quantity'];
        //         $perItemDiscountAmount = $saleItem['discount_amount'] / $saleItem['quantity'];
        //         $saleItem['net_unit_price'] -= $perItemDiscountAmount;
        //     } else {
        //         throw new UnprocessableEntityHttpException('Please enter discount value between 0 to 100.');
        //     }
        // } elseif ($saleItem['discount_type'] == Sale::FIXED) {
        //     if ($saleItem['discount_value'] <= $saleItem['net_unit_price'] && $saleItem['discount_value'] >= 0) {
        //         $saleItem['discount_amount'] = $saleItem['discount_value'] * $saleItem['quantity'];
        //         $perItemDiscountAmount = $saleItem['discount_amount'] / $saleItem['quantity'];
        //         $saleItem['net_unit_price'] -= $perItemDiscountAmount;
        //     } else {
        //         throw new UnprocessableEntityHttpException("Please enter  discount's value between product's price.");
        //     }
        // }

        // //tax calculation
        // $perItemTaxAmount = 0;
        // if ($saleItem['tax_value'] <= 100 && $saleItem['tax_value'] >= 0) {
        //     if ($saleItem['tax_type'] == Sale::EXCLUSIVE) {
        return $saleItem;
    }

    /**
     * @param $sale
     * @param $input
     * @return mixed
     */
    public function storeSaleItems($sale, $input)
    {
        $saleItemsList = $input['sale_items'];
        $now           = now();

        // ── Batch validate quantity_limit for all products in ONE query ────
        $productIds = array_column($saleItemsList, 'product_id');
        $products   = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($saleItemsList as $saleItem) {
            $product = $products->get($saleItem['product_id']);
            if ($product && isset($product->quantity_limit) && $saleItem['quantity'] > $product->quantity_limit) {
                throw new UnprocessableEntityHttpException(
                    'Please enter less than ' . $product->quantity_limit . ' quantity of ' . $product->name . ' product.'
                );
            }
        }

        // ── Build rows and bulk-insert all sale items in ONE query ────────
        $insertRows = [];
        foreach ($saleItemsList as $saleItem) {
            $item = $this->calculationSaleItems($saleItem);
            $insertRows[] = array_merge(
                Arr::only($item, [
                    'product_id',
                    'product_price',
                    'net_unit_price',
                    'tax_type',
                    'tax_value',
                    'tax_amount',
                    'discount_type',
                    'discount_value',
                    'discount_amount',
                    'sale_unit',
                    'quantity',
                    'sub_total',
                ]),
                [
                    'sale_id'    => $sale->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (!empty($insertRows)) {
            SaleItem::insert($insertRows);
        }

        // Payment
        if ($input['payment_status'] == Sale::PAID) {
            $input['paid_amount'] = $input['grand_total'];
            SalesPayment::create([
                'sale_id'         => $sale->id,
                'payment_date'    => Carbon::now(),
                'payment_type'    => $input['payment_type'],
                'amount'          => $input['paid_amount'],
                'received_amount' => $input['paid_amount'],
            ]);
        } elseif ($input['payment_status'] == Sale::UNPAID) {
            $input['paid_amount'] = 0;
        }

        $input['reference_code'] = getSettingValue('sale_code') . '_111' . $sale->id;
        $sale->update($input);

        return $sale;
    }

    /**
     * @param $input
     * @param $id
     * @return mixed
     */
    public function updateSale($input, $id)
    {

        try {
            DB::beginTransaction();
            $sale = Sale::findOrFail($id);

            $oldStatus = $sale->status;
            $newStatus = isset($input['status']) ? (int) $input['status'] : $oldStatus;

            if (in_array($oldStatus, [Sale::CANCLED, Sale::FAILED_ORDER]) && $newStatus !== $oldStatus) {
                throw new UnprocessableEntityHttpException("Cannot change status because the sale is already Cancelled or Failed Order.");
            }

            $isTransitioningToCancel = !in_array($oldStatus, [Sale::CANCLED, Sale::FAILED_ORDER]) && in_array($newStatus, [Sale::CANCLED, Sale::FAILED_ORDER]);

            // Update invoice file if a new one is uploaded
            if (!empty($input['file']) && str_starts_with($input['file'], 'data:')) {
                try {
                    $base64_str = $input['file'];
                    if (preg_match("/^data:(.*?);base64,/", $base64_str, $matches)) {
                        $mimeType = $matches[1];
                        $extension = $this->mimeToExtension($mimeType);

                        $base64_data = substr($base64_str, strpos($base64_str, ',') + 1);
                        $file_data = base64_decode($base64_data);

                        if ($file_data !== false) {
                            $uploadPath = public_path('uploads/sales/invoices');
                            if (!file_exists($uploadPath)) {
                                mkdir($uploadPath, 0755, true);
                            }

                            // Delete old file if exists
                            if (!empty($sale->file) && file_exists($uploadPath . '/' . $sale->file)) {
                                @unlink($uploadPath . '/' . $sale->file);
                            }

                            $fileName = 'invoice_' . $input['country'] . '_' . $input['order_no'] . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                            $filePath = $uploadPath . '/' . $fileName;
                            if (file_put_contents($filePath, $file_data) !== false) {
                                chmod($filePath, 0644);
                                $sale->file = $fileName;
                                $sale->save();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Invoice update upload error: ' . $e->getMessage());
                }
            }

            // Update courier document if a new one is uploaded
            if (!empty($input['courier_document']) && str_starts_with($input['courier_document'], 'data:')) {
                try {
                    $base64_str = $input['courier_document'];
                    if (preg_match("/^data:(.*?);base64,/", $base64_str, $matches)) {
                        $mimeType = $matches[1];
                        $extension = $this->mimeToExtension($mimeType);

                        $base64_data = substr($base64_str, strpos($base64_str, ',') + 1);
                        $file_data = base64_decode($base64_data);

                        if ($file_data !== false) {
                            $uploadPath = public_path('uploads/sales/couriers');
                            if (!file_exists($uploadPath)) {
                                mkdir($uploadPath, 0755, true);
                            }

                            // Delete old file if exists
                            if (!empty($sale->courier_document) && file_exists($uploadPath . '/' . $sale->courier_document)) {
                                @unlink($uploadPath . '/' . $sale->courier_document);
                            }

                            $fileName = 'courier_' . $input['country'] . '_' . $input['order_no'] . '_' . time() . '_' . Str::random(8) . '.' . $extension;
                            $filePath = $uploadPath . '/' . $fileName;
                            if (file_put_contents($filePath, $file_data) !== false) {
                                chmod($filePath, 0644);
                                $sale->courier_document = $fileName;
                                if (!empty($input['courier_document_name'])) {
                                    $sale->courier_document_name = $input['courier_document_name'];
                                }
                                $sale->save();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Courier document update upload error: ' . $e->getMessage());
                }
            }

            $saleItemIds = SaleItem::whereSaleId($id)->pluck('id')->toArray();
            $saleItmOldIds = [];
            foreach ($input['sale_items'] as $key => $saleItem) {
                $product = Product::whereId($saleItem['product_id'])->first();

                if (!empty($product) && isset($product->quantity_limit) && $saleItem['quantity'] > $product->quantity_limit) {
                    throw new UnprocessableEntityHttpException('Please enter less than ' . $product->quantity_limit . ' quantity of ' . $product->name . ' product.');
                }

                //get different ids & update
                $saleItmOldIds[$key] = $saleItem['sale_item_id'];
                $saleItemArray = Arr::only($saleItem, [
                    'sale_item_id',
                    'product_id',
                    'product_price',
                    'net_unit_price',
                    'tax_type',
                    'tax_value',
                    'tax_amount',
                    'discount_type',
                    'discount_value',
                    'discount_amount',
                    'sale_unit',
                    'quantity',
                    'sub_total'
                ]);


                $this->updateItem($saleItemArray, $input['warehouse_id']);
                //create new product items
                //create new product items
                if (is_null($saleItem['sale_item_id'])) {
                    $saleItem = $this->calculationSaleItems($saleItem);
                    $saleItemArray = Arr::only($saleItem, [
                        'product_id',
                        'product_price',
                        'net_unit_price',
                        'tax_type',
                        'tax_value',
                        'tax_amount',
                        'discount_type',
                        'discount_value',
                        'discount_amount',
                        'sale_unit',
                        'quantity',
                        'sub_total',
                    ]);
                    $sale->saleItems()->create($saleItemArray);

                    /** @var \App\Services\StockService $stockService */
                    $stockService = app(\App\Services\StockService::class);

                    $product = ManageStock::whereWarehouseId($input['warehouse_id'])->whereProductId($saleItem['product_id'])->first();
                    if ($product) {
                        if ($product->quantity >= $saleItem['quantity']) {
                            $stockService->updateStock(
                                $input['warehouse_id'],
                                $saleItem['product_id'],
                                -1 * $saleItem['quantity'], // Decrease
                                Sale::class,
                                $sale->id,
                                'sale_update_add',
                                'Sale Item Added'
                            );
                        } else {
                            throw new UnprocessableEntityHttpException('Quantity must be less than Available quantity.');
                        }
                    }
                }
            }
            $removeItemIds = array_diff($saleItemIds, $saleItmOldIds);
            //delete remove product
            if (!empty(array_values($removeItemIds))) {
                foreach ($removeItemIds as $removeItemId) {
                    // remove quantity manage storage
                    $oldProduct = SaleItem::whereId($removeItemId)->first();
                    $productQuantity = ManageStock::whereWarehouseId($input['warehouse_id'])->whereProductId($oldProduct->product_id)->first();

                    if ($productQuantity) {
                        if ($oldProduct) {
                            /** @var \App\Services\StockService $stockService */
                            $stockService = app(\App\Services\StockService::class);

                            // Add back the quantity
                            $stockService->updateStock(
                                $input['warehouse_id'],
                                $oldProduct->product_id,
                                $oldProduct->quantity, // Increase back
                                Sale::class,
                                $sale->id,
                                'sale_update_remove',
                                'Sale Item Removed'
                            );
                        }
                    } else {
                        // If no stock record exists, we should create it or handled by StockService if we pass +qty? 
                        // StockService creates if +qty.
                        /** @var \App\Services\StockService $stockService */
                        $stockService = app(\App\Services\StockService::class);
                        $stockService->updateStock(
                            $input['warehouse_id'],
                            $oldProduct->product_id,
                            $oldProduct->quantity,
                            Sale::class,
                            $sale->id,
                            'sale_update_remove',
                            'Sale Item Removed'
                        );
                    }
                }
                SaleItem::whereIn('id', array_values($removeItemIds))->delete();
            }
            $this->generateBarcode($sale->reference_code);
            $sale['barcode_image_url'] = Storage::url('sales/barcode-' . $sale->reference_code . '.png');
            $sale = $this->updateSaleCalculation($input, $id);

            if ((isset($input['parcel_number']) && !empty($input['parcel_number'])) || (isset($input['parcel_company_id']) && !empty($input['parcel_company_id']))) {
                $parcel = Shipment::where('sale_id', $sale->id)->first();

                if ($parcel != null) {
                    $parcel->update([
                        'parcel_company_id' => $input['parcel_company_id'],
                        'parcel_number' => $input['parcel_number']
                    ]);
                    if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {

                        $credential = ['gls_username', 'gls_password'];
                        $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
                        $pwd = $credentials['gls_password'];
                        $username = $credentials['gls_username'];
                        $password = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");

                        $credentials = [
                            'username' => $username,
                            'password' => $password,
                        ];
                        $url = 'https://api.mygls.si/ParcelService.svc/json/GetParcelStatuses';
                        $tracking_number = $parcel->parcel_number;

                        if (!empty($tracking_number)) {
                            $glsParcel = new GlsParcel($credentials, null, null, null);
                            $response = $glsParcel->fetch($tracking_number, $url);

                            if ($response) {

                                $parcel = Shipment::whereId($parcel->id)->first();
                                $reponse = $response->json();

                                $parcel->update([
                                    'weight' => $reponse['Weight'],
                                ]);

                                $responseArray = json_decode($response, true);

                                if (isset($responseArray['ParcelStatusList']) && !empty($responseArray['ParcelStatusList'])) {
                                    // Sort the array based on the "StatusDate" field in descending order
                                    usort($responseArray['ParcelStatusList'], function ($a, $b) {
                                        return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                                    });

                                    $latestStatus = $responseArray['ParcelStatusList'][0];
                                    $parcel->update([
                                        'status_date' => $latestStatus['StatusDate'],
                                        'status_description' => $latestStatus['StatusDescription'],
                                        'depot_city' => $latestStatus['DepotCity'],
                                    ]);

                                }
                            }
                        }

                    } elseif ($input['status'] == 2 && $parcel->parcel_company_id == 2) {
                        $username = "be70333cbce4922e";
                        $password = "be70333cbce4922ebf9644b963a7184a";
                        $credentials = [
                            'username' => $username,
                            'password' => $password,
                        ];
                        $tracking_number = $parcel->parcel_number;
                        if (!empty($tracking_number)) {
                            $expedicoParcel = new Expedico($credentials);
                            $response = $expedicoParcel->fetch($tracking_number);
                            if ($response) {

                                $parcel = Shipment::whereId($parcel->id)->first();
                                if (isset($response['data']) && is_array($response['data'])) {
                                    $parcelEvents = $response['data'];

                                    // Sort the array based on 'dateTime' in descending order
                                    usort($parcelEvents, function ($a, $b) {
                                        return strtotime($b['attributes']['dateTime']) - strtotime($a['attributes']['dateTime']);
                                    });

                                    // Get the latest 'statusDescription'
                                    $latestStatusDescription = isset($parcelEvents[0]['attributes']['statusDescription'])
                                        ? $parcelEvents[0]['attributes']['statusDescription']
                                        : null;
                                    $latestDate = isset($parcelEvents[0]['attributes']['dateTime'])
                                        ? $parcelEvents[0]['attributes']['dateTime']
                                        : null;

                                    $parcel->update([
                                        'status_date' => $latestDate,
                                        'status_description' => $latestStatusDescription,
                                        'depot_city' => 'null',
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    $this->ParcelStatusCreate($input, $sale);
                }

            }

            if ($isTransitioningToCancel) {
                $sale->load('saleItems.product');
                /** @var \App\Services\StockService $stockService */
                $stockService = app(\App\Services\StockService::class);
                foreach ($sale->saleItems as $saleItem) {
                    $stockService->updateStock(
                        $sale->warehouse_id,
                        $saleItem->product_id,
                        $saleItem->quantity,
                        Sale::class,
                        $sale->id,
                        'sale_cancel_restock',
                        'Sale Cancelled/Failed Restock',
                        false
                    );
                }
            }

            DB::commit();

            if ($isTransitioningToCancel) {
                $this->syncSaleStockToExternalAndPostgres($sale);
            }

            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function ParcelStatusCreate($input, $sale)
    {

        $parcel = Shipment::create([
            'sale_id' => $sale->id,
            'parcel_company_id' => $input['parcel_company_id'] ?? null,
            'parcel_number' => $input['parcel_number'] ?? null,
        ]);

        if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {
            $tracking_number = $parcel->parcel_number;
            if (!empty($tracking_number)) {
                $credentials = [
                    'username' => env('MYGLS_USERNAME'),
                    'password' => json_decode("[" . implode(',', unpack('C*', hash('sha512', env('MYGLS_PASSWORD'), true))) . "]"),
                ];

                $glsParcel = new GlsParcel($credentials);
                $response = $glsParcel->fetch($tracking_number);

                if ($response) {
                    $parcel = Shipment::find($parcel->id);
                    $parcel->update(['weight' => $response['Weight']]);

                    if (isset($response['ParcelStatusList']) && !empty($response['ParcelStatusList'])) {
                        usort($response['ParcelStatusList'], function ($a, $b) {
                            return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                        });

                        $latestStatus = $response['ParcelStatusList'][0];
                        $parcel->update([
                            'status_date' => $latestStatus['StatusDate'],
                            'status_description' => $latestStatus['StatusDescription'],
                            'depot_city' => $latestStatus['DepotCity'],
                        ]);
                    }
                }
            }
        }

        if ($input['status'] == 2 && $parcel->parcel_company_id == 2) {
            $tracking_number = $parcel->parcel_number;
            if (!empty($tracking_number)) {
                $username = "be70333cbce4922e";
                $password = "be70333cbce4922ebf9644b963a7184a";
                $credentials = [
                    'username' => $username,
                    'password' => $password,
                ];
                $expedicoParcel = new Expedico($credentials);
                $response = $expedicoParcel->fetch($tracking_number);
                if ($response) {

                    $parcel = Shipment::whereId($parcel->id)->first();
                    if (isset($response['data']) && is_array($response['data'])) {
                        $parcelEvents = $response['data'];

                        // Sort the array based on 'dateTime' in descending order
                        usort($parcelEvents, function ($a, $b) {
                            return strtotime($b['attributes']['dateTime']) - strtotime($a['attributes']['dateTime']);
                        });

                        // Get the latest 'statusDescription'
                        $latestStatusDescription = isset($parcelEvents[0]['attributes']['statusDescription'])
                            ? $parcelEvents[0]['attributes']['statusDescription']
                            : null;
                        $latestDate = isset($parcelEvents[0]['attributes']['dateTime'])
                            ? $parcelEvents[0]['attributes']['dateTime']
                            : null;

                        $parcel->update([
                            'status_date' => $latestDate,
                            'status_description' => $latestStatusDescription,
                            'depot_city' => 'null',
                        ]);
                    }
                }
            }
        }
    }

    public function updateParcelStatus($input)
    {

        if (isset($input['parcel_number']) && !empty($input['parcel_number'])) {
            $parcel = null;
            if (isset($input['shipment_id']) && !empty($input['shipment_id'])) {
                $parcel = Shipment::find($input['shipment_id']);
            }
            if ($parcel == null && isset($input['sale_id']) && !empty($input['sale_id'])) {
                $parcel = Shipment::where('sale_id', $input['sale_id'])->first();
            }

            if ($parcel == null) {
                return null;
            }

            if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {

                $credential = ['gls_username', 'gls_password'];
                $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
                $pwd = $credentials['gls_password'];
                $username = $credentials['gls_username'];
                $password = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");

                $credentials = [
                    'username' => $username,
                    'password' => $password,
                ];
                $url = 'https://api.mygls.si/ParcelService.svc/json/GetParcelStatuses';
                $tracking_number = $parcel->parcel_number;

                $glsParcel = new GlsParcel($credentials, null, null, null);
                $response = $glsParcel->fetch($tracking_number, $url);

                if ($response) {

                    $parcel = Shipment::whereId($parcel->id)->first();
                    $reponse = $response->json();
                    // dd ($reponse);

                    $parcel->update([

                        'weight' => $reponse['Weight'],
                    ]);

                    $responseArray = json_decode($response, true);

                    if (isset($responseArray['ParcelStatusList']) && !empty($responseArray['ParcelStatusList'])) {
                        // Sort the array based on the "StatusDate" field in descending order
                        usort($responseArray['ParcelStatusList'], function ($a, $b) {
                            return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                        });

                        $latestStatus = $responseArray['ParcelStatusList'][0];
                        $parcel->update([

                            'status_date' => $latestStatus['StatusDate'],
                            'status_description' => $latestStatus['StatusDescription'],
                            'depot_city' => $latestStatus['DepotCity'],
                        ]);

                    }
                    // dd($parcel);
                }

            }
            if ($input['status'] == 2 && $parcel->parcel_company_id == 2) {

                $username = "be70333cbce4922e";
                $password = "be70333cbce4922ebf9644b963a7184a";
                $credentials = [
                    'username' => $username,
                    'password' => $password,
                ];
                $tracking_number = $parcel->parcel_number;
                $expedicoParcel = new Expedico($credentials);
                $response = $expedicoParcel->fetch($tracking_number);
                if ($response) {

                    $parcel = Shipment::whereId($parcel->id)->first();
                    if (isset($response['data']) && is_array($response['data'])) {
                        $parcelEvents = $response['data'];

                        // Sort the array based on 'dateTime' in descending order
                        usort($parcelEvents, function ($a, $b) {
                            return strtotime($b['attributes']['dateTime']) - strtotime($a['attributes']['dateTime']);
                        });

                        // Get the latest 'statusDescription'
                        $latestStatusDescription = isset($parcelEvents[0]['attributes']['statusDescription'])
                            ? $parcelEvents[0]['attributes']['statusDescription']
                            : null;
                        $latestDate = isset($parcelEvents[0]['attributes']['dateTime'])
                            ? $parcelEvents[0]['attributes']['dateTime']
                            : null;

                        $parcel->update([

                            'status_date' => $latestDate,
                            'status_description' => $latestStatusDescription,

                        ]);
                    }

                }
            }

        }
    }

    /**
     * @param $saleItem
     * @param $warehouseId
     * @return bool
     */
    public function updateItem($saleItem, $warehouseId): bool
    {
        try {
            $saleItem = $this->calculationSaleItems($saleItem);
            $item = SaleItem::whereId($saleItem['sale_item_id']);
            $product = ManageStock::whereWarehouseId($warehouseId)->whereProductId($saleItem['product_id'])->first();
            $oldItem = SaleItem::whereId($saleItem['sale_item_id'])->first();

            if ($oldItem && $oldItem->quantity != $saleItem['quantity']) {
                $diff = $saleItem['quantity'] - $oldItem->quantity;
                /** @var \App\Services\StockService $stockService */
                $stockService = app(\App\Services\StockService::class);

                // If diff is positive (increased qty), we need to DECREASE stock.
                // If diff is negative (decreased qty), we need to INCREASE stock.
                // So change to stock = -1 * diff.

                $stockChange = -1 * $diff;

                if ($stockChange < 0) { // We are decreasing stock
                    if (($product->quantity + $stockChange) < 0) {
                        throw new UnprocessableEntityHttpException('Quantity must be less than Available quantity.');
                    }
                }

                $stockService->updateStock(
                    $warehouseId,
                    $saleItem['product_id'],
                    $stockChange,
                    Sale::class,
                    $oldItem->sale_id,
                    'sale_update_qty',
                    'Sale Item Quantity Changed'
                );
            }
            unset($saleItem['sale_item_id']);
            $item->update($saleItem);

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $input
     * @param $id
     * @return mixed
     */
    public function updateSaleCalculation($input, $id)
    {
        // dd ($input['market_place']);
        $sale = Sale::findOrFail($id);
        $subTotalAmount = $sale->saleItems()->sum('sub_total');

        if ($input['discount'] > $subTotalAmount || $input['discount'] < 0) {
            throw new UnprocessableEntityHttpException('Discount amount should not be greater than total.');
        }
        if ($input['tax_rate'] > 100 || $input['tax_rate'] < 0) {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100.');
        }



        if ($input['shipping'] > $input['grand_total'] || $input['shipping'] < 0) {
            throw new UnprocessableEntityHttpException('Shipping amount should not be greater than total.');
        }

        $sale->first();

        $saleInputArray = Arr::only($input, [
            'warehouse_id',
            'tax_rate',
            'tax_amount',
            'discount',
            'shipping',
            'grand_total',
            'received_amount',
            'paid_amount',
            'payment_type',
            'note',
            'date',
            'status',
            'payment_status',
            'market_place',
            'order_no',
            'country',
            'currency',
            'cod'
        ]);
        $sale->update($saleInputArray);

        return $sale;
    }

    /**
     * @param $input
     * @return bool
     */
    public function generateBarcode($code): bool
    {
        $generator = new BarcodeGeneratorPNG();
        $barcodeType = $generator::TYPE_CODE_128;

        Storage::disk(config('app.media_disc'))->put(
            'sales/barcode-' . $code . '.png',
            $generator->getBarcode(Sale::CODE128, $barcodeType, 4, 70)
        );

        return true;
    }

    public function syncSaleStockToExternalAndPostgres($sale)
    {
        $warehouse = $sale->warehouse;
        if (!$warehouse) {
            return;
        }

        $sale->load(['saleItems.product']);

        $productIds = $sale->saleItems->pluck('product_id')->unique()->toArray();
        if (empty($productIds)) {
            return;
        }

        // Fetch all products in bulk
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Fetch all stock records in bulk
        $stocks = ManageStock::where('warehouse_id', $warehouse->id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // Combos containing direct products
        $combosContainingProducts = \App\Models\ComboProduct::whereIn('product_id', $productIds)
            ->where('warehouse_id', $warehouse->id)
            ->get();

        $relatedComboIds = $combosContainingProducts->pluck('combo_id')->unique()->toArray();

        // All constituent products of related combos
        $allComboConstituents = collect();
        if (!empty($relatedComboIds)) {
            $allComboConstituents = \App\Models\ComboProduct::whereIn('combo_id', $relatedComboIds)
                ->where('warehouse_id', $warehouse->id)
                ->get();
        }

        $comboProductIds = $allComboConstituents->pluck('product_id')->unique()->toArray();
        $allProductIds = array_unique(array_merge($productIds, $comboProductIds));

        // Fetch all products/stocks needed for combos too
        $preloadedProducts = Product::whereIn('id', $allProductIds)->get()->keyBy('id');
        $preloadedStocks = ManageStock::where('warehouse_id', $warehouse->id)
            ->whereIn('product_id', $allProductIds)
            ->get()
            ->keyBy('product_id');

        $combosContainingProductGrouped = $combosContainingProducts->groupBy('product_id');
        $allComboConstituentsGrouped = $allComboConstituents->groupBy('combo_id');

        $preparedItems = [];
        $comboRelatedItems = [];
        $skusToSync = [];

        foreach ($sale->saleItems as $saleItem) {
            $product = $preloadedProducts->get($saleItem->product_id);
            if (!$product) {
                continue;
            }

            $manageStockProduct = $preloadedStocks->get($product->id);
            if ($manageStockProduct) {
                $preparedItems[] = [
                    'sku' => $product->code,
                    'quantity' => $manageStockProduct->quantity,
                ];

                $combos = $combosContainingProductGrouped->get($product->id) ?? collect();
                foreach ($combos as $comboInstance) {
                    $comboId = $comboInstance->combo_id;
                    $comboRelatedProductIds = $allComboConstituentsGrouped->get($comboId) ?? collect();

                    $smallestQuantity = null;

                    foreach ($comboRelatedProductIds as $comboProductRelation) {
                        $comboProductId = $comboProductRelation->product_id;
                        $comboProductModel = $preloadedProducts->get($comboProductId);
                        $comboStockProduct = $preloadedStocks->get($comboProductId);

                        if ($comboProductModel && $comboStockProduct) {
                            $currentComboProductQuantity = $comboStockProduct->quantity;

                            if ($smallestQuantity === null || $currentComboProductQuantity < $smallestQuantity) {
                                $smallestQuantity = $currentComboProductQuantity;
                            }

                            $comboRelatedItems[] = [
                                'sku' => $comboProductModel->code,
                                'quantity' => $comboStockProduct->quantity,
                            ];
                        }
                    }

                    if ($smallestQuantity !== null) {
                        $preparedItems[] = [
                            'sku' => $comboInstance->code,
                            'quantity' => $smallestQuantity,
                        ];
                    }
                }
            }

            $skusToSync[] = $product->code;
        }

        foreach ($combosContainingProducts as $comboProductRelation) {
            $skusToSync[] = $comboProductRelation->code;
        }

        $skusToSync = array_unique(array_filter($skusToSync));

        if (!empty($skusToSync)) {
            \App\Jobs\SyncWebhookOrderStocks::dispatch(
                $warehouse->id,
                $warehouse->country_code,
                'inventory',
                $preparedItems,
                $comboRelatedItems,
                $skusToSync
            );
        }
    }
}
