<?php

namespace App\Models;

use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends BaseModel implements HasMedia, JsonResourceful
{
    use HasFactory, InteractsWithMedia, HasJsonResourcefulData;
    protected $table = 'packages';

    const JSON_API_TYPE = 'packages';

    public const PATH = 'package';

    public const PACKAGE_BARCODE_PATH = 'package_barcode';

    public const CODE128 = 1;

    public const CODE39 = 2;

    public const EAN8 = 3;

    public const UPC = 4;

    public const EAN13 = 5;

    protected $appends = ['image_url', 'barcode_image_url', 'code'];

    protected $fillable = [
        'notes',
        'barcode_symbol','measurements','batch_id','batch_ref_track_id'

    ];
    public static $rules = [

        'code' => '',
        'barcode_symbol' => 'required',
        'package_data' => 'required|array',

    ];
    public static $availableRelations = [
        'product_id' => 'product',
        'variant_id' => 'variant',
        'product' => 'package_vs_product_vs_variants',

    ];

    protected $casts = [
        'measurements' => 'array',
    ];

    public function getBarcodeImageUrlAttribute(): string
    {
        /** @var Media $media */
        $media = $this->getMedia(Package::PACKAGE_BARCODE_PATH)->first();
        if (!empty($media)) {
            return $media->getFullUrl();
        }

        return '';
    }
    public function prepareLinks(): array
    {
        return [
            'self' => route('packages.show', $this->id),
        ];
    }
    public function prepareAttributes(): array
    {
        $fields = [

            'package_id' => $this->id,
            'code' => $this->code,
            'measurements' => $this->measurements,
            'batch' => $this->batch,

            'barcode_symbol' => $this->barcode_symbol,
            'barcode_url' => $this->getBarcodeImageUrl(),
            'notes' => $this->notes,
            'package_data' => $this->getProduct(),
            'pacakgeVsWarehouseData' => $this->getWarehouseData(),

        ];

        return $fields;
    }

    public function getCodeAttribute(){
        return strtoupper('PK_'.str_pad(dechex ($this->id), 10, '0', STR_PAD_LEFT));
    }

    public static function codeToSearchLogic($code){
        $id = hexdec(str_replace("PK_", "", $code));
        return [
            ['id' , '=', is_numeric($id)?$id:NULL],
        ];
    }

    public function getImageUrlAttribute()
    {
        /** @var Media $media */
        $medias = $this->getMedia(Package::PATH);
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

    public function testingPerpouse(): HasMany
    {
        return $this->hasMany(PackageVsProductVsVariant::class, 'package_id', 'id');
    }

    public function getProduct()
    {
        $data = PackageVsProductVsVariant::where('package_id', $this->id)
            ->with(['product', 'variant'])
            ->get();

        if ($data) {

            $products = $data->map(function ($item) {
                return [

                    'product_id' => $item->product->id,
                    'product_name' => $item->product->name,
                    'product_code' => $item->product->code,
                    'pan_style' => $item->product->productAbstract->pan_style,
                    'product_code' => $item->product->code,
                    'variant_id' => $item->variant->id,
                    'variant_name' => $item->variant->name,
                    'variant' => $item->variant->variant,
                    'quantity' => $item->quantity,
                    'barcode_url' => $item->product->generateProductCode(),
                    'pan_style' => $item->product->productAbstract->pan_style
                ];
            });

            return $products->toArray();
        }

        return '';
    }

    public function getWarehouseData()
    {
        $data = PackageVsWarehouse::where('package_id', $this->id)
            ->with(['warehouse'])
            ->get();
        if (!empty($data)) {
            return $data->toArray();

        }
        return '';
    }

    public function batch(){
        return $this->hasOne(Batch::class, 'batch_id', 'ref_id')->where('batches.type','product');
    }

    public function generatePackageCodeAndSave()
    {
        $code = $this->code;
        // if($this->code != $code){
        //     $this->code = $code;
        //     $this->save();
        // }

        return $code;
    }

    public function getBarcodeImageUrl(){
        return Storage::url('package_barcode/barcode-' . $this->code . '.png');
    }



}
