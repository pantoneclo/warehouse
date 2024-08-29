<?php

namespace App\Repositories;

use App\Models\Brand;
use App\Models\Inventory;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class BrandRepository
 */
class InventoryRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'product_id',
        'description',
        'created_at',
        
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'no_of_items_per_box',
        'no_of_boxes',
        'product_id',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Inventory::class;
    }
}
