<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Variant;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class ProductCategoryRepository
 */
class VariantRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name',
        'id',
        'variant',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'name',
        'id',
        'variant',
        'created_at',
    ];

    /**
     * @return array
     */
    // public function getAvailableRelations(): array
    // {
    //     return array_values(Variant::$availableRelations);
    // }

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
        return Variant::class;
    }

    /**
     * @param $input
     * @return LengthAwarePaginator|Collection|mixed
     */
    public function storeVariant($input)
    {
        try {
            DB::beginTransaction();

            $input = request()->input(); // Get the input data

            if (\isBulkRequest($input)) {
                $variant = [];
                foreach ($input as $variant_i) {
                    # code...
                    $variant[] = $this->storeSingleVariant($variant_i);
                }
            } else {
                $variant = $this->storeSingleVariant($input);
            }

            DB::commit();
            return $variant;

        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function storeSingleVariant($input)
    {
        $variant = $this->create($input);
        // $reference_code = 'P' . sprintf("%05X",$input['product_id']) . sprintf("%05X",$variant->id);
        // $input['code'] = $reference_code;
        // $variant->code = $reference_code;
        // $variant->save();
        if (isset($input['images']) && !empty($input['images'])) {
            foreach ($input['images'] as $image) {
                $variant['image_url'] = $variant->addMedia($image)->toMediaCollection(Variant::PATH,
                    config('app.media_disc'));
            }
        }


        // $this->generateBarcode($input, $reference_code);
        // $variant['barcode_image_url'] = Storage::url('variant_barcode/barcode-' . $reference_code . '.png');
        return $variant;
    }

    /**
     * @param $input
     * @param $id
     * @return LengthAwarePaginator|Collection|mixed
     */
    public function updateVariant($input, $id)
    {
        try {
            DB::beginTransaction();
            $variant = $this->update($input, $id);

            if (isset($input['images']) && !empty($input['images'])) {
                foreach ($input['images'] as $image) {
                    $variant['image_url'] = $variant->addMedia($image)->toMediaCollection(Variant::PATH,
                        config('app.media_disc'));
                }
            }
            // $variant->clearMediaCollection(Variant::VARIANT_BARCODE_PATH);
            // $reference_code = 'PR_' . $variant->id;
            // $this->generateBarcode($input, $reference_code);
            // $variant['barcode_image_url'] = Storage::url('variant_barcode/barcode-' . $reference_code . '.png');

            DB::commit();

            return $variant;
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
            case Variant::CODE128:
                $barcodeType = $generator::TYPE_CODE_128;
                break;
            case Variant::CODE39:
                $barcodeType = $generator::TYPE_CODE_39;
                break;
            case Variant::EAN8:
                $barcodeType = $generator::TYPE_EAN_8;
                break;
            case Variant::EAN13:
                $barcodeType = $generator::TYPE_EAN_13;
                break;
            case Variant::UPC:
                $barcodeType = $generator::TYPE_UPC_A;
                break;
        }

        Storage::disk(config('app.media_disc'))->put('variant_barcode/barcode-' . $reference_code . '.png',
            $generator->getBarcode($input['code'], $barcodeType, 4, 70));

        return true;
    }
}
