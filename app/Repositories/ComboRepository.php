<?php

namespace App\Repositories;


use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use App\Models\Combo;
/**
 * Class ComboRepository.
 *
 * @package namespace App\Repositories;
 */
class ComboRepository extends BaseRepository
{    
protected $fieldSearchable = [
    'id',
    'name',
    'sku',
    'created_at',

];

protected $allowedFields = [
    'id',
    'name',
    'sku',
    'created_at',
];

public function getFieldsSearchable(): array
{
    return $this->fieldSearchable;
}


    public function model(): string
    {
        return Combo::class;
    }
}
