<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

use App\Models\Product;

class BarcodeGenerateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public static function generateBarcode($input, $reference_code): bool
    {
        $barcodeType = null;
        $generator = new BarcodeGeneratorPNG();

        switch ($input['barcode_symbol']) {
            case Product::CODE128:
                $barcodeType = $generator::TYPE_CODE_128;
                break;
            case Product::CODE39:
                $barcodeType = $generator::TYPE_CODE_39;
                break;
            case Product::EAN8:
                $barcodeType = $generator::TYPE_EAN_8;
                break;
            case Product::EAN13:
                $barcodeType = $generator::TYPE_EAN_13;
                break;
            case Product::UPC:
                $barcodeType = $generator::TYPE_UPC_A;
                break;
        }

        Storage::disk(config('app.media_disc'))->put('product_barcode/barcode-' . $reference_code . '.png',
            $generator->getBarcode($input['code'], $barcodeType, 4, 70));

        return true;
    }

    public function run()
    {

        $products = Product::all();


        foreach ($products as $product) {
            $code = $product->generateProductCode();
            $input['code'] = $code;
            $input['barcode_symbol'] = Product::CODE128;
            $this->generateBarcode($input, $code);
            $product['barcode_image_url'] = Storage::url('product_barcode/barcode-' . $code . '.png');
        }
    }
}
