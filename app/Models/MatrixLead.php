<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class MatrixLead extends BaseModel
{
    use HasFactory;

    protected $table = 'matrix_leads';

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'profile_name',
        'note',
        'file_name',
        'status',
    ];
}
