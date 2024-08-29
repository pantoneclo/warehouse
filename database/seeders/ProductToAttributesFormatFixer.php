<?php

namespace Database\Seeders;

use App\Models\ProductAbstract;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductToAttributesFormatFixer extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        try {
            DB::beginTransaction();

            $globalColors = [];
            $globalSizes = [];

            $allabstracts = ProductAbstract::all();

            foreach ($allabstracts as $abstract) {
                $products = $abstract->products;
                $size = [];
                $color = [];
                foreach ($products as $product) {
                    //push color
                    if (!in_array($product->variant->variant['color'], $color)) {
                        $color[] = $product->variant->variant['color'];
                    }
                    //push size
                    if (!in_array($product->variant->variant['size'], $size)) {
                        $size[] = $product->variant->variant['size'];
                    }
                }

                //dd([$size,$color]);

                //checks if normal array or key mapped array
                if (is_null($abstract->attributes) || isBulkRequest($abstract->attributes)) {
                    //normal array
                    $abstract->attributes = [
                        'size' => $size,
                        'color' => $color,
                    ];

                    $abstract->save();

                    $this->command->warn('Attributes field changed for ProductAbstract: ' . $abstract->id . ', Style: ' . $abstract->pan_style . ', Data: ' . print_r($abstract->attributes, true));
                }

                $globalSizes = array_unique(array_merge($globalSizes, $size));
                $globalColors = array_unique(array_merge($globalColors, $color));

                Setting::updateOrCreate(
                    ['key'=>'possible_variant_list'],
                    ['key'=>'possible_variant_list' , 'value' => json_encode(['size'=>$globalSizes,'color'=>$globalColors])]
                );
            }



            DB::commit();
            $this->command->info('Data has been seeded successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            $this->command->error($e);
        }
    }
}
