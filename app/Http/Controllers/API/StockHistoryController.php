<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockHistoryController extends AppBaseController
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }
        $search = $request->get('search');
        $warehouse_id = $request->get('warehouse_id');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        $query = StockHistory::with(['product', 'warehouse', 'user'])
            ->select('stock_histories.*');

        if ($warehouse_id && $warehouse_id != 'null') {
            $query->where('warehouse_id', $warehouse_id);
        }

        if ($start_date) {
            $query->whereDate('created_at', '>=', $start_date);
        }

        if ($end_date) {
            $query->whereDate('created_at', '<=', $end_date);
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy($sort, $order);

        $histories = $query->paginate($perPage);
        
        // Transform data for frontend if needed
        $data = $histories->getCollection()->transform(function ($history) {
            return [
                'id' => $history->id,
                'created_at' => $history->created_at->toDateTimeString(),
                'warehouse_name' => $history->warehouse->name ?? 'N/A',
                'product_name' => $history->product->name ?? 'N/A',
                'product_code' => $history->product->code ?? 'N/A',
                'quantity_change' => $history->quantity,
                'old_quantity' => $history->old_quantity,
                'new_quantity' => $history->new_quantity,
                'action' => $history->action,
                'reference_type' => class_basename($history->reference_type), // Short name
                'reference_id' => $history->reference_id,
                'user_name' => $history->user ? ($history->user->first_name . ' ' . $history->user->last_name) : 'System',
                'note' => $history->note,
            ];
        });

        $histories->setCollection($data);

        return response()->json($histories);
    }
}
