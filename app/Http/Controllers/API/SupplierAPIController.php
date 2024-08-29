<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierCollection;
use App\Http\Resources\SupplierResource;
use App\Imports\SupplierImport;
use App\Models\Purchase;
use App\Repositories\SupplierRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\Auth;

/**
 * Class SupplierAPIController
 */
class SupplierAPIController extends AppBaseController
{
    /** @var SupplierRepository */
    private $supplierRepository;

    public function __construct(SupplierRepository $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * @param  Request  $request
     * @return SupplierCollection
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage.suppliers')) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $suppliers = $this->supplierRepository->paginate($perPage);
        SupplierResource::usingWithCollection();

        return new SupplierCollection($suppliers);
    }

    /**
     * @param  CreateSupplierRequest  $request
     * @return SupplierResource
     *
     * @throws ValidatorException
     */
    public function store(CreateSupplierRequest $request)
    {
        if (!Auth::user()->can('supplier.create')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $supplier = $this->supplierRepository->create($input);

        return new SupplierResource($supplier);
    }

    /**
     * @param $id
     * @return SupplierResource
     */
    public function show($id)
    {
        if (!Auth::user()->can('supplier.view')) {
            return $this->sendError('Permission Denied');
        }
        $supplier = $this->supplierRepository->find($id);

        return new SupplierResource($supplier);
    }

    /**
     * @param  UpdateSupplierRequest  $request
     * @param $id
     * @return SupplierResource
     *
     * @throws ValidatorException
     */
    public function update(UpdateSupplierRequest $request, $id)
    {
        if (!Auth::user()->can('supplier.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $supplier = $this->supplierRepository->update($input, $id);

        return new SupplierResource($supplier);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('supplier.delete')) {
            return $this->sendError('Permission Denied');
        }
        $purchaseModel = [
            Purchase::class,
        ];
        $useSupplier = canDelete($purchaseModel, 'supplier_id', $id);
        if ($useSupplier) {
            $this->sendError('Supplier can\'t be deleted.');
        }
        $this->supplierRepository->delete($id);

        return $this->sendSuccess('Supplier deleted successfully');
    }

    public function importSuppliers(Request $request): JsonResponse
    {
        Excel::import(new SupplierImport(), request()->file('file'));

        return $this->sendSuccess('Suppliers imported successfully');
    }
}
