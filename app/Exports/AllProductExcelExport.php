<?php


namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromView;

use App\Models\Managestock;

class AllProductExcelExport implements FromView
{
    public function view(): \Illuminate\Contracts\View\View
    {

            $products = Product::with('productAbstract', 'variant')->get();

            // Attach stock quantity to each product
            foreach ($products as $product) {
                $product->stock_quantity = Managestock::where('product_id', $product->id)->sum('quantity');
            }


        return view('excel.all-products-excel-export', ['products' => $products]);
    }
}
