<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Package;
use App\Models\Variant;
use App\Models\PurchaseItem;
use App\Models\PackageVsProductVsVariant;
use App\Models\PackageVsWarehouse;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Interface PackageRepository.
 *
 * @package namespace App\Repositories;
 */
class PackageRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'code._derived','id'
        ,'created_at'];
    /**
     * @var string[]
     */
    protected $allowedFields = [    'code','id'
    ,'created_at'];
    /**
     * @return array
     */
    public function getAvailableRelations(): array
    {
        return array_values(Package::$availableRelations);
    }

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Package::class;
    }

    /**
     * @param $input
     * @return LengthAwarePaginator|Collection|mixed
     */
    public function storePackage($input)
    {
        try {
            DB::beginTransaction();

            $package = $this->create($input);


            if (isset($input['images']) && !empty($input['images'])) {
                foreach ($input['images'] as $image) {
                    $package['image_url'] = $package->addMedia($image)->toMediaCollection(Package::PATH, config('app.media_disc'));
                }
            }

            $reference_code = $package->generatePackageCodeAndSave();
            $input['code'] = $reference_code;
            $this->generateBarcode($input, $reference_code);
            $package['barcode_image_url'] = $package->getBarcodeImageUrl();

            if (isset($input['package_data']) && is_array($input['package_data'])) {
                foreach ($input['package_data'] as $packageData) {
                    $packageData['package_id'] = $package->id;
                    $product = PackageVsProductVsVariant::create($packageData);
                }
            }

            // Create a single PackageVsWarehouse record for the package
            if (isset($input['package_vs_warehouse_data']) && is_array($input['package_vs_warehouse_data'])) {
                $warehouseData = $input['package_vs_warehouse_data'];
                $warehouseData['package_id'] = $package->id;

                // Create the PackageVsWarehouse record
                PackageVsWarehouse::create($warehouseData);
            }

            DB::commit();

            return $package;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $input
     * @param $id
     * @return LengthAwarePaginator|Collection|mixed
     */

    public function updatePackage($input, $id)
    {
        try {
            DB::beginTransaction();

            $package = Package::findOrFail($id);
            $package->update($input);
            if (isset($input['images']) && !empty($input['images'])) {
                foreach ($input['images'] as $image) {
                    $package->addMedia($image)->toMediaCollection(Package::PATH, config('app.media_disc'));
                }
            }

            // Generate a new barcode if needed
            $reference_code = 'PR_' . $package->id;
            $this->generateBarcode($input, $reference_code);
            $package['barcode_image_url'] = Storage::url('package_barcode/barcode-' . $reference_code . '.png');

            if (isset($input['package_data']) && is_array($input['package_data'])) {
                foreach ($input['package_data'] as $packageData) {
                    $packageData['package_id'] = $package->id;
                    $productId = $packageData['product_id'];
                    $variantId = $packageData['variant_id'];

                    // Find the existing record based on criteria
                    $existingRecord = PackageVsProductVsVariant::where([
                        'package_id' => $package->id,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                    ])->first();

                    if ($existingRecord) {
                        // Update the existing record with the new data
                        $existingRecord->update($packageData);
                    } else {
                        // Create a new record if it doesn't exist
                        PackageVsProductVsVariant::create($packageData);
                    }
                }
            }

            // Update or insert a single PackageVsWarehouse record
            if (isset($input['package_vs_warehouse_data']) && is_array($input['package_vs_warehouse_data'])) {
                $warehouseData = $input['package_vs_warehouse_data'];
                $warehouseData['package_id'] = $package->id;

                // Define criteria for updating or inserting a record
                $criteria = ['package_id' => $package->id];

                // Check if the record already exists
                $existingRecord = PackageVsWarehouse::where($criteria)->first();

                if ($existingRecord) {
                    // Update the 'position' attribute in the JSON column
                    $existingRecord->update([
                        'position' => $warehouseData['position'],
                        // Add other attributes you want to update here
                    ]);
                } else {
                    // Insert the new record
                    PackageVsWarehouse::create($warehouseData);
                }
            }

            DB::commit();

            return $package;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $input
     * @return bool
     */
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

    public function warehouseAddIntoPackage ($input, $id){
        $package = PackageVsWarehouse ::updateOrCreate( ['id' => $id ],
        [ 'package_id' => $input['package_id'],
        'warehouse_id' => $input['warehouse_id'],
          'position' => '{test:1}']);
        dd ($package);
        return $package;
    }

}
