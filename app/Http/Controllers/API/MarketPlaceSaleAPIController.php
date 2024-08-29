<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Http\Resources\SaleCollection;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Hold;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Repositories\SaleRepository;
use App\Services\Parcel\Address;
use App\Services\Parcel\GlsParcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class MarketPlaceSaleAPIController extends AppBaseController
{
         
    public function getProductDetails(Request $request)
    {
        // Example of handling multiple codes
        $codes = ['PR_002640026F', 'PR_0026400083', 'PR_002620056B', 'PR_0000200069']; // This could come from $request->input('codes')
        
        $products = Product::whereIn('code', $codes)->with(['stock'])->get();
    
        // Check if products were found
        if ($products->isEmpty()) {
            return response()->json(['message' => 'Products not found'], 404);
        }
    
        // Add warehouse data for each product
        $products->each(function ($product) {
            $product->warehouse = $product->warehouse($product->id);
        });
    
        return response()->json([
            'data' => $products
        ]);
    }
    

}