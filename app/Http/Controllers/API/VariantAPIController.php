<?php

namespace App\Http\Controllers\API;

use App\Models\Variant;
use App\Http\Requests\CreateVariantRequest;
use App\Http\Requests\UpdateVariantRequest;
use App\Http\Resources\VariantResource;
use App\Http\Resources\VariantCollection;
use App\Repositories\VariantRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class VariantAPIController extends AppBaseController
{
    /** @var  VariantRepository */
    private $variantRepository;

    public function __construct(VariantRepository $variantRepository)
    {
        $this->variantRepository = $variantRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = getPageSize($request);

        $variants = $this->variantRepository ;

        $variants = $variants->paginate($perPage);


        VariantResource::usingWithCollection();

        return new VariantCollection($variants);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\CreateVariantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateVariantRequest $request)
    {

        $input = $request->all();

        // if ($input['barcode_symbol'] == Variant::EAN8 && strlen($input['code']) != 7) {
        //     return $this->sendError('Please enter 7 digit code');
        // }

        // if ($input['barcode_symbol'] == Variant::UPC && strlen($input['code']) != 11) {
        //     return $this->sendError(' Please enter 11 digit code');
        // }

        $variant = $this->variantRepository->storeVariant($input);

        if (isBulkRequest($input)){
            VariantResource::usingWithCollection();
            return new VariantCollection($variant);
        }else{
            return new VariantResource($variant);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $variant = $this->variantRepository->find($id);

        return new VariantResource($variant);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateVariantRequest  $request
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVariantRequest $request,$id)
    {
        $input = $request->all();
        $variant = $this->variantRepository->updateVariant($input, $id);

        return new VariantResource($variant);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Variant  $variant
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // $purchaseItemModels = [
        //     PurchaseItem::class,
        // ];
        // $saleItemModels = [
        //     SaleItem::class,
        // ];
        // $purchaseResult = canDelete($purchaseItemModels, 'product_id', $id);
        // $saleResult = canDelete($saleItemModels, 'product_id', $id);
        // if ($purchaseResult || $saleResult) {
        //     return $this->sendError(__('messages.error.product_cant_deleted'));
        // }

        // if (File::exists(Storage::path('variant_barcode/barcode-PR_'.$id.'.png'))) {
        //     File::delete(Storage::path('variant_barcode/barcode-PR_'.$id.'.png'));
        // }

        $this->variantRepository->delete($id);

        return $this->sendSuccess('variant deleted successfully');
    }


}
