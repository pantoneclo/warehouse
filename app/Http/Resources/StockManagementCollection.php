<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StockManagementCollection extends BaseCollection
{
    public $collects = StockManagementResource::class;
}
