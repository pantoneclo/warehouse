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

class PackageVsWarehouse extends BaseModel
{
    use HasFactory;
    protected $table = 'package_vs_warehouses';
    const JSON_API_TYPE = 'package_vs_warehouses';

    public const PATH = 'package_vs_warehouses';

    protected $fillable = ['warehouse_id', 'package_id', 'position'];

    public static $rules = [
        'warehouse_id' => 'required|integer|exists:warehouses,id',
        'package_id' => 'required|integer|exists:packages,id',
        'position' => 'required|json',
    ];

    public static $availableRelations = [
        'warehouse_id' => 'warehouse',
        'package_id' => 'package',
    ];

    protected $casts = [
        'position' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
    public function prepareLinks(): array
    {
        return [
            'self' => route('package.show', $this->id),
        ];
    }
}
