<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductAbstract;
use App\Models\Variant;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ProductAbstractRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'id',
        'name', 'attributes',
        'product_unit', 'sale_unit',
        'purchase_unit', 'order_tax',
        'tax_type', 'notes',
        'style', 'pan_style',

        'created_at',

    ];

    protected $allowedFields = [
        'name',
        'product_category_id',
        'brand_id',
        'attributes',
        'product_unit',
        'sale_unit',
        'purchase_unit',
        'order_tax',
        'style',
        'pan_style',
        'tax_type',
        'notes',
    ];

    public function getAvailableRelations(): array
    {
        return array_values(ProductAbstract::$availableRelations);
    }

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return ProductAbstract::class;
    }

    public function storeAbstract($input)
    {
        try {

            DB::beginTransaction();

            $productAbstract = $this->create($input);
            $productAbstract->attributes = json_decode($input['attributes']);
            $productAbstract->save();
            // dd($input('images'));
            if (isset($input['images']) && !empty($input['images'])) {
                foreach ($input['images'] as $image) {
                    $productAbstract['image_url'] = $productAbstract->addMedia($image)->toMediaCollection(Product::PATH,
                        config('app.media_disc'));
                }
            }
            // dd($input['products']);
            if (isset($input['products']) && !empty($input['products'])) {

                $products = $input['products'];
                // dd($products);

                foreach ($products as $productData) {
                    // dd ($productData['is_available']);
                    if ($productData['is_available'] === 'false') {
                        continue;

                    }

                    // dd($productData['product_image']);
                    // $variantName = $productData['variant_name'];
                    $variant_get = json_decode($productData['variant']);
                    $variant = Variant::where('id', '!=', -1);
                    foreach ($variant_get as $key => $value) {
                        $variant->whereJsonContains('variant->' . $key, $value->value);
                    }

                    $variant = $variant->first();

                    if ($variant === null) {
                        // Create a new variant if it doesn't exist
                        $variant = new Variant();
                        $variantObject = [];
                        $variantName = '';

                        foreach ($variant_get as $key => $value) {
                            $variantObject[$key] = $value->value;
                            $variantName .= $value->value . ' - ';
                        }
                        $variant->variant = $variantObject;
                        $variant->name = str_replace(' - ', '', $variantName);

                        $variant->save();
                    }
                    //dd ($variant);

                    // Create a new product
                    $product = new Product();
                    $product->name = $input['name'];
                    $product->code = Product::generateProductCodeByValue($productAbstract->id, $variant->id);
                    $product->variant_id = $variant->id;
                    $product->product_abstract_id = $productAbstract->id;
                    $product->product_cost = $input['base_cost'];
                    $product->product_price = $input['base_price'];
                    $product->stock_alert = $productData['stock_alert'];
                    $product->quantity_limit = $productData['quantity_limit'];
                    $product->save();

                    if (isset($productData['product_image']) && !empty($productData['product_image'])) {
                        foreach ($productData['product_image'] as $image) {

                            $product['image_url'] = $product->addMedia($image)->toMediaCollection(Product::PATH,
                                config('app.media_disc'));
                        }
                    }

                    $code = Product::generateProductCodeByValue($productAbstract->id, $variant->id);
                    $reference_code = $code;
                    $input['code'] = $code;

                    $this->generateBarcode($input, $reference_code);
                    // dd('he;;');
                    $product['barcode_image_url'] = Storage::url('product_barcode/barcode-' . $reference_code . '.png');
                }

            }

            DB::commit();
            return $productAbstract;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function updateAbstract($input, $id)
    {

        try {



            DB::beginTransaction();
            $productAbstract = ProductAbstract::findOrFail($id);
            $productAbstract->pan_style = $input['pan_style'];

            $productAbstract->name = $input['name'];
            $productAbstract->product_category_id = $input['product_category_id'];
            $productAbstract->brand_id = $input['brand_id'];
            $productAbstract->product_unit = $input['product_unit'];
            $productAbstract->sale_unit = $input['sale_unit'];
            $productAbstract->purchase_unit = $input['purchase_unit'];
            $productAbstract->order_tax = $input['order_tax'];
            $productAbstract->tax_type = $input['tax_type'];
            $productAbstract->base_price = $input['base_price'];
            $productAbstract->base_cost = $input['base_cost'];

            $productAbstract->attributes = json_decode($input['attributes']);

            $productAbstract->save();

            // dd('Hi');

            if (isset($input['images']) && !empty($input['images'])) {
                foreach ($input['images'] as $image) {
                    $productAbstract['image_url'] = $productAbstract->addMedia($image)->toMediaCollection(Product::PATH,
                        config('app.media_disc'));
                }
            }

            // checking if product is available or not
             //start delete product from here if not in submitted form while it stay in database



            if (isset($input['products']) && !empty($input['products'])) {


                $products = $input['products'];

                $all_products = $productAbstract->products->pluck('id')->toArray();
                $delete_avoid_products = collect($products)->pluck('id')->toArray();
                $productsToDelete = array_diff($all_products, $delete_avoid_products);

                foreach ($productsToDelete as $productToDelete) {
                    $product = Product::find($productToDelete);
                    $product->delete();
                }



                //start edit or update single product from here
                foreach ($products as $productData) {
                    $product = Product::find($productData['id']);

                    //if products exist but not available in frontend form then delete it
                    if ($productData['is_available'] === 'false' && $product != null) {
                        $product->delete();
                        continue;
                    }

                    //variant check
                    $variant_get = json_decode($productData['variant']);
                    $variant = Variant::where('id', '!=', -1);
                    foreach ($variant_get as $key => $value) {
                        $variant->whereJsonContains('variant->' . $key, $value->value);
                    }

                    $variant = $variant->first();

                    // if variant not found then create new variant

                    if ($variant === null) {
                        // Create a new variant if it doesn't exist
                        $variant = new Variant();
                        $variantObject = [];
                        $variantName = '';

                        foreach ($variant_get as $key => $value) {

                            $variantObject[$key] = $value->value;
                            $variantName .= $value->value . ' - ';
                        }
                        $variant->variant = $variantObject;
                        $variant->name = str_replace(' - ', '', $variantName);

                        $variant->save();
                    }


                    // Create a new product if new products come from frontend form
                    $is_new_product = false;
                    if ($product == null) {
                        //cheking if product is available or not which is coming from frontend form switch
                        if ($productData['is_available'] === 'false') {
                            continue;
                        }
                        $is_new_product = true;
                        $product = new Product();
                    }

                    //else update existing product

                    $product->name = $input['name'];
                    $product->code = Product::generateProductCodeByValue($productAbstract->id, $variant->id);
                    $product->variant_id = $variant->id;
                    $product->product_abstract_id = $productAbstract->id;
                    $product->product_cost = $input['base_cost'];
                    $product->product_price = $input['base_price'];
                    $product->stock_alert = $productData['stock_alert'];
                    $product->quantity_limit = $productData['quantity_limit'];
                    $product->save();

                    if (isset($productData['product_image']) && !empty($productData['product_image'])) {
                        foreach ($productData['product_image'] as $image) {

                            $product['image_url'] = $product->addMedia($image)->toMediaCollection(Product::PATH,
                                config('app.media_disc'));
                        }
                    }

                    //if new product then generate barcode for it , barcode never be updated

                    if ($is_new_product) {
                        $code = Product::generateProductCodeByValue($productAbstract->id, $variant->id);
                        $reference_code = $code;
                        $input['code'] = $code;

                        $this->generateBarcode($input, $reference_code);
                        // dd('he;;');
                        $product['barcode_image_url'] = Storage::url('product_barcode/barcode-' . $reference_code . '.png');
                    }

                }

            }
            else
            {

                DB::rollBack();
                throw new UnprocessableEntityHttpException('Product is required');

            }
            DB::commit();
            return $productAbstract;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
    public static function generateBarcode($input, $reference_code): bool
    {

        $barcodeType = null;
        $generator = new BarcodeGeneratorPNG();

        switch ($input['barcode_symbol']) {
            case Product::CODE128:
                $barcodeType = $generator::TYPE_CODE_128;
                break;
            case Product::CODE39:
                $barcodeType = $generator::TYPE_CODE_39;
                break;
            case Product::EAN8:
                $barcodeType = $generator::TYPE_EAN_8;
                break;
            case Product::EAN13:
                $barcodeType = $generator::TYPE_EAN_13;
                break;
            case Product::UPC:
                $barcodeType = $generator::TYPE_UPC_A;
                break;
        }

        Storage::disk(config('app.media_disc'))->put('product_barcode/barcode-' . $reference_code . '.png',
            $generator->getBarcode($input['code'], $barcodeType, 4, 70));

        return true;
    }
}
