<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\QuotationCollection;
use App\Http\Resources\QuotationResource;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Repositories\QuotationRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class QuotationAPIController extends AppBaseController
{
    /** @var quotationRepository */
    private $quotationRepository;

    public function __construct(QuotationRepository $quotationRepository)
    {
        $this->quotationRepository = $quotationRepository;
    }

    public function index(Request $request)
    {
        if (!Auth::user()->can('manage.quotations')) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $search = $request->filter['search'] ?? '';
        $customer = (Customer::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $warehouse = (Warehouse::where('name', 'LIKE', "%$search%")->get()->count() != 0);

        $quotations = $this->quotationRepository;
        if ($customer || $warehouse) {
            $quotations->whereHas('customer', function (Builder $q) use ($search, $customer) {
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
            $quotations->whereBetween('date', [$request->get('start_date'), $request->get('end_date')]);
        }

        if ($request->get('warehouse_id')) {
            $quotations->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->get('customer_id')) {
            $quotations->where('customer_id', $request->get('customer_id'));
        }

        if ($request->get('status') && $request->get('status') != 'null') {
            $quotations->Where('status', $request->get('status'));
        }

        $quotations = $quotations->paginate($perPage);

        QuotationResource::usingWithCollection();

        return new QuotationCollection($quotations);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @param  CreateQuotationRequest  $request
     * @return QuotationResource
     */
    public function store(CreateQuotationRequest $request)
    {
        if (!Auth::user()->can('quotations.create')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $quotation = $this->quotationRepository->storeQuotation($input);

        return new QuotationResource($quotation);
    }

    /**
     * @param $id
     * @return QuotationResource
     */
    public function show($id)
    {
        if (!Auth::user()->can('manage.quotation')) {
            return $this->sendError('Permission Denied');
        }
        $quotation = $this->quotationRepository->find($id);

        return new QuotationResource($quotation);
    }

    /**
     * @param  Quotation  $quotation
     * @return JsonResponse
     */
    public function quotationInfo(Quotation $quotation)
    {
        $quotation = $quotation->load(['quotationItems.product', 'warehouse', 'customer','quotationItems.product' => function ($query) {
          
            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);
        $keyName = [
            'email', 'company_name', 'phone', 'address',
        ];
        $quotation['company_info'] = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();

        return $this->sendResponse($quotation, 'Quotation information retrieved successfully');
    }

    /**
     * @param  Quotation  $quotation
     * @return QuotationResource
     */
    public function edit(Quotation $quotation)
    {
        if (!Auth::user()->can('quotations.edit')) {
            return $this->sendError('Permission Denied');
        }
        $quotation = $quotation->load(['quotationItems.product.stocks', 'warehouse','quotationItems.product' => function ($query) {
          
            $query->with(['variant','productAbstract:id,pan_style',]);
        }]);

        return new QuotationResource($quotation);
    }

    /**
     * @param  UpdateQuotationRequest  $request
     * @param $id
     * @return QuotationResource
     */
    public function update(UpdateQuotationRequest $request, $id)
    {
        if (!Auth::user()->can('quotations.edit')) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $quotation = $this->quotationRepository->updateQuotation($input, $id);

        return new QuotationResource($quotation);
    }

    /**
     * @param  Quotation  $quotation
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Quotation $quotation)
    {
        if (!Auth::user()->can('quotations.delete')) {
            return $this->sendError('Permission Denied');
        }
        $this->quotationRepository->delete($quotation->id);

        return $this->sendSuccess('Quotation Deleted successfully');
    }

    /**
     * @param  Quotation  $quotation
     * @return JsonResponse
     */
    public function pdfDownload(Quotation $quotation): JsonResponse
    {
        $quotation = $quotation->load('customer', 'quotationItems.product');
        $data = [];
        if (Storage::exists('pdf/Quotation-'.$quotation->reference_code.'.pdf')) {
            Storage::delete('pdf/Quotation-'.$quotation->reference_code.'.pdf');
        }
        $companyLogo = getLogoUrl();
        $pdf = PDF::loadView('pdf.quotation-pdf', compact('quotation', 'companyLogo'))->setOptions([
            'tempDir' => public_path(),
            'chroot' => public_path(),
        ]);
        Storage::disk(config('app.media_disc'))->put('pdf/Quotation-'.$quotation->reference_code.'.pdf',
            $pdf->output());
        $data['quotation_pdf_url'] = Storage::url('pdf/Quotation-'.$quotation->reference_code.'.pdf');

        return $this->sendResponse($data, 'Quotation pdf retrieved Successfully');
    }
}
