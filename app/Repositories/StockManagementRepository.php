<?php

namespace App\Repositories;

use App\Models\Combo;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\RepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Interface StockManagementRepository.
 *
 * @package namespace App\Repositories;
 */
class StockManagementRepository extends BaseRepository
{
    protected $allowedFields = [
        'id',
        'quantity',
    ];


    public function model(): string
    {
        return ManageStock::class;
    }


    public function marketPlaceSale($input)
    {
        try {
            DB::beginTransaction();

            // Set default values if not provided
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $input['is_sale_created'] = $input['is_sale_created'] ?? false;
            $QuotationId = $input['quotation_id'] ?? false;

            // Extract only necessary fields for sale
            $saleInputArray = Arr::only($input, [
                'customer_id', 'warehouse_id', 'tax_rate', 'tax_amount', 'discount', 'shipping', 'grand_total',
                'received_amount', 'paid_amount', 'payment_type', 'note', 'date', 'status', 'payment_status', 'market_place', 'order_no', 'country'
            ]);

            /** @var Sale $sale */
            $sale = Sale::create($saleInputArray);

            // Update stock for each item sold
            // Pass sale details for history tracking
            $this->updateStockForItems($input['items'], 'decrease', $sale->id, $input['warehouse_id']);

            DB::commit();

            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
    public function updateWarehouseStock($input)
    {
        try {
            DB::beginTransaction();

            // Assume input includes items to add stock for
            // For general warehouse stock update (not attached to a specific sale/purchase entity yet in this context, or maybe it is?)
            // If it's a generic update, we might not have a reference ID, or we can use null.
            // But let's check what $input contains.
            
            $this->updateStockForItems($input['items'], 'increase', null, $input['warehouse_id'] ?? null); // Add warehouse_id if available in input

            // Trigger webhooks to update other marketplaces
            $this->triggerStockUpdateWebhooks($input['items']);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    private function updateStockForItems(array $items, $operation, $saleId = null, $warehouseId = null)
    {
        /** @var \App\Services\StockService $stockService */
        $stockService = app(\App\Services\StockService::class);

        foreach ($items as $item) {
            $product = Product::where('code', $item['code'])->first();
            
            // If warehouseId is not passed, try to find it from item or default? 
            // The original code: $stock = ManageStock::whereProductId($product->id)->first();
            // This implies it picked the FIRST stock record found, which is risky if multiple warehouses exist.
            // But preserving original logic:
            if ($warehouseId) {
                $stock = ManageStock::whereProductId($product->id)->whereWarehouseId($warehouseId)->first();
            } else {
                $stock = ManageStock::whereProductId($product->id)->first();
            }

            if (!$stock) {
                // If it's an increase, maybe we should create it?
                // Original code threw exception if not found.
                if ($operation === 'increase') {
                     // If we have warehouseId, we can create. If not, we can't reliably create.
                     if ($warehouseId) {
                         // StockService handles creation if needed? No, StockService updates.
                         // Actually StockService::updateStock handles creation if qty > 0.
                         // But we need warehouse_id.
                     } else {
                         throw new UnprocessableEntityHttpException('Product not found in stock and no warehouse specified.');
                     }
                } else {
                    throw new UnprocessableEntityHttpException('Product not found in stock.');
                }
            }
            
            $targetWarehouseId = $warehouseId ?? ($stock ? $stock->warehouse_id : null);

            if ($operation === 'decrease') {
                // Check sufficiency
                if ($stock && $stock->quantity < $item['quantity']) {
                    throw new UnprocessableEntityHttpException('Quantity must be less than available quantity.');
                }
                
                $stockService->updateStock(
                    $targetWarehouseId,
                    $product->id,
                    -1 * $item['quantity'],
                    Sale::class, // Assuming mostly Sales here, but could be generic
                    $saleId,
                    'market_place_sale',
                    'Marketplace Sale'
                );
                
            } elseif ($operation === 'increase') {
                $stockService->updateStock(
                    $targetWarehouseId,
                    $product->id,
                    $item['quantity'],
                    null, // No specific reference type for generic update?
                    null,
                    'stock_management_increase',
                    'Stock Management Increase'
                );
            }
        }
    }


   public function updateSingleProductStock($item)
   {
       $product = Product::where('code', $item['code'])->first();

       $wearhouse = Warehouse::where('country_code', $item['wearhouse'])->first();
       $stock = ManageStock::whereProductId($product->id)->whereWarehouseId($wearhouse->id)->first();
       if(!$stock) {
           throw new UnprocessableEntityHttpException('Product not found in stock.');
       }

       return $stock;
   }

//WEB Hooks Functionality
    private function triggerStockUpdateWebhooks(array $items)
    {
        foreach ($items as $item) {

            // Logic to send webhook request to other marketplaces
            // Example: call an external API to update stock on Amazon, Allegro, etc.
            // You can use Guzzle or any other HTTP client to make API requests
        }
    }
}
