<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ManageStockCollection;
use App\Http\Resources\ManageStockResource;
use App\Repositories\ManageStockRepository;
use Illuminate\Http\Request;

/**
 * Class UserAPIController
 */
class ManageStockAPIController extends AppBaseController
{
    /** @var */
    private $manageStockRepository;

    /**
     * @param  ManageStockRepository  $manageStockRepository
     */
    public function __construct(ManageStockRepository $manageStockRepository)
    {
        $this->manageStockRepository = $manageStockRepository;
    }

    /**
     * @param  Request  $request
     * @return ManageStockCollection
     */
    public function stockReport(Request $request): ManageStockCollection
    {
        $request->request->remove('filter');
        $perPage = getPageSize($request);
        $search = $request->get('search');
        $warehouseId = $request->get('warehouse_id');
        if ($search && $search != 'null') {
            // dd('hi');
            $stocks = $this->manageStockRepository->whereHas('product.productAbstract.productCategory',
                function ($query) use ($search) {
                    $query->where('products.code', 'like', '%'.$search.'%')
                        ->orWhere('products.name', 'like', '%'.$search.'%')
                        ->orWhere('products.product_cost', 'like', '%'.$search.'%')
                        ->orWhere('products.product_price', 'like', '%'.$search.'%')
                        ->orWhere('products.product_price', 'like', '%'.$search.'%')
                        ->orWhere('product_categories.name', 'like', '%'.$search.'%');
                })->where('warehouse_id', $warehouseId)->paginate($perPage);
        } else {
            // dd('hi2');
            $stocks = $this->manageStockRepository->where('warehouse_id', $warehouseId)->paginate($perPage);
        }
        ManageStockResource::usingWithCollection();

        return new ManageStockCollection($stocks);
    }
}
