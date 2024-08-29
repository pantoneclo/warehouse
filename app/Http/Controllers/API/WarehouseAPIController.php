<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseCollection;
use App\Http\Resources\WarehouseResource;
use App\Models\ManageStock;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Repositories\WarehouseRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\Auth;
/**
 * Class WarehouseAPIController
 */
class WarehouseAPIController extends AppBaseController
{
    /**
     * @var WarehouseRepository
     */
    private $warehouseRepository;

    public function __construct(WarehouseRepository $warehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @param  Request  $request
     * @return WarehouseCollection
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage.warehouses')) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $warehouses = $this->warehouseRepository->paginate($perPage);
        WarehouseResource::usingWithCollection();

        return new WarehouseCollection($warehouses);
    }

    /**
     * @param  CreateWarehouseRequest  $request
     * @return WarehouseResource
     *
     * @throws ValidatorException
     */
    public function store(CreateWarehouseRequest $request)
    {
        if (!Auth::user()->can('warehouse.create')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $warehouse = $this->warehouseRepository->create($input);

        return new WarehouseResource($warehouse);
    }

    public function warehouseDetails($id)
    {
        if (!Auth::user()->can('warehouse.view')) {
            return $this->sendError('Permission Denied');
        }
        $warehouses = ManageStock::where('warehouse_id', $id)->with('product')->get();

        $products = [];

        foreach ($warehouses as $warehouse) {
            $products[] = $warehouse->prepareWarehouseAttributes();
        }

        return $this->sendResponse($products, 'Products Retrived Successfully');
    }

    /**
     * @param $id
     * @return WarehouseResource
     */
    public function show($id)
    {
        if (!Auth::user()->can('warehouse.view')) {
            return $this->sendError('Permission Denied');
        }
        $warehouse = $this->warehouseRepository->find($id);

        return new WarehouseResource($warehouse);
    }

    /**
     * @param  UpdateWarehouseRequest  $request
     * @param $id
     * @return WarehouseResource
     *
     * @throws ValidatorException
     */
    public function update(UpdateWarehouseRequest $request, $id)
    {
        if (!Auth::user()->can('warehouse.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $warehouse = $this->warehouseRepository->update($input, $id);

        return new WarehouseResource($warehouse);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('warehouse.delete')) {
            return $this->sendError('Permission Denied');
        }
        if (getSettingValue('default_warehouse') == $id) {
            return $this->SendError(__('messages.error.default_warehouse_cant_delete'));
        }

        $useWarehouse = $this->warehouseRepository->warehouseCanDelete($id);
        if ($useWarehouse) {
            return $this->sendError(__('messages.error.warehouse_cant_delete'));
        }
        $this->warehouseRepository->delete($id);

        return $this->sendSuccess('Warehouse deleted successfully');
    }

    public function warehouseReport(Request $request)
    {
        $report = [];
        if ($request->get('warehouse_id') && ! empty($request->get('warehouse_id')) && $request->get('warehouse_id') != 'null') {
            $report['sale_count'] = Sale::whereWarehouseId($request->get('warehouse_id'))->count();
            $report['purchase_count'] = Purchase::whereWarehouseId($request->get('warehouse_id'))->count();
            $report['sale_return_count'] = SaleReturn::whereWarehouseId($request->get('warehouse_id'))->count();
            $report['purchase_return_count'] = PurchaseReturn::whereWarehouseId($request->get('warehouse_id'))->count();
        } else {
            $report['sale_count'] = Sale::count();
            $report['purchase_count'] = Purchase::count();
            $report['sale_return_count'] = SaleReturn::count();
            $report['purchase_return_count'] = PurchaseReturn::count();
        }

        return $this->sendResponse($report, '');
    }
}
