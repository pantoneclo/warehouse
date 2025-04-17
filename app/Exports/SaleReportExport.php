<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromView;

class SaleReportExport implements FromView
{
    protected $startDate;
    protected $endDate;
    protected $status;

    // Constructor to accept start and end dates
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
        $sales = Sale::with(['saleItems.product.productAbstract', 'warehouse', 'customer', 'payments', 'shipment']);
        if ($startDate != 'null' && $endDate != 'null' && $startDate && $endDate) {
            $sales->whereBetween('date', [$startDate, $endDate]);
        }

        if($status != 'null' && $status){
            $sales->where('status', 2);
        }
        $salesData = $sales->latest()->get();
        return view('excel.all-sale-report-excel', ['sales' => $salesData]);
    }
}
