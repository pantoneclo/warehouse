<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesItemExport;

use Illuminate\Support\Facades\Storage;

class SalesItemReportController extends AppBaseController
{
    public function salesItemExport(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('ExportController Query:', $request->query());
        \Illuminate\Support\Facades\Log::info('ExportController All:', $request->all());

        $filename = 'excel/sales-item-report-' . time() . '.xlsx';
        Excel::store(new SalesItemExport($request), $filename);

        $data['url'] = Storage::url($filename);

        return $this->sendResponse($data, 'Sales Item Report Export retrieved successfully');
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $search = $request->get('search');
        $warehouse_id = $request->get('warehouse_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
    
        // Main Query
        $query = SaleItem::select(
                'sale_items.id',
                'sales.order_no as reference_code', // User asked to use order_no as Order ID
                'sales.date as sale_date',
                'sales.currency as currency_code', // Display currency code
                'currencies.id as currency_id', // Joined ID
                // Subquery for effective conversion rate
                // We use currencies.id which comes from the join on sales.currency = currencies.code
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
                'products.product_price as product_price',
                'product_abstracts.pan_style as po_no', // Added PO Number
                'sales.country as country_code',         // Removed Join, selecting Raw Code
                'sales.market_place as market_place',    // Added Market Place
                // Explicitly show the rate used
                DB::raw('COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1)) as effective_rate_used'),
                // Calculations using the EXACT same logic
                DB::raw('sale_items.net_unit_price * COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1)) as selling_price'),
                'sale_items.quantity as quantity',
                DB::raw('sale_items.sub_total * COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1)) as total'),
                DB::raw('((sale_items.net_unit_price * COALESCE((SELECT conversion_rate FROM currency_histories WHERE currency_histories.currency_id = currencies.id AND currency_histories.date <= sales.date ORDER BY currency_histories.date DESC LIMIT 1), COALESCE(NULLIF(currencies.conversion_rate, 0), 1))) - products.product_price) * sale_items.quantity as margin'),
                'manage_stocks.quantity as available_stock'
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_abstracts', 'products.product_abstract_id', '=', 'product_abstracts.id') // Join Abstract for PO
            // ->leftJoin('countries', 'sales.country', '=', 'countries.id') // REMOVED Join
            // JOIN BY CODE AS REQUESTED
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

        if ($request->get('payment_status') && $request->get('payment_status') != 'null') {
            $query->where('sales.payment_status', $request->get('payment_status'));
        }

        if ($request->get('country_id') && $request->get('country_id') != 'null') {
            // Check if country_id column exists in sales table or linked via warehouse/customer? 
            // The Sale model has 'country_id' in fillable, so it exists.
            $query->where('sales.country', $request->get('country_id'));
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
                  ->orWhere('sales.order_no', 'like', "%{$search}%") // Added order_no search explicitly
                  ->orWhere('products.code', 'like', "%{$search}%")
                  ->orWhere('products.name', 'like', "%{$search}%");
            });
        }
    
        // Sorting
        // Map frontend sort columns to DB columns if needed
        $sortMap = [
            'created_at' => 'sales.created_at',
            'reference_code' => 'sales.reference_code',
            'sku' => 'products.code',
            'product_name' => 'products.name',
            'sale_date' => 'sales.date',
        ];
    
        $sortColumn = $sortMap[$sort] ?? $sort;
        
        // Ensure default sort direction
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }
    
        $query->orderBy($sortColumn, $order);
    
        $results = $query->paginate((int)$perPage);
    
        return $results;
    }
}
