<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreatePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Resources\PackageCollection;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Models\PackageVsWarehouse;
use App\Repositories\PackageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PackageAPIController extends AppBaseController
{
    /** @var  PackageRepository */
    private $packageRepository;
    public function __construct(PackageRepository $packageRepository)
    {
        $this->packageRepository = $packageRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can("manage.packages")) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $packages = $this->packageRepository;

        $packages = $packages->paginate($perPage);
        PackageResource::usingWithCollection();

        return new PackageCollection($packages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreatePackageRequest $request)
    {
        if (!Auth::user()->can("package.create")) {
            return $this->sendError('Permission Denied');
        }

        $input = $request->all();

        if ($input['barcode_symbol'] == Package::EAN8 && strlen($input['code']) != 7) {
            return $this->sendError('Please enter 7 digit code');
        }

        if ($input['barcode_symbol'] == Package::UPC && strlen($input['code']) != 11) {
            return $this->sendError(' Please enter 11 digit code');
        }

        $package = $this->packageRepository->storePackage($input);

        return new PackageResource($package);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!Auth::user()->can("package.view")) {
            return $this->sendError('Permission Denied');
        }
        $product = $this->packageRepository->find($id);

        return new PackageResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Package $package)
    {

        $package = $package->load('testingPerpouse.product');
        // dd ($package);

        return new PackageResource($package);
    }
    public function update(UpdatePackageRequest $request, $id)
    {
        if (!Auth::user()->can("package.edit")) {
            return $this->sendError('Permission Denied');
        }

        $input = $request->all();

        $package = $this->packageRepository->updatePackage($input, $id);

        return new PackageResource($package);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can("package.delete")) {
            return $this->sendError('Permission Denied');
        }
        if (File::exists(Storage::path('package_barcode/barcode-PR_' . $id . '.png'))) {
            File::delete(Storage::path('package_barcode/barcode-PR_' . $id . '.png'));
        }

        $this->packageRepository->delete($id);

        return $this->sendSuccess('package deleted successfully');

    }
    public function warehouseAddIntoPackage(Request $request)
    {
    

        $input = $request->all();

        $this->validate($request, [
            'package_id' => 'required',
            'warehouse_id' => 'required',
        ]);

        if (isset($input['id'])) {
            $package = PackageVsWarehouse::find($input['id']);

            if ($package) {
                $package->update($input);
                return $this->sendSuccess('Update successful');
            }

        } else {
          
            $package = PackageVsWarehouse::create($input);
            return $this->sendSuccess('warehouse added into package  successfully');
        }

    }

}
