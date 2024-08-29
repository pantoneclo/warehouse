<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * Class CustomSearchFilter
 */
class CustomSearchFilter implements Filter
{
    public $searchableFields;

    /**
     * @param $searchableFields
     */
    public function __construct($searchableFields)
    {
        // dd(request()->get('filter'));
        $this->searchableFields = $searchableFields;
        $filterSearchFields = request()->get('filter')['search_fields'] ?? [];
        if (!empty($filterSearchFields)) {
            $this->searchableFields = explode(',', $filterSearchFields);
        }
    }

    /**
     * @param  Builder  $query
     * @param $value
     * @param  string  $property
     * @return Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $customQuery = $query;
        $table = $query->getModel()->getTable();
        $customQuery->select($table . '.*');

        if (is_array($value)) {
            foreach ($this->searchableFields as $index => $searchableField) {

                if (strpos($searchableField, ".") !== false) {
                    $joinTable = explode(".", $searchableField)[0];
                    $field = explode(".", $searchableField)[1];
                    $tableJoinField = Str::singular($joinTable) . '_id';
                    $customQuery->selectRaw('`' . $table . '`.*' . ', `' . $joinTable . '`.`' . $field . '`');
                    $customQuery->leftJoin($joinTable, $table . '.' . $tableJoinField, '=', $joinTable . '.id');

                }

                if (strpos($searchableField, ".") !== false) {
                    foreach ($value as $string) {
                        $customQuery->orWhere($searchableField, 'LIKE', '%' . $string . '%');
                    }
                } else {
                    foreach ($value as $string) {
                        $customQuery->orWhere($table . '.' . $searchableField, 'LIKE', '%' . $string . '%');
                    }
                }

            }
        } else {
            foreach ($this->searchableFields as $searchableField) {
                //basic logic
                $searchLogic = [[$table . '.' . $searchableField, 'LIKE', '%' . $value . '%']];

                //logic for derived method
                if (strpos($searchableField, "._derived") !== false && method_exists($query->getModel(), explode(".", $searchableField)[0] . 'ToSearchLogic')) {
                    $customQuery->orWhere(function ($query) use ($searchableField,$value) {
                        $searchLogic = call_user_func_array([$query->getModel(), explode(".", $searchableField)[0] . 'ToSearchLogic'], [$value]);
                        $query->where($searchLogic);
                    });
                }
                //logic for table join two level
                else if (strpos($searchableField, ".") !== false) {
                    $joinTable = explode(".", $searchableField)[0];
                    $field = explode(".", $searchableField)[1];
                    $tableJoinField = Str::singular($joinTable) . '_id';
                    $customQuery->addSelect($joinTable . '.' . $field);
                    $customQuery->leftJoin($joinTable, $table . '.' . $tableJoinField, '=', $joinTable . '.id');
                    $searchLogic = [[$searchableField, 'LIKE', '%' . $value . '%']];
                    $customQuery->orWhere($searchLogic);
                }
                //return normal query
                else{
                    $customQuery->orWhere($searchLogic);
                }
            }
        }

        return $customQuery;
    }
}
