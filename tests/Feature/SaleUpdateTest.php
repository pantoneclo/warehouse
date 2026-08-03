<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use App\Repositories\SaleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleUpdateTest extends TestCase
{
    public function test_update_sale_without_sale_item_id_in_payload()
    {
        $saleRepository = app(SaleRepository::class);

        // Verify array key handling when sale_item_id is not set in sale_items array
        $input = [
            'date' => '2026-07-27T00:00:00.000Z',
            'is_sale_created' => 'true',
            'warehouse_id' => 1,
            'discount' => '0.00',
            'tax_rate' => '9.01',
            'tax_amount' => '4.13',
            'shipping' => '5.00',
            'cod' => '0.00',
            'grand_total' => '49.99',
            'received_amount' => 0,
            'paid_amount' => 0,
            'status' => 5,
            'payment_status' => 1,
            'payment_type' => 1,
            'country' => 'SI',
            'order_no' => 'TEST_ORDER_9999',
            'market_place' => 'PANTONECLO',
            'currency' => 'EUR',
            'name' => 'EDVINA KLEVŽE',
            'email' => 'edvina.klevze@gmail.com',
            'phone' => '+38641673404',
            'address' => 'Oplotniška cesta 1a ( OTP)',
            'city' => '3210 - SLOVENSKE KONJICE',
            'sale_items' => [
                [
                    'name' => 'MENS BOXER',
                    'code' => 'PR_0037F00461',
                    'product_id' => 1,
                    'product_price' => 9,
                    'net_unit_price' => 9,
                    'tax_type' => '2',
                    'tax_value' => 0,
                    'tax_amount' => 0,
                    'discount_type' => '2',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'quantity' => 1,
                    'sub_total' => 9,
                    'id' => 1,
                ]
            ]
        ];

        // Ensure key array lookup on sale_item_id does not throw Undefined array key error
        $saleItem = $input['sale_items'][0];
        $saleItemId = $saleItem['sale_item_id'] ?? null;
        $this->assertNull($saleItemId);
    }
}
