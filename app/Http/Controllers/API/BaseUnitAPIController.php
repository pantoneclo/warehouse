<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateBaseUnitRequest;
use App\Http\Requests\UpdateBaseUnitRequest;
use App\Http\Resources\BaseUnitCollection;
use App\Http\Resources\BaseUnitResource;
use App\Models\BaseUnit;
use App\Repositories\BaseUnitRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\Auth;
/**
 * Class BaseUnitAPIController
 */
class BaseUnitAPIController extends AppBaseController
{
    /**
     * @var BaseUnitRepository
     */
    private $baseUnitRepository;

    public function __construct(BaseUnitRepository $baseUnitRepository)
    {
        $this->baseUnitRepository = $baseUnitRepository;
    }

    /**
     * @param  Request  $request
     * @return BaseUnitCollection
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage.units')) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $baseUnits = $this->baseUnitRepository;

        $baseUnits = $baseUnits->paginate($perPage);

        BaseUnitResource::usingWithCollection();

        return new BaseUnitCollection($baseUnits);
    }

    /**
     * @param  CreateBaseUnitRequest  $request
     * @return BaseUnitResource
     *
     * @throws ValidatorException
     */
    public function store(CreateBaseUnitRequest $request)
    {
        if (!Auth::user()->can('unit.create')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $baseUnit = $this->baseUnitRepository->create($input);
        BaseUnitResource::usingWithCollection();

        return new BaseUnitResource($baseUnit);
    }

    /**
     * @param $id
     * @return BaseUnitResource
     */
    public function show($id)
    {
        if (!Auth::user()->can('unit.view')) {
            return $this->sendError('Permission Denied');
        }
        $baseUnit = $this->baseUnitRepository->find($id);

        return new BaseUnitResource($baseUnit);
    }

    /**
     * @param $id
     * @return BaseUnitResource
     */
    public function edit($id)
    {
        if (!Auth::user()->can('unit.edit')) {
            return $this->sendError('Permission Denied');
        }
        $baseUnit = $this->baseUnitRepository->find($id);

        return new BaseUnitResource($baseUnit);
    }

    /**
     * @param  UpdateBaseUnitRequest  $request
     * @param $id
     * @return BaseUnitResource
     *
     * @throws ValidatorException
     */
    public function update(UpdateBaseUnitRequest $request, $id)
    {
        if (!Auth::user()->can('unit.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $baseUnit = $this->baseUnitRepository->update($input, $id);

        return new BaseUnitResource($baseUnit);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        if (!Auth::user()->can('unit.delete')) {
            return $this->sendError('Permission Denied');
        }
        $defaultBaseUnit = BaseUnit::whereId($id)->where('is_default',true)->exists();
        
        if ($defaultBaseUnit){
            return $this->sendError('Default Base unit can\'t be deleted.');
        }
        
        $baseUnitUse = $this->baseUnitRepository->baseUnitCantDelete($id);
        if ($baseUnitUse) {
            return $this->sendError('Base unit can\'t be deleted.');
        }
        $this->baseUnitRepository->delete($id);

        return $this->sendSuccess('Base unit deleted successfully');
    }
}
