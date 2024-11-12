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
            $this->updateStockForItems($input['items'], 'decrease');

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
            $this->updateStockForItems($input['items'], 'increase');

            // Trigger webhooks to update other marketplaces
            $this->triggerStockUpdateWebhooks($input['items']);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    private function updateStockForItems(array $items, $operation)
    {
        foreach ($items as $item) {
            $product = Product::where('code', $item['code'])->first();
            $stock = ManageStock::whereProductId($product->id)->first();

            if (!$stock) {
                throw new UnprocessableEntityHttpException('Product not found in stock.');
            }

            if ($operation === 'decrease') {
                if ($stock->quantity >= $item['quantity']) {
                    $stock->update(['quantity' => $stock->quantity - $item['quantity']]);
                } else {
                    throw new UnprocessableEntityHttpException('Quantity must be less than available quantity.');
                }
            } elseif ($operation === 'increase') {
                $stock->update(['quantity' => $stock->quantity + $item['quantity']]);
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
