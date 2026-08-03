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
        $search_data = trim($request->input("search", ""));
        if (empty($search_data)) {
            return response()->json(['data' => []]);
        }

        if (substr($search_data, 0, 2) === "PR") {
            $products = Product::where('code', 'LIKE', '%' . $search_data . '%')
                ->orWhere('code', $search_data)
                ->get();

            if ($products->isNotEmpty()) {
                ProductResource::usingWithCollection();
                return new ProductCollection($products);
            }
            return response()->json(['data' => []]);
        }
        elseif (substr($search_data, 0, 3) === 'PK_') {
            $package_id = Package::where(Package::codeToSearchLogic($search_data))->pluck('id');

            if ($package_id !== null && count($package_id) > 0) {
                $package_vs_product = PackageVsProductVsVariant::whereIn('package_id', $package_id)->get();

                if (!empty($package_vs_product) && count($package_vs_product) > 0) {
                    PackageVsProductVsVariantResource::usingWithCollection();
                    return new PackageVsProductVsVariantCollection($package_vs_product);
                }
            }
            return response()->json(['data' => []]);
        }
        elseif (substr($search_data, 0, 5) === 'COMBO') {
            $combo_products_ids = ComboProduct::where('code', 'LIKE', '%' . $search_data . '%')
                ->orWhere('code', $search_data)
                ->pluck('product_id')
                ->toArray();

            if (!empty($combo_products_ids)) {
                $products = Product::whereIn('id', $combo_products_ids)->get();

                if ($products->isNotEmpty()) {
                    ProductResource::usingWithCollection();
                    return new ProductCollection($products);
                }
            }
            return response()->json(['data' => []]);
        }
        else {
            $abstracts_id = ProductAbstract::where('pan_style', 'LIKE', '%' . $search_data . '%')->pluck('id')->toArray();
            $products = Product::whereIn('product_abstract_id', $abstracts_id)
                ->orWhere('code', 'LIKE', '%' . $search_data . '%')
                ->orWhere('name', 'LIKE', '%' . $search_data . '%')
                ->get();

            if ($products->isNotEmpty()) {
                ProductResource::usingWithCollection();
                return new ProductCollection($products);
            }
            return response()->json(['data' => []]);
        }
    }

    public function warehouseProductsSearch(Request $request){
        $warehouse_id = $request->warehouse_id;
        $products = Warehouse::where('id', $warehouse_id)->with('products')->get();

        return response()->json([
            'data'=>$products,
        ]);
    }

}
