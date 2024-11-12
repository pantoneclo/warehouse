<?php


namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromView;

class AllProductExcelExport implements FromView
{
    public function view(): \Illuminate\Contracts\View\View
    {

            $products = Product::with('productAbstract', 'variant')->get();


        return view('excel.all-products-excel-export', ['products' => $products]);
    }
}
