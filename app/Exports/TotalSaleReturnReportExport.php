<?php

namespace App\Exports;

use App\Models\SaleReturn;
use Maatwebsite\Excel\Concerns\FromView;

class TotalSaleReturnReportExport implements FromView
{
    protected $startDate;
    protected $endDate;
    protected $status;

    public function __construct($startDate, $endDate, $status)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        $startDate =  $this->startDate;
        $endDate = $this->endDate;
        $status = $this->status;
        $saleReturns = SaleReturn::with(['saleReturnItems.product.productAbstract', 'warehouse', 'customer', 'sale.shipment']);
        if ($startDate != 'null' && $endDate != 'null' && $startDate && $endDate) {
            $saleReturns->whereBetween('date', [$startDate, $endDate]);
        }

        if($status != 'null' && $status){
            $saleReturns->where('status', $status);
        }
        $saleReturnsData = $saleReturns->latest()->get();
        return view('excel.all-sale-return-report-excel', ['saleReturns' => $saleReturnsData]);
    }
}
