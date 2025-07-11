<?php

use App\Models\Currency;
use App\Models\ManageStock;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Support\Str;

use App\Helpers\StockHelper;


if (!function_exists('getPageSize')) {
    /**
     * @param $request
     * @return mixed
     */
    function getPageSize($request)
    {
        return $request->input('page.size', 10);
    }
}

/**
 * @return string
 */
function getLogoUrl(): string
{
    static $appLogo;

    if (empty($appLogo)) {
        $appLogo = Setting::where('key', '=', 'logo')->first();
    }

    return asset($appLogo->logo);
}

if (!function_exists('getSettingValue')) {
    /**
     * @param $keyName
     * @return mixed
     */
    function getSettingValue($keyName)
    {
        $key = 'setting' . '-' . $keyName;

        static $settingValues;

        if (isset($settingValues[$key])) {
            return $settingValues[$key];
        }

        /** @var Setting $setting */
        $setting = Setting::where('key', '=', $keyName)->first();
        $settingValues[$key] = $setting->value;

        return $setting->value;
    }
}

/**
 * @param  array  $models
 * @param  string  $columnName
 * @param  int  $id
 * @return bool
 */
function canDelete(array $models, string $columnName, int $id): bool
{
    foreach ($models as $model) {
        $result = $model::where($columnName, $id)->exists();

        if ($result) {
            return true;
        }
    }

    return false;
}

function getCurrencyCode()
{
    $currencyId = Setting::where('key', '=', 'currency')->first()->value;

    return Currency::whereId($currencyId)->first()->symbol;
}

function getLoginUserLanguage(): string
{
    return \Illuminate\Support\Facades\Auth::user()->language;
}

if (!function_exists('manageStock')) {
    /**
     * @param $request
     * @return mixed
     */
    function manageStock($warehouseID, $productID, $qty = 0)
    {
        $product = ManageStock::with('product')->whereWarehouseId($warehouseID)
            ->whereProductId($productID)
            ->first();


        if ($product) {
            $totalQuantity = $product->quantity + $qty;

            if (($product->quantity + $qty) < 0) {
                $totalQuantity = 0;
            }
            $product->update([
                'quantity' => $totalQuantity,
            ]);

            StockHelper::manageStockForCodeAndWarehouse($product->product->code, $warehouseID);
        } else {
            if ($qty < 0) {
                $qty = 0;
            }

            ManageStock::create([
                'warehouse_id' => $warehouseID,
                'product_id' => $productID,
                'quantity' => $qty,
            ]);
        }
    }
}

if (!function_exists('keyExist')) {
    function keyExist($key)
    {
        $exists = Setting::where('key', $key)->exists();

        return $exists;
    }
}

function getSupplierGrandTotalFilterIds($search)
{
    $supplierData = Supplier::with('purchases')->get();
    $ids = [];
    foreach ($supplierData as $key => $supplier) {
        $value = $supplier->purchases->sum('grand_total');
        if ($search != '') {
            if ($value == $search) {
                $ids[] = $supplier->id;
            }
        }
    }

    return $ids;
}

if (!function_exists('replaceArrayValue')) {
    function replaceArrayValue(&$array, $key, $replaceValue)
    {
        foreach ($array as $index => $value) {
            if (is_array($value)) {
                $array[$index] = replaceArrayValue($value, $key, $replaceValue);
            }
            if ($index == $key) {
                $array[$index] = $replaceValue;
            }
        }

        return $array;
    }
}

if (!function_exists('getLogo')) {
    function getLogo()
    {
        /** @var Setting $setting */
        $logoImage = Setting::where('key', '=', 'logo')->first()->value;

        $logo = base64_encode(file_get_contents(asset($logoImage)));

        return 'data:image/png;base64,' . $logo;
    }
}

if (!function_exists('currencyAlignment')) {
    function currencyAlignment($amount)
    {
        if (getSettingValue('is_currency_right') != 1) {
            return getCurrencyCode() . ' ' . $amount;
        }

        return $amount . ' ' . getCurrencyCode();
    }
}

if (!function_exists('isBulkRequest')) {
    /**
     * @param $request
     * @return mixed
     * this function will check if request for bulk update create or delete
     */
    function isBulkRequest($array)
    {
        return is_array($array) && count(array_filter(array_keys($array), 'is_numeric')) === count($array);
    }
}
if (!function_exists('areObjectsEqual')) {
    function areObjectsEqual($object1, $object2)
    {
        // Sort the objects by keys
        ksort($object1);
        ksort($object2);

        // Compare the sorted objects
        return $object1 == $object2;
    }
}
if (!function_exists('getLatestStatus')) {
    function getLatestStatus(array $parcelStatusList)
    {
        $latestStatus = null;
        $latestStatusDate = 0;

        foreach ($parcelStatusList as $status) {
            $statusDate = convertStatusDate($status['StatusDate']);

            if ($statusDate !== null && $statusDate > $latestStatusDate) {
                $latestStatusDate = $statusDate;
                $latestStatus = $status;
            }
        }

        return $latestStatus;
    }
}

if (!function_exists('convertStatusDate')) {
    function convertStatusDate($statusDate)
    {
        $matches = [];
        preg_match('/\/Date\((\d+)([+-]\d{4})\)\//', $statusDate, $matches);

        if (isset($matches[1])) {
            $timestamp = $matches[1] / 1000; // Convert milliseconds to seconds
            return $timestamp;
        }

        return null;
    }
}


if(!function_exists('generateUniqueSKU')){
    function generateUniqueSKU($model, $prefix, $startSequence = '000001', $column = 'sku')
        {
            $lastRecord = $model::orderBy('id', 'desc')->first();
            if (!$lastRecord) {
                return $prefix . $startSequence;
            }
            $lastSkuNumber = (int) Str::after($lastRecord->$column, $prefix);
            $newSkuNumber = $lastSkuNumber + 1;
            return $prefix . str_pad($newSkuNumber, strlen($startSequence), '0', STR_PAD_LEFT);
        }
}

