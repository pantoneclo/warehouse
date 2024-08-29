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
use App\Models\ProductAbstract;
use App\Models\PackageVsProductVsVariants;
use App\Models\Package;
use App\Http\Resources\PackageResource;


class Product extends BaseModel implements HasMedia, JsonResourceful
{
    use HasFactory, InteractsWithMedia, HasJsonResourcefulData;

    protected $table = 'products';

    const JSON_API_TYPE = 'products';

    public const PATH = 'product';

    public const PRODUCT_BARCODE_PATH = 'product_barcode';

    public const CODE128 = 1;

    public const CODE39 = 2;

    public const EAN8 = 3;

    public const UPC = 4;

    public const EAN13 = 5;

    protected $appends = ['image_url', 'barcode_image_url'];

    protected $fillable = ['name',
     'code', 'product_abstract_id', 'variant_id' ,
      'product_cost', 'product_price', 'stock_alert',
      'quantity_limit',  'notes', 'barcode_symbol'];

    public static $rules = [
        'name' => 'required',
        // 'code' => 'unique:products',
        // 'product_category_id' => 'required|exists:product_categories,id',
        'product_abstract_id' => 'required|exists:product_abstracts,id',
        'variant_id' => 'required|exists:variants,id',

        'product_cost' => 'required|numeric',
        'product_price' => 'required|numeric',

        'stock_alert' => 'nullable',
        'quantity_limit' => 'nullable',
        // 'order_tax' => 'nullable|numeric',
        'tax_type' => 'nullable',
        'notes' => 'nullable',
        // 'barcode_symbol' => 'required',
        'images.*' => 'image|mimes:jpg,jpeg,png',
    ];

    public static $availableRelations = [
        'product_category_id' => 'productCategory',
        'brand_id' => 'brand',
        'product_abstract_id' => 'productAbstract',
    ];

    protected $casts = [
        'product_cost' => 'float',
        'product_price' => 'float',
        'grand_total' => 'float',
        // 'order_tax' => 'float',
    ];

    public function __construct()
    {
        // parent::find($this->product_abstruct_id);
    }

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

    public function getBarcodeImageUrlAttribute(): string
    {
        /** @var Media $media */
        $media = $this->getMedia(Product::PRODUCT_BARCODE_PATH)->first();
        if (!empty($media)) {
            return $media->getFullUrl();
        }

        return '';
    }

    /**
     * @return array
     */
    public function prepareLinks(): array
    {
        return [
            'self' => route('products.show', $this->id),
        ];
    }

    /**
     * @return array
     */
    public function prepareAttributes(): array
    {
        $fields = [
            // 'name' => $this->productAbstract->name,
            'product_id' => $this->id,
            'name' => $this->name,
            'product_abstract_name' => $this->productAbstract->name,
            'product_abstract_id' => $this->productAbstract->id,
            'code' => $this->code,
            'pan_style' => $this->productAbstract->pan_style,
            'product_category_id' => $this->productAbstract->product_category_id,
            'brand_id' => $this->productAbstract->brand_id,
            'variant_id' => $this->variant->id,
            'variant' => $this->variant->variant,
            'product_cost' => $this->product_cost,
            'product_price' => $this->product_price,
            'product_unit' => $this->productAbstract->product_unit,
            'sale_unit' => $this->productAbstract->sale_unit,
            'purchase_unit' => $this->productAbstract->purchase_unit,
            'stock_alert' => $this->stock_alert,
            'quantity_limit' => $this->quantity_limit,
            'order_tax' => $this->productAbstract->order_tax,
            'tax_type' => $this->productAbstract->tax_type,
            'notes' => $this->notes,
            'images' => $this->image_url,
            'product_category_name' => $this->productAbstract->productCategory->name,
            'brand_name' => $this->productAbstract->brand->name,
            'barcode_image_url' => $this->barcode_image_url,
            'barcode_symbol' => $this->barcode_symbol,
            'created_at' => $this->created_at,
            'product_unit_name' => $this->productAbstract->getProductUnitName(),
            'purchase_unit_name' => $this->productAbstract->getPurchaseUnitName(),
            'sale_unit_name' => $this->productAbstract->getSaleUnitName(),
            'stock' => $this->stock,
            'warehouse' => $this->warehouse($this->id) ?? '',
            'barcode_url' => Storage::url('product_barcode/barcode-' . $this->generateProductCode() . '.png'),
            'in_stock' => $this->inStock($this->id),
        
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
    public function getProductUnitName()
    {
        $productUnit = BaseUnit::whereId($this->productAbstract->product_unit)->first();
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
        $purchaseUnit = Unit::whereId($this->productAbstract->purchase_unit)->first();
        if ($purchaseUnit) {
            return $purchaseUnit->toArray();
        }

        return '';
    }

    /**
     * @return array|string
     */
    public function getSaleUnitName()
    {
        $saleUnit = Unit::whereId($this->productAbstract->sale_unit)->first();

        if ($saleUnit) {
            return $saleUnit->toArray();
        }

        return '';
    }

    /**
     * @return BelongsTo
     */
    // public function productCategory(): BelongsTo
    // {
    //     return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    // }

    public function productAbstract(): BelongsTo
    {
        return $this->belongsTo(ProductAbstract::class, 'product_abstract_id', 'id');
    }

    public function PackageCode() 
    {
        $packages_id = PackageVsProductVsVariant::where('product_id', $this->id)->pluck('package_id');
        $packages = Package::whereIn('id', $packages_id)->get();
        if (!$packages_id->isEmpty()) {
            $packageResources =PackageResource::collection($packages);
           return $packageResources;
        }
        else {
            return '';
        }    
    }


    /**
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->productAbstract->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'id');
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
            ->select(DB::raw('sum(quantity) as total_quantity'), 'warehouses.name', 'warehouses.id')
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

    // public function scopeSearch($query, $search = '')
    // {
    //     $product_abstract = (ProductAbstract::Where('pan_style', 'LIKE', "%$search%")->get()->count() != 0);

    //     if ($product_abstract) {
    //         return $query->whereHas('productAbstract', function (Builder $q) use ($search, $product_abstract) {
    //             if ($product_abstract) {
    //                 $q->where('pan_style', 'LIKE', "%$search%");
    //             }
    //         });
    //     }
    //     if (is_numeric($search)) {
    //         $search = (float) $search;
    //         $search = (string) $search;
    //     }

    //     return $query;
    // }

    public function generateProductCode(){
        return strtoupper('PR_'. str_pad(dechex ($this->product_abstract_id), 5, '0', STR_PAD_LEFT) .str_pad(dechex($this->variant_id), 5, '0', STR_PAD_LEFT));
    }

    public static function generateProductCodeByValue( $product_abstract_id, $variant_id){
        return strtoupper('PR_'. str_pad(dechex ($product_abstract_id), 5, '0', STR_PAD_LEFT) .str_pad(dechex($variant_id), 5, '0', STR_PAD_LEFT));
    }

    public function comboProducts()
    {
        return $this->hasMany(ComboProduct::class);
    }
}
