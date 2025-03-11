<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\HasJsonResourcefulData;
use App\Models\Contracts\JsonResourceful;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Log;
class Combo extends BaseModel implements HasMedia, JsonResourceful
{
    use HasFactory, InteractsWithMedia, HasJsonResourcefulData;

    protected $table = 'combos';
    const JSON_API_TYPE = 'combos';
    protected $fillable = [
        'name',
        'sku'
    ];

    public function prepareLinks(): array
    {
        return [
            'self' => route('combo.show', $this->id),
        ];
    }


    public function prepareAttributes(): array
    {
        // Get and format products in the desired JSON structure
        $groupedProducts = $this->comboItems->groupBy('code')->map(function ($comboProducts, $comboCode) {
            return [
                'combo_code' => $comboCode,
                'products' => $comboProducts->map(function ($comboProduct) use ($comboCode){
                    $product = $comboProduct->product; // Assuming the ComboProduct model has a 'product' relationship
                    if (!$product) {
                        Log::error('Missing product for combo', [
                            'combo_code' => $comboCode,  // ✅ Now this variable is available
                            'combo_product_id' => $comboProduct->id,
                        ]);

                        return $comboCode;
                    }


                    return [
                        'type' => 'products',
                        'id' => $product->id,
                        'attributes' => [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'code' => $product->code,
                            // Add any additional attributes here
                        ]
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();


        $fields = [
            // 'name' => $this->productAbstract->name,
            'product_id' => $this->id,
            'name' => $this->name,
            'sku'  => $this->sku,
            'created_at'  => $this->created_at,
            'products' => $this->getAllProducts(),

        ];

        return $fields;
    }


    public function comboItems(): HasMany
    {
        return $this->hasMany(ComboProduct::class, 'combo_id', 'id');
    }


    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            ComboProduct::class,
            'combo_id', // Foreign key on ComboProduct table
            'id', // Foreign key on Product table
            'id', // Local key on Combo table
            'product_id' // Local key on ComboProduct table
        );
    }

    public function getAllProducts()
    {
        // Fetch ComboProducts related to this Combo
        $comboProducts = ComboProduct::where('combo_id', $this->id)->get();

        // Get the product IDs from ComboProducts
        $productIds = $comboProducts->pluck('product_id');

        // Fetch products by these IDs
        $products = Product::whereIn('id', $productIds)->get();

        // Group products by their code
        $groupedProducts = $comboProducts->groupBy('code')->map(function ($comboProducts, $comboCode) use ($products) {
            return [
                'combo_code' => $comboCode,
                'warehouse_id' => $comboProducts->first()->warehouse_id,
                'products' => $comboProducts->map(function ($comboProduct) use ($products) {
                    $product = $products->firstWhere('id', $comboProduct->product_id);
                    return [
                        'type' => 'products',
                        'id' => $product->id,
                        'attributes' => [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'code' => $product->code,
                            "product_cost" =>$product->product_cost,
                            "product_price" => $product->product_price,
                            'variant' => $product->variant,
                            'stock' => $product->stock??0,
                            'warehouse' => $product->warehouse($product->id),
                            'images'=>$product->getImageUrlAttribute(),
                            'barcode_url'=>$product->getBarcodeImageUrlAttribute(),
                            // Add any additional attributes here
                        ]
                    ];
                })->values()->toArray()
            ];
        })->values()->toArray();

        return $groupedProducts;
    }



}
