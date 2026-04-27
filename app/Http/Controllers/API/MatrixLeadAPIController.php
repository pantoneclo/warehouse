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
    public function index()
    {
        $leads = MatrixLead::orderBy('created_at', 'desc')->get();

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
        Mail::to('shohel@matrixapparels.com')->send(new MatrixLeadMail($matrixLead));

        return $this->sendResponse($matrixLead, 'Matrix Lead saved and email sent successfully.');
    }
}
