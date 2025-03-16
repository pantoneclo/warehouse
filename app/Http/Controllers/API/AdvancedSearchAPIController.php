<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Resources\ProductResource;
use App\Models\ComboProduct;
use App\Models\ManageStock;
use App\Models\Warehouse;
use App\Models\Package;
use App\Models\PackageVsProductVsVariant;
use App\Models\Product;
use App\Models\ProductAbstract;
use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\PackageVsProductVsVariantCollection;
use App\Http\Resources\PackageVsProductVsVariantResource;

class AdvancedSearchAPIController extends AppBaseController
{

    public function searchProduct(Request $request)
    {
        $search_data = $request->input("search");
//        $warehouse_id = 1;
        $result_of_search = [];

        if (substr($search_data, 0, 2) === "PR") {
            $product = Product::where('code', $search_data)->pluck('id')->toArray(); // Use first() to get a single product
            $products = Product::whereIn('id', $product)->get();

            if ($products->isNotEmpty()) {

                ProductResource::usingWithCollection();
                return new ProductCollection($products );
            } else {
                return $this->sendError('Product not found');
            }
        }
        elseif (substr($search_data, 0, 3) === 'PK_') {
            $package_id = Package::where(Package::codeToSearchLogic($search_data))->pluck( 'id');

            if ($package_id!==null) {

                $package_vs_product  = PackageVsProductVsVariant::whereIn('package_id', $package_id)->get() ;

                //dd($package_vs_product) ;

                if (!empty($package_vs_product) && count($package_vs_product) > 0) {
                    PackageVsProductVsVariantResource::usingWithCollection();
                    return new PackageVsProductVsVariantCollection($package_vs_product );
                } else {
                    return $this->sendError('Products not found');
                }

            } else {
                return $this->sendError('Product not found');
            }
        }elseif (substr($search_data, 0, 5) === 'COMBO'){
           $combo_products_ids = ComboProduct::where('code', $search_data)->pluck('product_id')->toArray();

            if (!empty($combo_products_ids)) {
                $products = Product::whereIn('id', $combo_products_ids)->get();


                if ($products->isNotEmpty()) {
                    ProductResource::usingWithCollection();

                    return new ProductCollection($products);
                } else {
                    return $this->sendError('Products not found');
                }
            }

        } else {
            $abstracts_id = ProductAbstract::where('pan_style', $search_data)->pluck('id')->toArray();

            if (!empty($abstracts_id)) {
                $products = Product::whereIn('product_abstract_id', $abstracts_id)->get();


                if ($products->isNotEmpty()) {
                    ProductResource::usingWithCollection();

                    return new ProductCollection($products);
                } else {
                    return $this->sendError('Products not found');
                }
            }
        }

        if (empty($result_of_search)) {
            return $this->sendError('Product not found');
        }

        return $result_of_search;
    }

    public function warehouseProductsSearch(Request $request){
        $warehouse_id = $request->warehouse_id;
        $products = Warehouse::where('id', $warehouse_id)->with('products')->get();

        return response()->json([
            'data'=>$products,
        ]);
    }

}
