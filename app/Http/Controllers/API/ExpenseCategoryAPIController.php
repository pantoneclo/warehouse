<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryCollection;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\Expense;
use App\Repositories\ExpenseCategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Validator\Exceptions\ValidatorException;
use Illuminate\Support\Facades\Auth;
/**
 * Class ExpenseCategoryAPIController
 */
class ExpenseCategoryAPIController extends AppBaseController
{
    /** @var ExpenseCategoryRepository */
    private $expenseCategoryRepository;

    public function __construct(ExpenseCategoryRepository $expenseCategoryRepository)
    {
        $this->expenseCategoryRepository = $expenseCategoryRepository;
    }

    /**
     * @param  Request  $request
     * @return ExpenseCategoryCollection
     */
    public function index(Request $request)
    {
        if(!Auth::user()->can("manage.expense.categories")){
            return $this->sendError('Permission Denied');
        }
        $perPage = getPageSize($request);
        $expenseCategories = $this->expenseCategoryRepository->paginate($perPage);
        ExpenseCategoryResource::usingWithCollection();

        return new ExpenseCategoryCollection($expenseCategories);
    }

    /**
     * @param  CreateExpenseCategoryRequest  $request
     * @return ExpenseCategoryResource
     *
     * @throws ValidatorException
     */
    public function store(CreateExpenseCategoryRequest $request)
    { 
        if(!Auth::user()->can("manage.expense.categories")){
        return $this->sendError('Permission Denied');
    }
        $input = $request->all();
        $expenseCategory = $this->expenseCategoryRepository->create($input);

        return new ExpenseCategoryResource($expenseCategory);
    }

    /**
     * @param $id
     * @return ExpenseCategoryResource
     */
    public function show($id)
    {
        if(!Auth::user()->can("manage.expense.categories")){
            return $this->sendError('Permission Denied');
        }
        $expenseCategory = $this->expenseCategoryRepository->find($id);

        return new ExpenseCategoryResource($expenseCategory);
    }

    /**
     * @param  UpdateExpenseCategoryRequest  $request
     * @param $id
     * @return ExpenseCategoryResource
     *
     * @throws ValidatorException
     */
    public function update(UpdateExpenseCategoryRequest $request, $id)
    {
        if(!Auth::user()->can("manage.expense.categories")){
            return $this->sendError('Permission Denied');
        }
        $input = $request->all();
        $expenseCategory = $this->expenseCategoryRepository->update($input, $id);

        return new ExpenseCategoryResource($expenseCategory);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if(!Auth::user()->can("manage.expense.categories")){
            return $this->sendError('Permission Denied');
        }
        $expenseModels = [
            Expense::class,
        ];
        $result = canDelete($expenseModels, 'expense_category_id', $id);
        if ($result) {
            return $this->sendError('Expense category can\'t be deleted.');
        }
        $this->expenseCategoryRepository->delete($id);

        return $this->sendSuccess('Expense category deleted successfully');
    }
}
