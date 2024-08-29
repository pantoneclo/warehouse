<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PackageCollection extends BaseCollection

{
    

        public $collects = PackageResource::class;

}
