<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\SaleCollection;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Hold;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Repositories\SaleRepository;
use App\Services\Parcel\Address;
use App\Services\Parcel\GlsParcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class SaleAPIController
 */
class SaleAPIController extends AppBaseController
{
    /** @var saleRepository */
    private $saleRepository;

    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }

    /**
     * @param  Request  $request
     * @return SaleCollection
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage.sale')) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $search = $request->filter['search'] ?? '';
        $customer = (Customer::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $warehouse = (Warehouse::where('name', 'LIKE', "%$search%")->get()->count() != 0);

        $sales = $this->saleRepository;
        if ($customer || $warehouse) {
            $sales->whereHas('customer', function (Builder $q) use ($search, $customer) {
                if ($customer) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            })->whereHas('warehouse', function (Builder $q) use ($search, $warehouse) {
                if ($warehouse) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            });
        }

        if ($request->get('start_date') && $request->get('end_date')) {
            $sales->whereBetween('date', [$request->get('start_date'), $request->get('end_date')]);
        }

        if ($request->get('warehouse_id')) {
            $sales->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->get('customer_id')) {
            $sales->where('customer_id', $request->get('customer_id'));
        }

        if ($request->get('status') && $request->get('status') != 'null') {
            $sales->Where('status', $request->get('status'));
        }

        if ($request->get('payment_status') && $request->get('payment_status') != 'null') {
            $sales->where('payment_status', $request->get('payment_status'));
        }

        if ($request->get('payment_type') && $request->get('payment_type') != 'null') {
            $sales->where('payment_type', $request->get('payment_type'));
        }

        $sales = $sales->paginate($perPage);

        SaleResource::usingWithCollection();

        return new SaleCollection($sales);
    }

    /**
     * @param  CreateSaleRequest  $request
     * @return SaleResource
     */
    public function store(CreateSaleRequest $request)
    {
       
      
  
        if (!Auth::user()->can('sale.create')) {
            return $this->sendError('Permission Denied');
        }
        if (isset($request->hold_ref_no)) {
            $holdExist = Hold::whereReferenceCode($request->hold_ref_no)->first();
            if (!empty($holdExist)) {
                $holdExist->delete();
            }
        }
        $input = $request->all();
   
        $sale = $this->saleRepository->storeSale($input);

        return new SaleResource($sale);
    }

    /**
     * @param $id
     * @return SaleResource
     */
    public function show($id)
    {
       
        if (!Auth::user()->can('sale.view')) {
            return $this->sendError('Permission Denied');
        }
        $sale = $this->saleRepository->find($id);

        return new SaleResource($sale);
    }

    /**
     * @param  Sale  $sale
     * @return SaleResource
     */
    public function edit(Sale $sale)
    {
        if (!Auth::user()->can('sale.edit')) {
            return $this->sendError('Permission Denied');
        }
        $sale = $sale->load(['saleItems.product.stocks', 'warehouse',

            'saleItems.product' => function ($query) {

                $query->with(['variant', 'productAbstract:id,pan_style']);
            }]);

        return new SaleResource($sale);
    }

    /**
     * @param  UpdateSaleRequest  $request
     * @param $id
     * @return SaleResource
     */
    public function update(UpdateSaleRequest $request, $id)
    {
        if (!Auth::user()->can('sale.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $sale = $this->saleRepository->updateSale($input, $id);

        return new SaleResource($sale);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('sale.delete')) {
            return $this->sendError('Permission Denied');
        }
        try {
            DB::beginTransaction();
            $sale = $this->saleRepository->with('saleItems')->where('id', $id)->first();
            foreach ($sale->saleItems as $saleItem) {
                manageStock($sale->warehouse_id, $saleItem['product_id'], $saleItem['quantity']);
            }
            if (File::exists(Storage::path('sales/barcode-' . $sale->reference_code . '.png'))) {
                File::delete(Storage::path('sales/barcode-' . $sale->reference_code . '.png'));
            }
            $this->saleRepository->delete($id);
            DB::commit();

            return $this->sendSuccess('Sale Deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  Sale  $sale
     * @return JsonResponse
     *
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function pdfDownload(Sale $sale): JsonResponse
    {
        $sale = $sale->load('customer', 'saleItems.product', 'payments');
        $data = [];
        if (Storage::exists('pdf/Sale-' . $sale->reference_code . '.pdf')) {
            Storage::delete('pdf/Sale-' . $sale->reference_code . '.pdf');
        }
        $companyLogo = getLogoUrl();
        $pdf = PDF::loadView('pdf.sale-pdf', compact('sale', 'companyLogo'))->setOptions([
            'tempDir' => public_path(),
            'chroot' => public_path(),
        ]);
        Storage::disk(config('app.media_disc'))->put('pdf/Sale-' . $sale->reference_code . '.pdf', $pdf->output());
        $data['sale_pdf_url'] = Storage::url('pdf/Sale-' . $sale->reference_code . '.pdf');

        return $this->sendResponse($data, 'pdf retrieved Successfully');
    }

    /**
     * @param  Sale  $sale
     * @return JsonResponse
     */
    public function saleInfo(Sale $sale)
    {
        $sale = $sale->load(['saleItems.product', 'warehouse', 'customer','shipment',

            'saleItems.product' => function ($query) {

                $query->with(['variant', 'productAbstract:id,pan_style']);
            }]);
        $keyName = [
            'email', 'company_name', 'phone', 'address',
        ];
        $sale['company_info'] = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();

        return $this->sendResponse($sale, 'Sale information retrieved successfully');
    }

    public function parcelStatusUpdate (Request $request)
    {
        $input = $request->all();
        // dd ($input);
        $sale = $this->saleRepository->updateParcelStatus($input);
    }

    /**
     * @param  Request  $request
     * @return SaleCollection
     */
    public function getSaleProductReport(Request $request): SaleCollection
    {
        $perPage = getPageSize($request);
        $productId = $request->get('product_id');
        $sales = $this->saleRepository->whereHas('saleItems', function ($q) use ($productId) {
            $q->where('product_id', '=', $productId);
        })->with(['saleItems.product', 'customer']);

        $sales = $sales->paginate($perPage);

        SaleResource::usingWithCollection();

        return new SaleCollection($sales);
    }

    public function getStatus()
    {
        $credential = ['gls_username', 'gls_password'];
        $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
        $pwd = $credentials['gls_password'];
        $username = $credentials['gls_username'];
        $password_converted = "[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]";
        $password = json_decode($password_converted, true);

        $credentials = [
            'username' => $username,
            'password' => $password,
        ];
        $parcelNumber = 509496216;

        $glsParcel = new GlsParcel($credentials, null, null, null);
        $response = $glsParcel->fetch($parcelNumber);
        // $reponse = $response->json();

        
        // $responseArray = json_decode($response, true);

        // if (isset($responseArray['ParcelStatusList']) && !empty($responseArray['ParcelStatusList'])) {
        //     // Sort the array based on the "StatusDate" field in descending order
        //     usort($responseArray['ParcelStatusList'], function($a, $b) {
        //         return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
        //     });
        
        //     // The first element in the sorted array is the latest status
        //     $latestStatus = $responseArray['ParcelStatusList'][0];
        
        //     // Now $latestStatus contains the latest status information
        //     dd($latestStatus);
        // }
        return $response->json();
    }

    public function createParcel(Request $request)
    {
        $credential = ['gls_username', 'gls_password'];
        $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
        $pwd = $credentials['gls_password'];
        $username = $credentials['gls_username'];

        $password_converted = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");
        $password = $password_converted;
        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        $pickupAddressData = [
            "city" => "Érd",
            "countryIsoCode" => "SI",
            "houseNumber" => "2",
            "name" => "Pickup Address",
            "street" => "Európa u.",
            "zipCode" => "2351",
            "houseNumberInfo" => "/a",
            "contactName" => "Contact Name",
            "contactPhone" => "+36701234567",
            "contactEmail" => "something@anything.hu",
        ];

        $pickupAddress = new Address($pickupAddressData);

        $deliveryAdressData = [
            "city" => "Érd",
            "countryIsoCode" => "SI",
            "houseNumber" => "2",
            "name" => "delivery Address",
            "street" => "Európa u.",
            "zipCode" => "2351",
            "houseNumberInfo" => "/a",
            "contactName" => "Contact Name",
            "contactPhone" => "+36701234567",
            "contactEmail" => "something@anything.hu",
        ];
        $deliveryAddress = new Address($deliveryAdressData);

        $url = 'https://api.mygls.si/ParcelService.svc/json/PrintLabels';
        $pickupDate = "/Date(" . (strtotime("2023-11-25 23:59:59") * 1000) . ")/";

        $clientNumber = 492380936;
        $codReference = "COD TEST REFETRENCE";
        $clientReference = "TEST PARCEL435";
        $count = 1;
        $codAmount = 1;

        $additionInfo = array(
            "clientNUmber" => $clientNumber,
            "pickupDate" => $pickupDate,
            'url' => $url,
            'Count' => $count,
            'CODAmount' => $codAmount,

        );
        $data = $additionInfo;
        $ref = [
            'CODReference' => "COD TEST REFETRENCE",
            "ClientReference" => "TEST REFETRENCE",
        ];

        $glsParcel = new GlsParcel($credentials, $pickupAddress, $deliveryAddress, $ref);
        $response = $glsParcel->create($data);

        return $response;

    }

}
