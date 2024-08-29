<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ComboRepository;
use App\Entities\Combo;
use App\Validators\ComboValidator;

/**
 * Class ComboRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ComboRepositoryEloquent extends BaseRepository implements ComboRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Combo::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
