<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Package;
use App\Models\PackageVsProductVsVariant;
use App\Models\Product; // Import the Command class
use App\Models\ProductAbstract; // Import the InputOption class

use App\Models\Variant;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Picqer\Barcode\BarcodeGeneratorPNG;

class CsvToPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    private $csvFile;
    private $batchNo;
    private $batchName;

    public function __construct()
    {
        // Initialize the $csvFile property within the constructor
        // $this->csvFile = public_path('uploads/csv/stock.csv');
        $folderPath = 'csv'; // Change this to your folder path
        $files = Storage::files($folderPath);
        $file_filters = [];
        $tmpBatchId = null;
        $file_pos = 0;
        foreach ($files as $index => $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if (strpos($fileName, 'package') !== false) {
                // The file name contains 'package' substring
                //$fileNameWExtention = str_replace(".csv", "", $fileName);
                $batchId = intval(explode('-', $fileName)[1]);
                if (is_null($tmpBatchId) || $tmpBatchId < $batchId) {
                    $tmpBatchId = $batchId;
                    $file_pos = $index;
                }
            }
        }

        $this->csvFile = is_null($files) ? null : $files[$file_pos];
        $this->batchNo = $tmpBatchId;
        $this->batchName = is_null($files) ? null : pathinfo($files[$file_pos], PATHINFO_FILENAME);
    }

    // protected function getOptions()
    // {
    //     return [
    //         ['update', null, InputOption::VALUE_NONE, 'Update existing db'],
    //     ];
    // }

    public function run()
    {
        $this->command->info('Csv File: ' . $this->csvFile);
        $this->command->info('Batch No: ' . $this->batchNo);

        $is_update = true; //$this->command->option('update');
        $is_debug = false; //$this->command->option('update');

        $csv = Reader::createFromPath(public_path('uploads/' . $this->csvFile), 'r');
        $csv->setHeaderOffset(0);

        try {
            DB::beginTransaction();

            $batch = Batch::firstOrCreate(
                ['type' => 'package', 'name' => $this->batchName],
                ['type' => 'package', 'name' => $this->batchName]
            );

            if (!$is_update && !$batch->wasRecentlyCreated) {
                $this->command->info('Batch name: ' . $this->batchName . ' has already been added');
                return;
            }

            $currentPackage = null;
            $currentProduct = null;
            $currentVariant = null;
            $currentProductAbstract = null;

            $curPackageRefId = null;
            $currentStyle = null;
            $currentColor = null;
            $currentSize = null;
            $currentQuantity = null;
            $count_package = 0;

            foreach ($csv as $record) {

                if (isset($record['Package']) && !is_null($record['Package']) && $record['Package'] != '') {
                    $curPackageRefId = $record['Package'];
                    if ($is_debug) {
                        $this->command->info(++$count_package . " --  " . $record['Package'] . '. ------------------------ ');
                    }

                }

                if (isset($record['Style']) && !is_null($record['Style']) && $record['Style'] != '') {
                    $currentStyle = $record['Style'];
                    if ($is_debug) {
                        $this->command->info('Set Current Style To: ' . $currentStyle);
                    }

                    $currentProductAbstract = ProductAbstract::where('pan_style', $record['Style'])->get()->first();
                    if ($currentProductAbstract === null) {
                        $this->command->error('Product For Current Style : ' . ç . ' Not Found.');
                        continue;
                    }
                }

                if (isset($record['Color']) && !is_null($record['Color']) && $record['Color'] != '') {
                    $currentColor = strtoupper($record['Color']);
                    $currentColor = preg_replace('/\s+/', ' ', trim($currentColor));
                    if ($is_debug) {
                        $this->command->info('Set Current Color To: ' . $currentColor);
                    }

                }

                if (isset($record['Size']) && !is_null($record['Size']) && $record['Size'] != '') {
                    $currentSize = strtoupper($record['Size']);
                    // if($currentSize=='XXL')$currentSize='2XL';
                    if ($is_debug) {
                        $this->command->info('Set Current Size To: ' . $currentSize);
                    }

                }

                if (isset($record['Quantity']) && !is_null($record['Quantity']) && $record['Quantity'] != '') {
                    $currentQuantity = $record['Quantity'];
                    if ($is_debug) {
                        $this->command->info('Set Current Quantity To: ' . $currentQuantity);
                    }

                }

                $currentVariant = Variant::where('name', $currentColor . ' - ' . $currentSize)->get()->first();
                if ($currentVariant === null) {
                    $this->command->error('Product  Not Found. variant : ' . $currentColor . ' - ' . $currentSize . ' style: ' . $currentStyle);
                    continue;
                }

                $currentProduct = Product::where('product_abstract_id', $currentProductAbstract->id)
                    ->where('variant_id', $currentVariant->id)->get()->first();
                if ($currentProduct === null) {
                    $this->command->error('Product  Not Found. variant : ' . $currentColor . ' - ' . $currentSize . ' style: ' . $currentStyle);
                    continue;
                }

                $currentPackage = Package::firstOrCreate(
                    ['batch_id' => $batch->id, 'batch_ref_track_id' => $curPackageRefId ],
                    [
                        'batch_id' => $batch->id,
                        'batch_ref_track_id' => $curPackageRefId ,
                        'barcode_symbol' => 1,
                        'notes' => 'Length 60 x Width: 40 x Height 40',
                        'measurements' => ['length' => '60', 'width' => '40', 'height' => '40', 'weight' => null],
                    ]);

                if ($currentPackage->wasRecentlyCreated) {

                    if ($is_debug) {
                        $this->command->info('New package created id: ' . $currentPackage->id);
                    }

                    $this->generateBarcode(['barcode_symbol'=>1,'code'=>$currentPackage->code], $currentPackage->code);

                } else {
                    if ($is_debug) {
                        $this->command->info('Using existing package id: ' . $currentPackage->id);
                    }

                }

                $package_product_variant = PackageVsProductVsVariant::updateOrInsert(
                    ['product_id' => $currentProduct->id, 'variant_id' => $currentVariant->id, 'package_id' => $currentPackage->id],
                    ['product_id' => $currentProduct->id, 'variant_id' => $currentVariant->id, 'package_id' => $currentPackage->id, 'quantity' => $currentQuantity],
                );

            }

            DB::commit();
            $this->command->info('Data has been seeded successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            $this->command->error($e);
        }
    }

    public function generateBarcode($input, $reference_code): bool
    {
        $barcodeType = null;

        $generator = new BarcodeGeneratorPNG();
        switch ($input['barcode_symbol']) {
            case Package::CODE128:
                $barcodeType = $generator::TYPE_CODE_128;
                break;
            case Package::CODE39:
                $barcodeType = $generator::TYPE_CODE_39;
                break;
            case Package::EAN8:
                $barcodeType = $generator::TYPE_EAN_8;
                break;
            case Package::EAN13:
                $barcodeType = $generator::TYPE_EAN_13;
                break;
            case Package::UPC:
                $barcodeType = $generator::TYPE_UPC_A;
                break;
        }

        Storage::disk(config('app.media_disc'))->put('package_barcode/barcode-' . $reference_code . '.png', $generator->getBarcode($input['code'], $barcodeType, 4, 70));

        return true;
    }
}
