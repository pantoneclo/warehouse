<?php

namespace App\Helpers;

use App\Models\ComboProduct;
use App\Models\Product;
use App\Models\ManageStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockHelper
{
    public static function manageStockForCodeAndWarehouse($code, $warehouseId, &$visitedProductCodes = [], &$visitedComboCodes = [])
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        if (strpos($code, 'COMBO') === 0) {
            if (in_array($code, $visitedComboCodes, true)) {
                Log::info("Combo code $code has already been processed. Skipping to prevent recursion.");
                return;
            }
        } else {
            if (in_array($code, $visitedProductCodes, true)) {
                Log::info("Product code $code has already been processed. Skipping to prevent recursion.");
                return;
            }
        }

        if (strpos($code, 'COMBO') === 0) {
            $result = ComboProduct::select(
                'combo_products.warehouse_id',
                'combo_products.code AS combo_code',
                DB::raw('MIN(manage_stocks.quantity) AS min_quantity')
            )
                ->join('manage_stocks', function ($join) {
                    $join->on('combo_products.product_id', '=', 'manage_stocks.product_id')
                        ->on('combo_products.warehouse_id', '=', 'manage_stocks.warehouse_id');
                })
                ->where('combo_products.code', $code)
                ->where('combo_products.warehouse_id', $warehouseId)
                ->groupBy('combo_products.warehouse_id', 'combo_products.code')
                ->first();

            if (!$result) {
                Log::warning("No combo products found for code: $code in warehouse: $warehouseId");
                return;
            }

            $minQuantity = $result->min_quantity ?? 0;
            Log::info("Combo $code Minimum Quantity: $minQuantity");

            self::updateProductMetaQuantity($code, $minQuantity, $warehouseId);

            $visitedComboCodes[] = $code;

            $relatedProducts = ComboProduct::where('code', $code)
                ->where('warehouse_id', $warehouseId)
                ->pluck('product_id');

            foreach ($relatedProducts as $productId) {
                $productCode = Product::where('id', $productId)->value('code');
                if ($productCode) {
                    Log::info("Processing product code: $productCode within combo: $code");
                    self::manageStockForCodeAndWarehouse($productCode, $warehouseId, $visitedProductCodes, $visitedComboCodes);
                }
            }

        } else {
            $productId = Product::where('code', $code)->value('id');

            if (!$productId) {
                Log::error("Product not found for code: $code");
                return;
            }

            $quantity = ManageStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->value('quantity') ?? 0;

            $comboCodes = ComboProduct::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->pluck('code');

            foreach ($comboCodes as $comboCode) {
                Log::info("Processing nested combo code: $comboCode for product: $code");
                self::manageStockForCodeAndWarehouse($comboCode, $warehouseId, $visitedProductCodes, $visitedComboCodes);
            }

            self::updateProductMetaQuantity($code, $quantity, $warehouseId);

            $visitedProductCodes[] = $code;
        }

        Log::info("Stock quantities updated successfully for code: $code");
    }

    public static function updateProductMetaQuantity($code, $quantity, $warehouseId)
    {
        $countryCondition = ($warehouseId == 3) ? '=' : '!=';

        $productMetaItems = DB::connection('pgsql')->table('product_meta')
            ->select('id', 'variants')
            ->whereRaw("country_id $countryCondition 1")
            ->whereRaw("variants::jsonb @> ?", [json_encode([['variantDetails' => [['sku' => $code]]]])])
            ->orderBy('id')
            ->get();

        foreach ($productMetaItems as $item) {
            $variants = json_decode($item->variants, true);

            if (!is_array($variants)) {
                continue;
            }

            $updated = false;

            foreach ($variants as &$variant) {
                foreach ($variant['variantDetails'] as &$variantDetail) {
                    if ($variantDetail['sku'] === $code) {
                        if ($variantDetail['quantity'] != $quantity) {
                            $variantDetail['quantity'] = $quantity;
                            $updated = true;
                        }
                    }
                }
            }

            if ($updated) {
                DB::connection('pgsql')->table('product_meta')
                    ->where('id', $item->id)
                    ->update(['variants' => json_encode($variants)]);

                Log::info("Updated quantity for SKU: $code to $quantity in product_meta ID: {$item->id}");
            }
        }
    }
}
