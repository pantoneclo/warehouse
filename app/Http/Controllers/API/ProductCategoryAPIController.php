<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryCollection;
use App\Http\Resources\ProductCategoryResource;
use App\Models\Product;
use App\Models\ProductAbstract;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCategoryAPIController extends AppBaseController
{
    /** @var productCategoryRepository */
    private $productCategoryRepository;

    public function __construct(ProductCategoryRepository $productCategoryRepository)
    {
        $this->productCategoryRepository = $productCategoryRepository;
    }

    /**
     * @param  Request  $request
     * @return ProductCategoryCollection
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can("manage.product.categories")) {
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $sort = null;
        if ($request->sort == 'products_count') {
            $sort = 'asc';
            $request->request->remove('sort');
        } elseif ($request->sort == '-products_count') {
            $sort = 'desc';
            $request->request->remove('sort');
        }
        $productCategory = $this->productCategoryRepository->withCount('products')->when($sort,
            function ($q) use ($sort) {
                $q->orderBy('products_count', $sort);
            })->paginate($perPage);

        ProductCategoryResource::usingWithCollection();

        return new ProductCategoryCollection($productCategory);
    }

    /**
     * @param  CreateProductCategoryRequest  $request
     * @return ProductCategoryResource
     */
    public function store(CreateProductCategoryRequest $request)
    {
        if (!Auth::user()->can("product.category.create")) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $productCategory = $this->productCategoryRepository->storeProductCategory($input);

        return new ProductCategoryResource($productCategory);
    }

    /**
     * @param $id
     * @return ProductCategoryResource
     */
    public function show($id)
    {
        if (!Auth::user()->can("product.category.view")) {
            return $this->sendError('Permission Denied');
        }
        $productCategory = $this->productCategoryRepository->withCount('products')->find($id);

        return new ProductCategoryResource($productCategory);
    }

    /**
     * @param  UpdateProductCategoryRequest  $request
     * @param $id
     * @return ProductCategoryResource
     */
    public function update(UpdateProductCategoryRequest $request, $id)
    {
        if (!Auth::user()->can("product.category.edit")) {
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $productCategory = $this->productCategoryRepository->updateProductCategory($input, $id);

        return new ProductCategoryResource($productCategory);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (!Auth::user()->can("product.category.delete")) {
            return $this->sendError('Permission Denied');
        }
        $productModels = [
            ProductAbstract::class,
        ];
        $result = canDelete($productModels, 'product_category_id', $id);
        if ($result) {
            return $this->sendError('Product category can\'t be deleted.');
        }
        $this->productCategoryRepository->delete($id);

        return $this->sendSuccess('Product category deleted successfully');
    }
}
