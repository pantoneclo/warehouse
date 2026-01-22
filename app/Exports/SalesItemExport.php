<?php

namespace App\Exports;

use App\Models\SaleItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;

class SalesItemExport implements FromView
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
        $request = $this->request;
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $search = $request->get('search');
        $warehouse_id = $request->get('warehouse_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        \Illuminate\Support\Facades\Log::info('SalesItemExport Params:', $request->all());

        // Main Query
        $query = SaleItem::select(
                'sale_items.id',
                'sales.order_no as reference_code',
                'sales.date as sale_date',
                'sales.currency as currency_code',
                'currencies.id as currency_id',
                DB::raw('(SELECT conversion_rate 
                          FROM currency_histories 
                          WHERE currency_histories.currency_id = currencies.id 
                          AND currency_histories.date <= sales.date 
                          ORDER BY currency_histories.date DESC 
                          LIMIT 1) as effective_rate_history'),
                'currencies.conversion_rate as currency_table_rate', 
                'products.code as sku',
                'products.name as product_name',
                'products.product_cost as fob',
                'products.product_price as product_price',
                DB::raw('COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1)) as effective_rate_used'),
                DB::raw('sale_items.net_unit_price * COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1)) as selling_price'),
                'sale_items.quantity as quantity',
                DB::raw('sale_items.sub_total * COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1)) as total'),
                DB::raw('((sale_items.net_unit_price * COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1))) - products.product_price) * sale_items.quantity as margin'),
                'manage_stocks.quantity as available_stock'
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('currencies', 'sales.currency', '=', 'currencies.code')
            ->leftJoin('manage_stocks', function($join) {
                $join->on('sale_items.product_id', '=', 'manage_stocks.product_id')
                     ->on('sales.warehouse_id', '=', 'manage_stocks.warehouse_id');
            });

        // Filters
        if ($warehouse_id && $warehouse_id != 'null') {
            $query->where('sales.warehouse_id', $warehouse_id);
        }

        if ($request->get('status') && $request->get('status') != 'null') {
            $query->where('sales.status', $request->get('status'));
        }

        if ($request->get('country_id') && $request->get('country_id') != 'null') {
            $query->where('sales.country', $request->get('country_id'));
        }

        if ($request->get('payment_status') && $request->get('payment_status') != 'null') {
            $query->where('sales.payment_status', $request->get('payment_status'));
        }

        if ($start_date) {
            $query->whereDate('sales.date', '>=', $start_date);
        }

        if ($end_date) {
            $query->whereDate('sales.date', '<=', $end_date);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('sales.reference_code', 'like', "%{$search}%")
                  ->orWhere('sales.order_no', 'like', "%{$search}%")
                  ->orWhere('products.code', 'like', "%{$search}%")
                  ->orWhere('products.name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortMap = [
            'created_at' => 'sales.created_at',
            'reference_code' => 'sales.reference_code',
            'sku' => 'products.code',
            'product_name' => 'products.name',
            'sale_date' => 'sales.date',
        ];

        $sortColumn = $sortMap[$sort] ?? $sort;
        
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }

        $query->orderBy($sortColumn, $order);

        $reports = $query->get();

        return view('excel.sales-item-report-excel', ['reports' => $reports]);
    }
}
