<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\ComboRepository;
use App\Http\Resources\ComboCollection;
use App\Http\Resources\ComboResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Product;
use App\Models\Combo;
use App\Models\ComboProduct;
class ComboController extends AppBaseController
{  


    public function __construct(ComboRepository $comboRepository)
    {
        $this->comboRepository = $comboRepository;
    }

    public function index(Request $request){
        $perPage = getPageSize($request);
        $combos = $this->comboRepository->paginate($perPage);
    // Return the response with the transformed combos

       ComboResource::usingWithCollection();

        return new ComboCollection($combos);

    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'items'                       => 'required|array',
            'combo_name'                       => 'required',
        ]);

        if ( $validator->fails() ) {
            return response()->json([
                'status' => FALSE,
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $data = $request->input('items');
            DB::beginTransaction();

            $combo = Combo::create([
                   'name'=> $request->combo_name,     
                   'sku'=> generateUniqueSKU(Combo::class, 'C', '000001'),     
            ]);


            foreach ( $data as $item ) {
               $combo_Product_sku = generateUniqueSKU(ComboProduct::class, 'COMBO', '000001', 'code');
                $products = $item['product'];
                foreach ( $products as $product ) {
                    $single_product = Product::find($product['product_id']);
                    if (!$single_product) {
                        throw new \Exception("Product not found: {$product['product_id']}");
                    }

                    ComboProduct::create([
                        'combo_id'    => $combo->id,
                        'product_id'    => $product['product_id'],
                        'code'    => $combo_Product_sku,
                    ]);
                }
            }


            DB::commit();
            return response()->json([
                'status'  => TRUE,
                'message' => 'Inventory items created successfully',
                'data'    =>  $combo,
            ]);
        }
        catch ( \Exception $e ) {
            DB::rollBack();
            Log::error('Error creating inventory: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while creating inventory items.',
                'error'   => $e->getMessage()
            ], 500);
        }


    }




    public function show($id)
    {
       
        $comboProduct = $this->comboRepository->find($id);

        return new ComboResource($comboProduct);
    }
}
