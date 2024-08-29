<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\ManageStock;

use App\Http\Resources\ProductResource;


class ProductAbstract extends BaseModel implements HasMedia, JsonResourceful
{
    use HasFactory, InteractsWithMedia, HasJsonResourcefulData;

    protected $table = 'product_abstracts';

    const JSON_API_TYPE = 'product_abstracts';

    public const PATH = 'product_abstract';
    public const PATH2 = 'product';

    // public const PRODUCT_BARCODE_PATH = 'product_barcode';

    // public const CODE128 = 1;

    // public const CODE39 = 2;

    // public const EAN8 = 3;

    // public const UPC = 4;

    // public const EAN13 = 5;

    protected $appends = ['image_url'];

    protected $fillable = ['name', 'product_category_id','base_price','base_cost', 'brand_id', 'attributes',  'product_unit', 'sale_unit', 'purchase_unit', 'order_tax', 'tax_type', 'notes', 'style', 'pan_style'];

    public static $rules = [
        'name' => 'required',
        'pan_style' => 'required',
        'product_category_id' => 'required|exists:product_categories,id',
        'brand_id' => 'required|exists:brands,id',
        'attributes' => 'required',
        'product_unit' => 'required',
        'sale_unit' => 'nullable',
        'purchase_unit' => 'nullable',
        'order_tax' => 'nullable|numeric',
        'tax_type' => 'nullable',
        'notes' => 'nullable',
        'images.*' => 'image|mimes:jpg,jpeg,png',
        
    ];

    public static $availableRelations = [
        'product_category_id' => 'productCategory',
        'brand_id' => 'brand',
        'products' => 'products',
    ];


    protected $casts = [
        'product_cost' => 'float',
        'product_price' => 'float',
        'grand_total' => 'float',
        'order_tax' => 'float',
        'attributes' => 'array'
    ];

   



    /**
     * @return array
     */
    public function prepareLinks(): array
    {
        return [
            'self' => route('product_abstracts.show', $this->id),
        ];
    }

    /**
     * @return array
     */
     /**
     * @return array|string
     */
    public function getImageUrlAttribute()
    {
        /** @var Media $media */
        $medias = $this->getMedia(Product::PATH);
        $images = [];
        if (!empty($medias)) {
            foreach ($medias as $key => $media) {
                $images['imageUrls'][$key] = $media->getFullUrl();
                $images['id'][$key] = $media->id;
            }

            return $images;
        }

        return '';
    }
    public function prepareAttributes(): array
    {
        $fields = [
            'name' => $this->name,
            'pan_style' => $this->pan_style,
            'product_category_id' => $this->product_category_id,
            'brand_id' => $this->brand_id,
            'attributes' => json_decode ($this->attributes['attributes']),
            'product_unit' => $this->product_unit,
            'sale_unit' => $this->sale_unit,
            'purchase_unit' => $this->purchase_unit,
            'order_tax' => $this->order_tax,
            'tax_type' => $this->tax_type,
            'notes' => $this->notes,
            'images' => $this->image_url,
            'product_category_name' => $this->productCategory->name,
            'brand_name' => $this->brand->name,
            'created_at' => $this->created_at,
            'product_unit_name' => $this->getProductUnitName(),
            'purchase_unit_name' => $this->getPurchaseUnitName(),
            'sale_unit_name' => $this->getSaleUnitName(),   
            'products' => $this->getAllProducts(),   
            'base_price' => $this->base_price,
            'base_cost' => $this->base_cost, 
            'total_quantity' => $this->totalQuantity($this->id),
           
        ];

        return $fields;
    }
    

    /**
     * @return string[]
     */
    public function getIdFilterFields(): array
    {
        return [
            'id' => self::class,
            'product_category_id' => ProductCategory::class,
            'brand_id' => Brand::class,
        ];
    }

    /**  
     * @return array|string
     */
    public function totalQuantity($id)
    {
        $products = Product::where('product_abstract_id', $id)->get();
    
        if (!$products->isEmpty()) {
            $totalQuantity = 0;
    
            foreach ($products as $product) {
                $totalQuantity += Managestock::where('product_id', $product->id)->sum('quantity');
            }
    
            return $totalQuantity;
        } else {
            return 0; 
        }
    }
    
    public function getProductUnitName()
    {
        $productUnit = BaseUnit::whereId($this->product_unit)->first();
        if ($productUnit) {
            return $productUnit->toArray();
        }

        return '';
    }

    /**
     * @return array|string
     */
    public function getPurchaseUnitName()
    {
        $purchaseUnit = Unit::whereId($this->purchase_unit)->first();
        if ($purchaseUnit) {
            return $purchaseUnit->toArray();
        }

        return '';
    }
    public function getVariants()
    {
        $variants = Variant::where('product_id', $this->id)->get();

        if (!$variants->isEmpty()) {
            return $variants->toArray();
        } else {
            return '';
        }
    }

    public function getAllProducts()
    {
        $products = Product::where('product_abstract_id', $this->id)->get();

        if (!$products->isEmpty()) {
            return ProductResource::collection($products) ;
        } else {
            return '';
        }
    }
    /**
     * @return array|string
     */
    public function getSaleUnitName()
    {
        $saleUnit = Unit::whereId($this->sale_unit)->first();

        if ($saleUnit) {
            return $saleUnit->toArray();
        }

        return '';
    }

    /**
     * @return BelongsTo
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    public function variant(): HasMany
    {
        return $this->hasMany(Variant::class, 'product_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function stock(): HasOne
    {
        return $this->hasOne(ManageStock::class, 'product_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'purchase_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_abstract_id', 'id');
    }
    
    /**
     * @return array
     */
    public function prepareTopSelling()
    {
        return [
            'name' => $this->name,
            'total_quantity' => $this->total_quantity,
            'grand_total' => $this->grand_total,
            'sale_unit' => isset($this->getSaleUnitName()['short_name']) ? $this->getSaleUnitName()['short_name'] : null,
        ];
    }

    /**
     * @return array
     */
    public function prepareTopSellingReport()
    {
        return [
            'name' => $this->name,
            'total_quantity' => $this->total_quantity,
            'price' => $this->product_price,
            'grand_total' => $this->grand_total,
            'code' => $this->code,
            'sale_unit' => isset($this->getSaleUnitName()['short_name']) ? $this->getSaleUnitName()['short_name'] : null,
        ];
    }

    /**
     * @return array
     */
    public function yearlyTopSelling()
    {
        return [
            'name' => $this->name,
            'total_quantity' => $this->total_quantity,
            'grand_total' => $this->grand_total,
            'sale_unit' => isset($this->getSaleUnitName()['short_name']) ? $this->getSaleUnitName()['short_name'] : null,
        ];
    }

    /**
     * @param $id
     * @return mixed
     */
    public function warehouse($id)
    {
        return Managestock::where('product_id', $id)
            ->Join('warehouses', 'manage_stocks.warehouse_id', 'warehouses.id')
            ->select(DB::raw('sum(quantity) as total_quantity'), 'warehouses.name')
            ->groupBy('warehouse_id')
            ->get();
    }

   

    /**
     * @param $id
     * @return mixed
     */
    public function inStock($id)
    {
        $totalQuantity = Managestock::where('product_id', $id)->sum('quantity');

        return $totalQuantity;
    }

    /**
     * @return HasMany
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ManageStock::class, 'product_id', 'id');
    }

    public function prepareProductReport()
    {
        return [
            'reference_code' => $this->code,
            'name' => $this->name,
            'total_quantity' => $this->total_quantity,
            'grand_total' => $this->grand_total,
            'product_unit' => $this->product_unit,
        ];
    }

}
