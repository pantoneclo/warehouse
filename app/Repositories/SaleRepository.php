<?php

namespace App\Repositories;

use App\Mail\MailSender;
use App\Models\Customer;
use App\Models\MailTemplate;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesPayment;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use App\Services\Expedico\Expedico;
use App\Services\Parcel\GlsParcel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use App\Http\Controllers\API\StockManagementAPIController;
use App\Helpers\StockHelper;

/**
 * Class SaleRepository
 */
class SaleRepository extends BaseRepository
{
    /**
     * @var array
     */


    protected $fieldSearchable = [
        'date',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'tax_amount',
        'grand_total',
        'received_amount',
        'paid_amount',
        'payment_type',
        'note',
        'created_at',
        'reference_code',
        'order_no'
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'date',
        'tax_rate',
        'tax_amount',
        'discount',
        'shipping',
        'grand_total',
        'received_amount',
        'note',
    ];

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
        return Sale::class;
    }

    /**
     * @param $input
     * @return Sale
     */
    public function storeSale($input): Sale
    {

        try {
            DB::beginTransaction();

            $input['date'] = $input['date'] ?? date('Y/m/d');
            $input['is_sale_created'] = $input['is_sale_created'] ?? false;
            $QuotationId = $input['quotation_id'] ?? false;

            // Step 1: Create the customer
            $customerData = [
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'address' => $input['address'],
                'city' => $input['city'],
                'country' => $input['country'],
            ];
            $customer = Customer::where('email', $input['email'])->first();
            if ($customer) {
                $customerId = $customer->id;
            } else {
                $customer = Customer::create($customerData);
                $customerId = $customer->id;
            }


            // Step 2: Prepare sale input array
            $saleInputArray = Arr::only($input, [
                'warehouse_id', 'tax_rate', 'tax_amount', 'discount', 'shipping', 'grand_total',
                'received_amount', 'paid_amount', 'payment_type', 'note', 'date', 'status',
                'payment_status', 'market_place', 'order_no', 'country', 'currency','cod'
            ]);

            // Step 3: Add customer_id to the sale input array
            $saleInputArray['customer_id'] = $customerId; // Use the customer_id

            // Step 4: Create the sale
            /** @var Sale $sale */
            $sale = Sale::create($saleInputArray);

            if ($input['is_sale_created'] && $QuotationId) {
                $quotation = Quotation::find($QuotationId);
                $quotation->update([
                    'is_sale_created' => true,
                ]);
            }
            $sale = $this->storeSaleItems($sale, $input);
            $reference_code = getSettingValue('sale_code') . '_111' . $sale->id;
            $this->generateBarcode($reference_code);
            $sale['barcode_image_url'] = Storage::url('sales/barcode-' . $reference_code . '.png');

            foreach ($input['sale_items'] as $saleItem) {
                $product = ManageStock::whereWarehouseId($input['warehouse_id'])->whereProductId($saleItem['product_id'])->first();
                if ($product && $product->quantity >= $saleItem['quantity']) {
                    $totalQuantity = $product->quantity - $saleItem['quantity'];
                    $product->update([
                        'quantity' => $totalQuantity,
                    ]);
                    StockHelper::manageStockForCodeAndWarehouse($saleItem['code'], $input['warehouse_id']);
                } else {
                    throw new UnprocessableEntityHttpException('Quantity must be less than Available quantity.');
                }
            }

            $mailTemplate = MailTemplate::where('type', MailTemplate::MAIL_TYPE_SALE)->first();
            $smsTemplate = SmsTemplate::where('type', SmsTemplate::SMS_TYPE_SALE)->first();

            $subject = 'Customer sale';

            $customer = Customer::whereId($sale->customer_id)->first();

            $search = [
                '{customer_name}', '{sales_id}', '{sales_date}', '{sales_amount}', '{paid_amount}', '{due_amount}',
                '{app_name}',
            ];

            $totalPayAmount = SalesPayment::whereSaleId($sale->id)->sum('amount');

            $dueAmount = $sale->grand_total - $totalPayAmount;

            $payAmount = 0;

            if (($dueAmount < 0) || ($sale->payment_status == Sale::PAID)) {
                $dueAmount = 0;
                $payAmount = $sale->grand_total;
            }

            $payAmount = number_format($payAmount, 2);
            $dueAmount = number_format($dueAmount, 2);

            $replace = [
                $customer->name, $sale->reference_code, $sale->date, number_format($sale->grand_total, 2), $payAmount, $dueAmount,
                getSettingValue('company_name'),
            ];

            if (!empty($mailTemplate) && $mailTemplate->status == MailTemplate::ACTIVE) {
                $data['data'] = str_replace($search, $replace, $mailTemplate->content);

                Mail::to($customer->email)
                    ->send(new MailSender('emails.mail-sender', $subject, $data));
            }

            if (!empty($smsTemplate) && $smsTemplate->status == SmsTemplate::ACTIVE) {
                $message = str_replace($search, $replace, $smsTemplate->content);

                $client = new \GuzzleHttp\Client();

                $url = SmsSetting::where('key', 'url')->value('value');
                // $token = SmsSetting::where('key', 'token')->value('value');
                //            $url = "https://xrjv8e.api.infobip.com/sms/2/text/advanced";

                $data = SmsSetting::where('key', 'payload')->value('value');

                $data = preg_replace('/\s/', '', $data);

                $data = json_decode($data, true);

                $toKey = SmsSetting::where('key', 'mobile_key')->value('value');
                $number = $customer->phone;

                $messageKey = SmsSetting::where('key', 'message_key')->value('value');

                $data = replaceArrayValue($data, $toKey, $number);
                $data = replaceArrayValue($data, $messageKey, $message);

                $response = $client->post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'form_params' => [$data],
                ]);
            }
            if (isset($input['parcel_number'])) {
                $this->ParcelStatusCreate($input, $sale);

                // $parcel = Shipment::create([
                //     'sale_id' => $sale->id,
                //     'parcel_company_id' => $input['parcel_company_id'],
                //     'parcel_number' => $input['parcel_number'],

                // ]);

                // if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {

                //     $credential = ['gls_username', 'gls_password'];
                //     $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
                //     $pwd = $credentials['gls_password'];
                //     $username = $credentials['gls_username'];
                //     $password = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");

                //     $credentials = [
                //         'username' => $username,
                //         'password' => $password,
                //     ];
                //     $url = 'https://api.mygls.si/ParcelService.svc/json/GetParcelStatuses';
                //     $tracking_number = $parcel->parcel_number;

                //     $glsParcel = new GlsParcel($credentials, null, null, null);
                //     $response = $glsParcel->fetch($tracking_number, $url);

                //     if ($response) {

                //         $parcel = Shipment::whereId($parcel->id)->first();
                //         $reponse = $response->json();
                //         // dd ($reponse);

                //         $parcel->update([

                //             'weight' => $reponse['Weight'],
                //         ]);

                //         $responseArray = json_decode($response, true);

                //         if (isset($responseArray['ParcelStatusList']) && !empty($responseArray['ParcelStatusList'])) {
                //             // Sort the array based on the "StatusDate" field in descending order
                //             usort($responseArray['ParcelStatusList'], function ($a, $b) {
                //                 return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                //             });

                //             $latestStatus = $responseArray['ParcelStatusList'][0];
                //             $parcel->update([

                //                 'status_date' => $latestStatus['StatusDate'],
                //                 'status_description' => $latestStatus['StatusDescription'],
                //                 'depot_city' => $latestStatus['DepotCity'],
                //             ]);

                //         }
                //         // dd($parcel);
                //     }

                // }

            }

            // if (isset($input['parcel_list'])) {
            //     //  dd ($input['parcel_list'][0]['delivery_address']['name']);
            //     $deliveryAddressArray = [

            //         'name' => $input['parcel_list'][0]['delivery_address']['name'],
            //         'street' => $input['parcel_list'][0]['delivery_address']['street'],
            //         'house_number' => $input['parcel_list'][0]['delivery_address']['house_number'],
            //         'house_number_info' => $input['parcel_list'][0]['delivery_address']['house_number_info'],
            //         'zip_code' => $input['parcel_list'][0]['delivery_address']['zip_code'],
            //         'city' => $input['parcel_list'][0]['delivery_address']['city'],
            //         'country_iso_code' => $input['parcel_list'][0]['delivery_address']['country_iso_code'],
            //         'contact_name' => $input['parcel_list'][0]['delivery_address']['contact_name'],
            //         'contact_phone' => $input['parcel_list'][0]['delivery_address']['contact_phone'],
            //         'contact_email' => $input['parcel_list'][0]['delivery_address']['contact_email'],
            //     ];
            //     // dd ('hi');

            //     $pickupAddressArray = [
            //         'name' => $input['parcel_list'][0]['pickup_address']['name'],
            //         'street' => $input['parcel_list'][0]['pickup_address']['street'],
            //         'house_number' => $input['parcel_list'][0]['pickup_address']['house_number'],
            //         'house_number_info' => $input['parcel_list'][0]['pickup_address']['house_number_info'],
            //         'zip_code' => $input['parcel_list'][0]['pickup_address']['zip_code'],
            //         'city' => $input['parcel_list'][0]['pickup_address']['city'],
            //         'country_iso_code' => $input['parcel_list'][0]['pickup_address']['country_iso_code'],
            //         'contact_name' => $input['parcel_list'][0]['pickup_address']['contact_name'],
            //         'contact_phone' => $input['parcel_list'][0]['pickup_address']['contact_phone'],
            //         'contact_email' => $input['parcel_list'][0]['pickup_address']['contact_email'],
            //     ];

            //     $shipment = new Shipment();
            //     $shipment->sale_id = $sale->id;
            //     $shipment->parcel_company_id = $input['parcel_list'][0]['parcel_company_id'];
            //     $shipment->cod_amount = $input['parcel_list'][0]['cod_amount'];
            //     $shipment->cod_reference = $input['parcel_list'][0]['cod_reference'];
            //     $shipment->count = $input['parcel_list'][0]['count'];
            //     $shipment->content = $input['parcel_list'][0]['content'];
            //     $shipment->pickup_date = $input['parcel_list'][0]['pickup_date'];
            //     // $shipment->pickup_date = "/Date(" . (strtotime($input['parcel_list'][0]['pickup_date']) * 1000) . ")/";

            //     if ($input['parcel_list'][0]['delivery_address']) {

            //         $address = ShipmentAddress::create($deliveryAddressArray);

            //         $shipment->delivery_address_id = $address->id;

            //     }
            //     if ($input['parcel_list'][0]['pickup_address']) {

            //         $address = ShipmentAddress::create($pickupAddressArray);
            //         $address->save();
            //         $shipment->pickup_address_id = $address->id;

            //     }
            //     $shipment->save();

            //     if ($input['parcel_list'][0]['parcel_company_id'] == 1 && $input['status'] == 2) {

            //         $credential = ['gls_username', 'gls_password'];
            //         $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
            //         $pwd = $credentials['gls_password'];
            //         $username = $credentials['gls_username'];
            //         $password = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");

            //         $credentials = [
            //             'username' => $username,
            //             'password' => $password,
            //         ];
            //         $url = 'https://api.mygls.si/ParcelService.svc/json/PrintLabels';
            //         $pickupDate = "/Date(" . (strtotime("2023-11-25 23:59:59") * 1000) . ")/";

            //         $clientNumber = 492380936;
            //         $count = $input['parcel_list'][0]['count'];
            //         $codAmount = $input['parcel_list'][0]['cod_amount'];

            //         $additionInfo = array(
            //             "clientNumber" => $clientNumber,
            //             "pickupDate" => $pickupDate,
            //             'url' => $url,
            //             'Count' => $count,
            //             'CODAmount' => $codAmount,

            //         );
            //         $data = $additionInfo;
            //         $ref = [
            //             'CODReference' => "COD TEST REFETRENCE",
            //             "ClientReference" => "TEST REFETRENCE",
            //         ];
            //         $deliveryAddress = new Address($deliveryAddressArray);

            //         $pickupAddress = new Address($pickupAddressArray);

            //         $glsParcel = new GlsParcel($credentials, $pickupAddress, $deliveryAddress, $ref);
            //         $response = $glsParcel->create($data);

            //         return $response;

            //     }

            // }

            DB::commit();

            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $saleItem
     * @return mixed
     */
    public function calculationSaleItems($saleItem)
    {
        $validator = Validator::make($saleItem, SaleItem::$rules);
        if ($validator->fails()) {
            throw new UnprocessableEntityHttpException($validator->errors()->first());
        }

        //discount calculation
        $perItemDiscountAmount = 0;
        $saleItem['net_unit_price'] = $saleItem['product_price'];
        if ($saleItem['discount_type'] == Sale::PERCENTAGE) {
            if ($saleItem['discount_value'] <= 100 && $saleItem['discount_value'] >= 0) {
                $saleItem['discount_amount'] = ($saleItem['discount_value'] * $saleItem['product_price'] / 100) * $saleItem['quantity'];
                $perItemDiscountAmount = $saleItem['discount_amount'] / $saleItem['quantity'];
                $saleItem['net_unit_price'] -= $perItemDiscountAmount;
            } else {
                throw new UnprocessableEntityHttpException('Please enter discount value between 0 to 100.');
            }
        } elseif ($saleItem['discount_type'] == Sale::FIXED) {
            if ($saleItem['discount_value'] <= $saleItem['product_price'] && $saleItem['discount_value'] >= 0) {
                $saleItem['discount_amount'] = $saleItem['discount_value'] * $saleItem['quantity'];
                $perItemDiscountAmount = $saleItem['discount_amount'] / $saleItem['quantity'];
                $saleItem['net_unit_price'] -= $perItemDiscountAmount;
            } else {
                throw new UnprocessableEntityHttpException("Please enter  discount's value between product's price.");
            }
        }

        //tax calculation
        $perItemTaxAmount = 0;
        if ($saleItem['tax_value'] <= 100 && $saleItem['tax_value'] >= 0) {
            if ($saleItem['tax_type'] == Sale::EXCLUSIVE) {
                $saleItem['tax_amount'] = (($saleItem['net_unit_price'] * $saleItem['tax_value']) / 100) * $saleItem['quantity'];
                $perItemTaxAmount = $saleItem['tax_amount'] / $saleItem['quantity'];
            } elseif ($saleItem['tax_type'] == Sale::INCLUSIVE) {
                $saleItem['tax_amount'] = ($saleItem['net_unit_price'] * $saleItem['tax_value']) / (100 + $saleItem['tax_value']) * $saleItem['quantity'];
                $perItemTaxAmount = $saleItem['tax_amount'] / $saleItem['quantity'];
                $saleItem['net_unit_price'] -= $perItemTaxAmount;
            }
        } else {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100 ');
        }
        $saleItem['sub_total'] = ($saleItem['net_unit_price'] + $perItemTaxAmount) * $saleItem['quantity'];

        return $saleItem;
    }

    /**
     * @param $sale
     * @param $input
     * @return mixed
     */
    public function storeSaleItems($sale, $input)
    {
        foreach ($input['sale_items'] as $saleItem) {
            $product = Product::whereId($saleItem['product_id'])->first();

            if (!empty($product) && isset($product->quantity_limit) && $saleItem['quantity'] > $product->quantity_limit) {
                throw new UnprocessableEntityHttpException('Please enter less than ' . $product->quantity_limit . ' quantity of ' . $product->name . ' product.');
            }
            $item = $this->calculationSaleItems($saleItem);
            $saleItem = new SaleItem($item);
            $sale->saleItems()->save($saleItem);
        }

        $subTotalAmount = $sale->saleItems()->sum('sub_total');

//        if ($input['discount'] <= $subTotalAmount) {
//            $input['grand_total'] = $subTotalAmount - $input['discount'];
//        } else {
//            throw new UnprocessableEntityHttpException('Discount amount should not be greater than total.');
//        }
//        if ($input['tax_rate'] <= 100 && $input['tax_rate'] >= 0) {
//            $input['tax_amount'] = $input['grand_total'] * $input['tax_rate'] / 100;
//        } else {
//            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100.');
//        }
//        $input['grand_total'] += $input['tax_amount'];
//        if ($input['shipping'] <= $input['grand_total'] && $input['shipping'] >= 0) {
//            $input['grand_total'] += $input['shipping'];
//        } else {
//            throw new UnprocessableEntityHttpException('Shipping amount should not be greater than total.');
//        }

        if ($input['payment_status'] == Sale::PAID) {
            $input['paid_amount'] = $input['grand_total'];
            SalesPayment::create([
                'sale_id' => $sale->id,
                'payment_date' => Carbon::now(),
                'payment_type' => $input['payment_type'],
                'amount' => $input['paid_amount'],
                'received_amount' => $input['paid_amount'],
            ]);
        } elseif ($input['payment_status'] == Sale::UNPAID) {
            $input['paid_amount'] = 0;
        }

        $input['reference_code'] = getSettingValue('sale_code') . '_111' . $sale->id;
        $sale->update($input);

        return $sale;
    }

    /**
     * @param $input
     * @param $id
     * @return mixed
     */
    public function updateSale($input, $id)
    {

        try {
            DB::beginTransaction();
            $sale = Sale::findOrFail($id);
            $saleItemIds = SaleItem::whereSaleId($id)->pluck('id')->toArray();
            $saleItmOldIds = [];
            foreach ($input['sale_items'] as $key => $saleItem) {
                $product = Product::whereId($saleItem['product_id'])->first();

                if (!empty($product) && isset($product->quantity_limit) && $saleItem['quantity'] > $product->quantity_limit) {
                    throw new UnprocessableEntityHttpException('Please enter less than ' . $product->quantity_limit . ' quantity of ' . $product->name . ' product.');
                }

                //get different ids & update
                $saleItmOldIds[$key] = $saleItem['sale_item_id'];
                $saleItemArray = Arr::only($saleItem, [
                    'sale_item_id', 'product_id', 'product_price', 'net_unit_price', 'tax_type', 'tax_value',
                    'tax_amount', 'discount_type', 'discount_value', 'discount_amount', 'sale_unit', 'quantity',
                    'sub_total'
                ]);
                $this->updateItem($saleItemArray, $input['warehouse_id']);
                //create new product items
                if (is_null($saleItem['sale_item_id'])) {
                    $saleItem = $this->calculationSaleItems($saleItem);
                    $saleItemArray = Arr::only($saleItem, [
                        'product_id', 'product_price', 'net_unit_price', 'tax_type', 'tax_value', 'tax_amount',
                        'discount_type', 'discount_value', 'discount_amount', 'sale_unit', 'quantity', 'sub_total',
                    ]);
                    $sale->saleItems()->create($saleItemArray);
                    $product = ManageStock::whereWarehouseId($input['warehouse_id'])->whereProductId($saleItem['product_id'])->first();
                    if ($product) {
                        if ($product->quantity >= $saleItem['quantity']) {
                            $product->update([
                                'quantity' => $product->quantity - $saleItem['quantity'],
                            ]);
                        } else {
                            throw new UnprocessableEntityHttpException('Quantity must be less than Available quantity.');
                        }
                    }
                }
            }
            $removeItemIds = array_diff($saleItemIds, $saleItmOldIds);
            //delete remove product
            if (!empty(array_values($removeItemIds))) {
                foreach ($removeItemIds as $removeItemId) {
                    // remove quantity manage storage
                    $oldProduct = SaleItem::whereId($removeItemId)->first();
                    $productQuantity = ManageStock::whereWarehouseId($input['warehouse_id'])->whereProductId($oldProduct->product_id)->first();
                    if ($productQuantity) {
                        if ($oldProduct) {
                            $productQuantity->update([
                                'quantity' => $productQuantity->quantity + $oldProduct->quantity,
                            ]);
                        }
                    } else {
                        ManageStock::create([
                            'warehouse_id' => $input['warehouse_id'],
                            'product_id' => $oldProduct->product_id,
                            'quantity' => $oldProduct->quantity,
                        ]);
                    }
                }
                SaleItem::whereIn('id', array_values($removeItemIds))->delete();
            }
            $this->generateBarcode($sale->reference_code);
            $sale['barcode_image_url'] = Storage::url('sales/barcode-' . $sale->reference_code . '.png');
            $sale = $this->updateSaleCalculation($input, $id);

            if (isset($input['parcel_number'])) {

                $parcel = Shipment::find($input['shipment_id']);


                if ($parcel != null) {


                    $parcel->update([
                        'parcel_company_id' => $input['parcel_company_id'],
                        'parcel_number' => $input['parcel_number']]);
                    if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {

                        $credential = ['gls_username', 'gls_password'];
                        $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
                        $pwd = $credentials['gls_password'];
                        $username = $credentials['gls_username'];
                        $password = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");

                        $credentials = [
                            'username' => $username,
                            'password' => $password,
                        ];
                        $url = 'https://api.mygls.si/ParcelService.svc/json/GetParcelStatuses';
                        $tracking_number = $parcel->parcel_number;

                        $glsParcel = new GlsParcel($credentials, null, null, null);
                        $response = $glsParcel->fetch($tracking_number, $url);

                        if ($response) {

                            $parcel = Shipment::whereId($parcel->id)->first();
                            $reponse = $response->json();
                            // dd ($reponse);

                            $parcel->update([

                                'weight' => $reponse['Weight'],
                            ]);

                            $responseArray = json_decode($response, true);

                            if (isset($responseArray['ParcelStatusList']) && !empty($responseArray['ParcelStatusList'])) {
                                // Sort the array based on the "StatusDate" field in descending order
                                usort($responseArray['ParcelStatusList'], function ($a, $b) {
                                    return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                                });

                                $latestStatus = $responseArray['ParcelStatusList'][0];
                                $parcel->update([

                                    'status_date' => $latestStatus['StatusDate'],
                                    'status_description' => $latestStatus['StatusDescription'],
                                    'depot_city' => $latestStatus['DepotCity'],
                                ]);

                            }
                            // dd($parcel);
                        }

                    } elseif ($input['status'] == 2 && $parcel->parcel_company_id == 2) {
                        $username = "be70333cbce4922e";
                        $password = "be70333cbce4922ebf9644b963a7184a";
                        $credentials = [
                            'username' => $username,
                            'password' => $password,
                        ];
                        $tracking_number = $parcel->parcel_number;
                        $expedicoParcel = new Expedico($credentials);
                        $response = $expedicoParcel->fetch($tracking_number);
                        if ($response) {

                            $parcel = Shipment::whereId($parcel->id)->first();
                            if (isset($response['data']) && is_array($response['data'])) {
                                $parcelEvents = $response['data'];

                                // Sort the array based on 'dateTime' in descending order
                                usort($parcelEvents, function ($a, $b) {
                                    return strtotime($b['attributes']['dateTime']) - strtotime($a['attributes']['dateTime']);
                                });

                                // Get the latest 'statusDescription'
                                $latestStatusDescription = isset($parcelEvents[0]['attributes']['statusDescription'])
                                    ? $parcelEvents[0]['attributes']['statusDescription']
                                    : null;
                                $latestDate = isset($parcelEvents[0]['attributes']['dateTime'])
                                    ? $parcelEvents[0]['attributes']['dateTime']
                                    : null;

                                $parcel->update([

                                    'status_date' => $latestDate,
                                    'status_description' => $latestStatusDescription,
                                    'depot_city' => 'null',
                                ]);
                            }

                        }
                    }
                } else {


                    $this->ParcelStatusCreate($input, $sale);
                }

            }
            DB::commit();

            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function ParcelStatusCreate($input, $sale)
    {

        $parcel = Shipment::create([
            'sale_id' => $sale->id,
            'parcel_company_id' => $input['parcel_company_id'],
            'parcel_number' => $input['parcel_number'],
        ]);

        if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {
            $credentials = [
                'username' => env('MYGLS_USERNAME'),
                'password' => json_decode("[" . implode(',', unpack('C*', hash('sha512', env('MYGLS_PASSWORD'), true))) . "]"),
            ];

            $glsParcel = new GlsParcel($credentials);
            $tracking_number = $parcel->parcel_number;
            $response = $glsParcel->fetch($tracking_number);

            if ($response) {
                $parcel = Shipment::find($parcel->id);
                $parcel->update(['weight' => $response['Weight']]);

                if (isset($response['ParcelStatusList']) && !empty($response['ParcelStatusList'])) {
                    usort($response['ParcelStatusList'], function ($a, $b) {
                        return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                    });

                    $latestStatus = $response['ParcelStatusList'][0];
                    $parcel->update([
                        'status_date' => $latestStatus['StatusDate'],
                        'status_description' => $latestStatus['StatusDescription'],
                        'depot_city' => $latestStatus['DepotCity'],
                    ]);
                }
            }
        }

        if ($input['status'] == 2 && $parcel->parcel_company_id == 2) {

            $username = "be70333cbce4922e";
            $password = "be70333cbce4922ebf9644b963a7184a";
            $credentials = [
                'username' => $username,
                'password' => $password,
            ];
            $tracking_number = $parcel->parcel_number;
            $expedicoParcel = new Expedico($credentials);
            $response = $expedicoParcel->fetch($tracking_number);
            if ($response) {

                $parcel = Shipment::whereId($parcel->id)->first();
                if (isset($response['data']) && is_array($response['data'])) {
                    $parcelEvents = $response['data'];

                    // Sort the array based on 'dateTime' in descending order
                    usort($parcelEvents, function ($a, $b) {
                        return strtotime($b['attributes']['dateTime']) - strtotime($a['attributes']['dateTime']);
                    });

                    // Get the latest 'statusDescription'
                    $latestStatusDescription = isset($parcelEvents[0]['attributes']['statusDescription'])
                        ? $parcelEvents[0]['attributes']['statusDescription']
                        : null;
                    $latestDate = isset($parcelEvents[0]['attributes']['dateTime'])
                        ? $parcelEvents[0]['attributes']['dateTime']
                        : null;

                    $parcel->update([

                        'status_date' => $latestDate,
                        'status_description' => $latestStatusDescription,

                    ]);
                }

            }
        }
    }

    public function updateParcelStatus($input)
    {

        if (isset($input['parcel_number'])) {

            $parcel = Shipment::find($input['shipment_id']);
            if ($input['status'] == 2 && $parcel->parcel_company_id == 1) {

                $credential = ['gls_username', 'gls_password'];
                $credentials = Setting::whereIn('key', $credential)->pluck('value', 'key')->toArray();
                $pwd = $credentials['gls_password'];
                $username = $credentials['gls_username'];
                $password = json_decode("[" . implode(',', unpack('C*', hash('sha512', $pwd, true))) . "]");

                $credentials = [
                    'username' => $username,
                    'password' => $password,
                ];
                $url = 'https://api.mygls.si/ParcelService.svc/json/GetParcelStatuses';
                $tracking_number = $parcel->parcel_number;

                $glsParcel = new GlsParcel($credentials, null, null, null);
                $response = $glsParcel->fetch($tracking_number, $url);

                if ($response) {

                    $parcel = Shipment::whereId($parcel->id)->first();
                    $reponse = $response->json();
                    // dd ($reponse);

                    $parcel->update([

                        'weight' => $reponse['Weight'],
                    ]);

                    $responseArray = json_decode($response, true);

                    if (isset($responseArray['ParcelStatusList']) && !empty($responseArray['ParcelStatusList'])) {
                        // Sort the array based on the "StatusDate" field in descending order
                        usort($responseArray['ParcelStatusList'], function ($a, $b) {
                            return strtotime($b['StatusDate']) - strtotime($a['StatusDate']);
                        });

                        $latestStatus = $responseArray['ParcelStatusList'][0];
                        $parcel->update([

                            'status_date' => $latestStatus['StatusDate'],
                            'status_description' => $latestStatus['StatusDescription'],
                            'depot_city' => $latestStatus['DepotCity'],
                        ]);

                    }
                    // dd($parcel);
                }

            }
            if ($input['status'] == 2 && $parcel->parcel_company_id == 2) {

                $username = "be70333cbce4922e";
                $password = "be70333cbce4922ebf9644b963a7184a";
                $credentials = [
                    'username' => $username,
                    'password' => $password,
                ];
                $tracking_number = $parcel->parcel_number;
                $expedicoParcel = new Expedico($credentials);
                $response = $expedicoParcel->fetch($tracking_number);
                if ($response) {

                    $parcel = Shipment::whereId($parcel->id)->first();
                    if (isset($response['data']) && is_array($response['data'])) {
                        $parcelEvents = $response['data'];

                        // Sort the array based on 'dateTime' in descending order
                        usort($parcelEvents, function ($a, $b) {
                            return strtotime($b['attributes']['dateTime']) - strtotime($a['attributes']['dateTime']);
                        });

                        // Get the latest 'statusDescription'
                        $latestStatusDescription = isset($parcelEvents[0]['attributes']['statusDescription'])
                            ? $parcelEvents[0]['attributes']['statusDescription']
                            : null;
                        $latestDate = isset($parcelEvents[0]['attributes']['dateTime'])
                            ? $parcelEvents[0]['attributes']['dateTime']
                            : null;

                        $parcel->update([

                            'status_date' => $latestDate,
                            'status_description' => $latestStatusDescription,

                        ]);
                    }

                }
            }

        }
    }

    /**
     * @param $saleItem
     * @param $warehouseId
     * @return bool
     */
    public function updateItem($saleItem, $warehouseId): bool
    {
        try {
            $saleItem = $this->calculationSaleItems($saleItem);
            $item = SaleItem::whereId($saleItem['sale_item_id']);
            $product = ManageStock::whereWarehouseId($warehouseId)->whereProductId($saleItem['product_id'])->first();
            $oldItem = SaleItem::whereId($saleItem['sale_item_id'])->first();
            if ($oldItem && $oldItem->quantity != $saleItem['quantity']) {
                $totalQuantity = 0;
                if ($oldItem->quantity > $saleItem['quantity']) {
                    if ($product) {
                        $totalQuantity = $product->quantity + ($oldItem->quantity - $saleItem['quantity']);
                        $product->update([
                            'quantity' => $totalQuantity,
                        ]);
                    } else {
                        ManageStock::create([
                            'warehouse_id' => $warehouseId,
                            'product_id' => $saleItem['product_id'],
                            'quantity' => $totalQuantity,
                        ]);
                    }
                } elseif ($oldItem->quantity < $saleItem['quantity']) {
                    $totalQuantity = $product->quantity - ($saleItem['quantity'] - $oldItem->quantity);
                    if ($product->quantity < ($saleItem['quantity'] - $oldItem->quantity)) {
                        throw new UnprocessableEntityHttpException('Quantity must be less than Available quantity.');
                    }
                    $product->update([
                        'quantity' => $totalQuantity,
                    ]);
                }
            }
            unset($saleItem['sale_item_id']);
            $item->update($saleItem);

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param $input
     * @param $id
     * @return mixed
     */
    public function updateSaleCalculation($input, $id)
    {
        // dd ($input['market_place']);
        $sale = Sale::findOrFail($id);
        $subTotalAmount = $sale->saleItems()->sum('sub_total');

        if ($input['discount'] > $subTotalAmount || $input['discount'] < 0) {
            throw new UnprocessableEntityHttpException('Discount amount should not be greater than total.');
        }
        $input['grand_total'] = $subTotalAmount - $input['discount'];
        if ($input['tax_rate'] > 100 || $input['tax_rate'] < 0) {
            throw new UnprocessableEntityHttpException('Please enter tax value between 0 to 100.');
        }
        $input['tax_amount'] = $input['grand_total'] * $input['tax_rate'] / 100;

        $input['grand_total'] += $input['tax_amount'];

        if ($input['shipping'] > $input['grand_total'] || $input['shipping'] < 0) {
            throw new UnprocessableEntityHttpException('Shipping amount should not be greater than total.');
        }

        $input['grand_total'] += $input['shipping'];

        $sale->first();
        $saleExistGrandTotal = $sale->grand_total;

        if ($input['payment_status'] == Sale::PAID && $input['grand_total'] > $saleExistGrandTotal) {
            $input['payment_status'] = Sale::PARTIAL_PAID;
        }

        $saleInputArray = Arr::only($input, [
            'customer_id', 'warehouse_id', 'tax_rate', 'tax_amount', 'discount', 'shipping', 'grand_total',
            'received_amount', 'paid_amount', 'payment_type', 'note', 'date', 'status', 'payment_status'
            , 'market_place', 'order_no', 'country'
        ]);
        $sale->update($saleInputArray);

        return $sale;
    }

    /**
     * @param $input
     * @return bool
     */
    public function generateBarcode($code): bool
    {
        $generator = new BarcodeGeneratorPNG();
        $barcodeType = $generator::TYPE_CODE_128;

        Storage::disk(config('app.media_disc'))->put('sales/barcode-' . $code . '.png',
            $generator->getBarcode(Sale::CODE128, $barcodeType, 4, 70));

        return true;
    }
}
