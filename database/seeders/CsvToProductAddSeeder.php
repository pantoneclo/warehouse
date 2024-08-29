<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\ProductAbstract;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Repositories\ProductRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CsvToProductAddSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    private $csvFile;

    public function __construct()
    {
        // Initialize the $csvFile property within the constructor
        $this->csvFile = public_path('uploads/csv/stock.csv');
    }

    public function run()
    {
        //
        try {
            DB::beginTransaction();
            $csv = Reader::createFromPath($this->csvFile, 'r');
            $csv->setHeaderOffset(0); // Set the header row, adjust as needed

            $currentPurchase = $this->purchase();

            $variant = [];
            $variant['size'] = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '6', '8', '10', '12', '14', '16', '18', '19', '20', '21', '22', '23', '24', '48', '50', '52', '54' , '56', '58', '62', '68', '74', '80', '86', '92'];

            $isNewProduct = false;
            $currentProduct = null;
            $currentCategory = null;
            $currentBrand = null;
            $currentVariant = null;

            $count_products = 0;
            foreach ($csv as $record) {

                if (isset($record['PAN Style']) && !is_null($record['PAN Style']) && $record['PAN Style'] != '') {
                    $isNewProduct = true;
                    $this->command->info(++$count_products . " --  " . $record['PAN Style'] . '. ------------------------ ');
                } else {
                    $isNewProduct = false;
                }

                //get brand name
                if ($isNewProduct) {
                    $brandName = isset($record['Brand']) && !empty($record['Brand']) ? $record['Brand'] : 'True Classic';
                    $currentBrand = Brand::where('name', $brandName)->get()->first();
                    if ($currentBrand === null) {
                        $currentBrand = Brand::create([
                            'name' => $brandName,
                            'description' => null,
                        ]);
                        $this->command->info('New brand created as id: ' . $currentBrand->id . ', name: ' . $currentBrand->name);
                    }
                }

                //get product categoty here
                if ($isNewProduct) {
                    $category = $record['Cat Lvl_1'] . '/' . $record['Cat Lvl_2'] . '/' . $record['Cat Lvl_3'];
                    $currentCategory = ProductCategory::where('name', $category)->get()->first();
                    if ($currentCategory === null) {
                        $currentCategory = ProductCategory::create([
                            'name' => $category,
                        ]);
                        $this->command->info('New category created as id: ' . $currentCategory->id . ', name: ' . $currentCategory->name);
                    }
                }

                if ($isNewProduct) //create new product abstract here
                {
                    $currentProduct = ProductAbstract::where([
                        ['pan_style', $record['PAN Style']],
                        ['name', $record['Item']],
                    ])->get()->first();
                    if ($currentProduct === null) {
                        $currentProduct = ProductAbstract::create([
                            'name' => isset($record['Item']) && !empty($record['Item']) ? $record['Item'] : 'Add a name',
                            'product_category_id' => $currentCategory->id,
                            'brand_id' => $currentBrand->id,
                            'attributes' => ['size', 'color'],
                            'product_unit' => '1',
                            'sale_unit' => '1',
                            'purchase_unit' => '1',
                            'notes' => isset($record['Description']) && !empty($record['Description']) ? $record['Description'] : '/*this product is added from excel/*',

                            'pan_style' => $record['PAN Style'],
                        ]);
                        $this->command->info('New product model created as id: ' . $currentProduct->id . ', name: ' . $currentProduct->name);
                    }
                }

                //create product variant here if not exists
                $currentColor = strtoupper($record['Color']);
                foreach ($variant['size'] as $size) {
                    if (!array_key_exists($size, $record) || strpos($record[$size], '-') !== false || empty($record[$size])) {
                        continue;
                    }

                    $variant_to_name = strtoupper(trim(preg_replace('/\s+/', ' ', $currentColor . ' - ' . $size)));
                    $currentVariant = Variant::where('name', $variant_to_name)->get()->first();
                    if ($currentVariant === null) {
                        $currentVariant = Variant::create([
                            'name' => $variant_to_name,
                            'variant' => ['size' => $size, 'color' => $currentColor],
                        ]);
                        $this->command->info('New product variant created as id: ' . $currentVariant->id . ', name: ' . $currentVariant->name);
                    }

                    //create product here
                    $prd_seed = $this->productSeed($record, $currentProduct, $currentVariant);
                    //purchase this product
                    $this->purchaseProduct($currentPurchase, $prd_seed, $record[$size]);
                    //$this->command->info($record[$size]);
                }

            }
            DB::commit();
            $this->command->info('Data has been seeded successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            $this->command->error($e);
        }
    }

    private function productSeed($record, $product_abstract, $variant)
    {
        $barcode = Product::generateProductCodeByValue($product_abstract->id, $variant->id);

        $currentProduct = Product::where([
            ['product_abstract_id', $product_abstract->id],
            ['variant_id', $variant->id],
        ])->get()->first();

        $cup = Product::updateOrCreate(
            ['product_abstract_id' => $product_abstract->id, 'variant_id' => $variant->id],
            [
                'product_abstract_id' => $product_abstract->id,
                'variant_id' => $variant->id,
                'name' => $product_abstract->name,
                'code' => $barcode,
                'barcode_symbol' => 1,
                'product_cost' => $record['Cost (€)'],
                'product_price' => $record['Price (€)'],
                'notes' => 'add by import from xl',
            ]
        );

        if ($currentProduct === null) {
            $this->command->info('New product created as id: ' . $cup->id . ', name: ' . $cup->name);
            ProductRepository::generateBarcode(['code' => $barcode, 'barcode_symbol' => 1], $barcode);
            return $cup;
        }

        return $currentProduct;
    }

    private function purchase()
    {

        $purchaseStock = [
            "warehouse_id" => Warehouse::where('name', 'In Hand')->get()->first()->id,
            "supplier_id" => Supplier::where('name', 'Own Production')->get()->first()->id,
            "status" => 1,
            "date" => date('Y/m/d'),
        ];

        $curPurchase = Purchase::where([
            ['date', date('Y/m/d')],
            ['warehouse_id', $purchaseStock['warehouse_id']],
            ['supplier_id', $purchaseStock['supplier_id']],
        ])->get()->first();

        if ($curPurchase !== null) {

            $this->command->info('Using existing purchase. id: ' . $curPurchase->id . ', warehouse: ' . $curPurchase->warehouse->name);
            //$this->command->warn('Deleting all purchase items under purchase_id: : ' . $curPurchase->id . ', warehouse: ' . $curPurchase->warehouse->name);
            //$curPurchase->purchaseItems()->delete();
            $curPurchase->grand_total = 0;
            $curPurchase->save();
            return $curPurchase;
        }

        $purchaseStock['tax_rate'] = 0;
        $purchaseStock['tax_amount'] = 0;
        $purchaseStock['discount'] = 0;
        $purchaseStock['shipping'] = 0;
        $purchaseStock['payment_type'] = 0;

        $purchaseInputArray = Arr::only($purchaseStock, [
            'supplier_id', 'warehouse_id', 'date', 'status', 'discount', 'tax_rate', 'tax_amount', 'shipping', 'payment_type',
        ]);

        /** @var Purchase $purchase */
        $purchase = Purchase::create($purchaseInputArray);

        $this->command->info('New purchase created as id: ' . $purchase->id . ', warehouse: ' . $purchase->warehouse->name);

        return $purchase;
    }

    private function purchaseProduct($purchase, $product, $quantity)
    {
        $this->command->warn($purchase->id . ' ' . $product->id . ' ' . $quantity);
        $curPrdItem = PurchaseItem::where([
            ['purchase_id', $purchase->id],
            ['product_id', $product->id],
            ['quantity',$quantity],
        ])->first();

        if ($curPrdItem == null) {
            $this->command->error('Not found purchase');
        } else {
            $this->command->warn('found purchase');
            $iio = PurchaseItem::where([
                ['purchase_id', $purchase->id],
                ['product_id', $product->id],
            ])->get()->sum('quantity');

            $mst = ManageStock::where([
                ['product_id', $product->id],
                ['warehouse_id', $purchase->warehouse_id],
            ])->get()->first();
            $mst->quantity -= $iio;
            $mst->save();

            PurchaseItem::where([
                ['purchase_id', $purchase->id],
                ['product_id', $product->id],
            ])->delete();

            // dd($mst);
        }

        $perItemTaxAmount = 0;

        $purchase_net_unit_cost = $product->product_cost;

        if ($product->order_tax <= 100 && $product->order_tax >= 0) {
            if ($product->tax_type == Purchase::EXCLUSIVE) {
                $purchase->tax_amount = (($purchase_net_unit_cost * $product->order_tax) / 100) * $quantity;
                $perItemTaxAmount = $purchase->tax_amount / $quantity;
            } elseif ($product->tax_type == Purchase::INCLUSIVE) {
                $purchase->tax_amount = ($purchase_net_unit_cost * $product->order_tax) / (100 + $product->order_tax) * $quantity;
                $perItemTaxAmount = $purchase->tax_amount / $quantity;
                $purchase_net_unit_cost -= $perItemTaxAmount;
            }
        } else {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100 ');
        }
        $purchase_sub_total = ($purchase_net_unit_cost + $perItemTaxAmount) * $quantity;
        $data = ProductAbstract::where('id', $product->product_abstract_id)->first();
        $purchaseItemArr = [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'product_cost' => $product->product_cost,
            'net_unit_cost' => $purchase_net_unit_cost,
            'tax_type' => $data->tax_type ?? 1,
            'tax_value' => $data->order_tax,
            'tax_amount' => $purchase->tax_amount,
            'discount_type' => Purchase::FIXED,
            'discount_value' => 0,
            'discount_amount' => 0,
            'purchase_unit' => $data->purchase_unit,
            'quantity' => $quantity,
            'sub_total' => $purchase_sub_total,
        ];

        $purchaseItem = new PurchaseItem($purchaseItemArr);
        $purchase->purchaseItems()->save($purchaseItem);

        $purchase->update([
            'reference_code' => getSettingValue('purchase_code') . '_111' . $purchase->id,
            'grand_total' => $purchase->grand_total + $purchase_sub_total,
        ]);

        // manage stock

        manageStock($purchase->warehouse_id, $product->id, $quantity);

    }
}
