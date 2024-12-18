<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSalePaymentRequest;
use App\Http\Resources\SalesPaymentResource;
use App\Models\Sale;
use App\Models\SalesPayment;
use App\Repositories\SalesPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Auth;
class SalesPaymentAPIController extends AppBaseController
{
    /** @var SalesPaymentRepository */
    private $salesPaymentRepository;

    /**
     * SalesPaymentAPIController constructor.
     *
     * @param  SalesPaymentRepository  $salesPaymentRepository
     */
    public function __construct(SalesPaymentRepository $salesPaymentRepository)
    {
        $this->salesPaymentRepository = $salesPaymentRepository;
    }

    /**
     * @param  Sale  $sale
     * @return array
     */
    public function getAllPayments(Sale $sale)
    {
        $data = [
            'sale_id' => $sale->id,
            'data' => $sale->payments,
        ];

        return $data;
    }

    /**
     * @param  Sale  $sale
     * @param  Request  $request
     * @return SalesPaymentResource
     */
    public function createSalePayment(Sale $sale, CreateSalePaymentRequest $request)
    {
        $input = $request->all();

        $salePayment = $this->salesPaymentRepository->storeSalePayment($input, $sale);

        return new SalesPaymentResource($salePayment);
    }

    /**
     * @param  SalesPayment  $salesPayment
     * @param  Request  $request
     * @return SalesPaymentResource
     */
    public function updateSalePayment(SalesPayment $salesPayment, Request $request)
    {
        $input = $request->all();

        $salePayment = $this->salesPaymentRepository->updateSalePayment($input, $salesPayment);

        return new SalesPaymentResource($salePayment);
    }

    public function deletePayment($id)
    {
        try {
            DB::beginTransaction();

            $salePayment = SalesPayment::whereId($id)->firstOrFail();
            $saleID = $salePayment->sale_id;

            $existAmount = SalesPayment::whereSaleId($saleID)->sum('amount') - $salePayment->amount;

            $status = $existAmount <= 0 ? Sale::UNPAID : Sale::PARTIAL_PAID;

            Sale::whereId($saleID)->update([
                'payment_status' => $status,
                'paid_amount' => $existAmount,
            ]);

            SalesPayment::findOrFail($id)->delete();

            $latestPayment = SalesPayment::whereSaleId($saleID)->latest()->first();

            Sale::whereId($saleID)->update([
                'payment_type' => ! empty($latestPayment) ? $latestPayment->payment_type : null,
            ]);

            DB::commit();

            return $this->sendSuccess('Payment deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
