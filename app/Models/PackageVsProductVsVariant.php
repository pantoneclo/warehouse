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

class PackageVsProductVsVariant extends BaseModel implements JsonResourceful
{
    use HasFactory , HasJsonResourcefulData;
    protected $table = 'package_vs_product_vs_variants';

    const JSON_API_TYPE = 'package_vs_product_vs_variants';

    public const PATH = 'package_vs_product_vs_variant';

    protected $fillable = ['product_id', 'variant_id', 'package_id', 'quantity'];

    public static $rules = [
       'product_id' => 'required|integer|exists:products,id',
       'variant_id' => 'required|integer|exists:variants,id',
       'package_id' => 'required|integer|exists:packages,id',
       'quantiy' => 'required|numeric|min:1',
    ];

    public static $availableRelations = [
        'variant_id' => 'variant',
        'product_id' => 'product',
        'package_id' => 'package',
    ];

    protected $casts = [

        'quantity' => 'double',

    ];

    public function prepareAttributes(): array
    {
        $fields = $this->product->prepareAttributes();     
        $fields['package_quantity'] = $this->quantity;

        return $fields;
    }


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
    public function prepareLinks(): array
    {
        return [
            // 'self' => route('package.show', $this->id),
        ];
    }
}
