<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\MatrixLead;
use App\Mail\MatrixLeadMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MatrixLeadAPIController extends AppBaseController
{
    /**
     * Display a listing of the leads.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = getPageSize($request);
        $search = $request->get('filter')['search'] ?? null;
        $order_By = $request->get('sort') ?? '-created_at';

        $query = MatrixLead::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('profile_name', 'like', '%' . $search . '%');
            });
        }

        if (str_starts_with($order_By, '-')) {
            $query->orderBy(substr($order_By, 1), 'desc');
        } else {
            $query->orderBy($order_By, 'asc');
        }

        $leads = $query->paginate($perPage, ['*'], 'page', $request->get('page')['number'] ?? 1);

        return $this->sendResponse($leads, 'Leads retrieved successfully.');
    }

    /**
     * Store a newly created lead in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'profile_name' => 'required|string|max:255',
            'note' => 'required|string',
            'file_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $input = $request->all();
        $input['status'] = 'pending';

        $matrixLead = MatrixLead::create($input);

        // Send Email
        Mail::to('atiq@matrixapparels.com')->send(new MatrixLeadMail($matrixLead));

        return $this->sendResponse($matrixLead, 'Matrix Lead saved and email sent successfully.');
    }
    /**
     * Update the status of the specified lead.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $matrixLead = MatrixLead::find($id);

        if (empty($matrixLead)) {
            return $this->sendError('Matrix Lead not found.');
        }

        $matrixLead->status = $request->get('status');
        $matrixLead->save();

        return $this->sendResponse($matrixLead, 'Status updated successfully.');
    }
}
