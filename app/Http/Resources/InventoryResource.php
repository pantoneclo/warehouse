<?php

namespace App\Http\Resources;

/**
 * Class InventoryResource
 */
class InventoryResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'insert_key' => $this->insert_key,
            'no_of_items_per_box' => $this->no_of_items_per_box,
            'sticker_meas_unit' => $this->sticker_meas_unit,
            'no_of_boxes' => $this->no_of_boxes,
            'net_wt' => $this->net_wt,
            'gross_wt' => $this->gross_wt,
            'carton_meas' => $this->carton_meas,
            'combos' => $this->combo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
