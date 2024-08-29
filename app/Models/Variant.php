<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Contracts\JsonResourceful;
use App\Traits\HasJsonResourcefulData;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Variant extends BaseModel implements HasMedia, JsonResourceful
{
    use HasFactory, InteractsWithMedia, HasJsonResourcefulData;
    protected $table = 'variants';

    const JSON_API_TYPE = 'variants';

    public const PATH = 'variant';

    protected $appends = ['image_url'];

    protected $fillable = [
        'name',
        'variant',
    ];

    public static $rules = [
        'variant'=> 'required|array|unique:variants,variant',
    ];

    protected $casts = [
        'variant' => 'array',
    ];
    public function setAttribute($key, $value)
    {
        if ($key === 'variant') {
            $this->attributes[$key] = json_encode($value, JSON_UNESCAPED_SLASHES);
        } else {
            parent::setAttribute($key, $value);
        }
    }

    public function getImageUrlAttribute()
    {
        /** @var Media $media */
        $medias = $this->getMedia(Variant::PATH);
        $images = [];
        if (! empty($medias)) {
            foreach ($medias as $key => $media) {
                $images['imageUrls'][$key] = $media->getFullUrl();
                $images['id'][$key] = $media->id;
            }

            return $images;
        }

        return '';
    }

    public function prepareLinks(): array
    {
        return [
            'self' => route('variants.show', $this->id),
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'variant_id', 'id');
    }

    public function prepareAttributes ():array{
        $fields = [
            'name' => $this->name,
            'variant' =>$this->variant,
        ];
        return $fields;
    }

}
