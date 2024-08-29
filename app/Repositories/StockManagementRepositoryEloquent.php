<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\StockManagementRepository;
use App\Entities\StockManagement;
use App\Validators\StockManagementValidator;

/**
 * Class StockManagementRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class StockManagementRepositoryEloquent extends BaseRepository implements StockManagementRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return StockManagement::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
