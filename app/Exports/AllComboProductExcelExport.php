<?php

namespace App\Exports;

use App\Models\ComboProduct;
use App\Models\Warehouse;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromView;

class AllComboProductExcelExport implements FromView
{
    public function view(): \Illuminate\Contracts\View\View
    {
        $products = ComboProduct::with('product')->get();

        $warehouses = Warehouse::all(); // Fetch all warehouses

        return view('excel.all-combo-products-excel-export', [
            'products' => $products,
            'warehouses' => $warehouses
        ]);
    }
}
