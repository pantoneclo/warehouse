<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title> Sale report pdf</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.ico') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fonts -->
    <!-- General CSS Files -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <style>
        /* Remove borders from the table */
        table {
            border-collapse: collapse; /* Prevent double borders */
            border: none; /* No border for the table itself */
            width: 100%; /* Ensure full width */
        }
        th, td {
            border: 1px solid black; /* Borders only for cells */
            padding: 10px; /* Padding for cell content */
            text-align: center; /* Center-align text */
        }
        .header-row {
            background-color: dodgerblue;
            font-weight: bold;
        }
        .content-row {
            background-color: white; /* Set background color for content rows */
        }
    </style>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="10" style="margin-top: 40px;">
    <thead>
    <tr style="background-color: dodgerblue; border: 5px solid black; font-weight: bold;">
        <th style="text-align: center;" colspan="22">RETAILS REPORT.</th>
    </tr>
    <tr style="background-color: dodgerblue; border: 5px solid black; font-weight: bold; height:">
        <th style=" text-align: center; ">{{ __('messages.sl_no') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.selling_country') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.selling_market') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.date') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.order_no') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.style_no') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.item_descriptuion') }}</th> 
        
        

        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.ttl_sale_item') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.currency') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.selling_value') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.conversion_rate') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.selling_value_eur') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.vat') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.mp_comm') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.mp_mf') }}</th>
        
        
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.courier_fee') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.parcel_company') }}</th>
        
        <th style=" text-align: center; width: 200%">SALES RETURN</th>
        <th style=" text-align: center; width: 200%">PROMOTION</th>
        <th style=" text-align: center; width: 200%">META COST</th>
       <th style=" text-align: center; width: 200%">{{ __('messages.pdf.received_amount') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.op_fee') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.other_cost') }}</th>
    
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.total_fob') }}</th>
        <th style=" text-align: center; width: 200%">PROFIT/LOSS</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.payment_method') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.status') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.payment_status') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.customer') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.warehouse') }}</th>



    </tr>
    </thead>
    <tbody>
        @php
          $sum_grand_total = 0;
          $sum_selling_value_euro_total = 0;
        @endphp
    @foreach($sales  as $key=>$sale)
        @php
            $sum_grand_total += $sale->grand_total;
            $sum_selling_value_euro_total += $sale->selling_value_eur;

           $panStyles = $sale->saleItems
            ->map(function($item) {
                return optional($item->product->productAbstract)->pan_style;
            })
            ->filter()
            ->groupBy(function($style) {
                return $style;
            })
            ->map(function($group, $style) {
                return count($group) > 1 ? "{$style}(" . count($group) . ")" : $style;
            })
            ->values()
            ->implode(', ');

           $fobSum = $sale->saleItems->sum(function ($item) {
                        return optional(optional($item->product)->productAbstract)->base_cost;
                    });
            $item_name  =   $sale->saleItems ->map(function($item) {
                return $item->product->name;
            })->values()
            ->implode(', ');       
        @endphp
        <tr align="center" style="background-color: dodgerblue; border: 5px solid black; font-weight: bold;">
            <td style="text-align: center;">{{$key+1}}</td>
            <td style="text-align: center;">{{$sale->country}}</td>
            <td style="text-align: center;">{{ ucfirst(strtolower($sale->market_place)) }}</td>
            <td style="text-align: center;">{{\Carbon\Carbon::parse($sale->date)->format('d-m-Y')}}</td>
            <td style="text-align: center;">{{$sale->order_no}}</td>
            <td style="text-align: center;">{{$panStyles}}</td>
            <td style="text-align: center;">{{$item_name}}</td>
            @if(count($sale->saleItems) > 0)
                <td style="text-align: center;">{{count($sale->saleItems)}}  </td>
            @else
                <td style="text-align: center;" >0</td>
            @endif

            <td style="text-align: center;">{{$sale->currency}}</td>



            <td style="text-align: center;">{{ (float) str_replace(',', '', $sale->grand_total) }}</td>
            <td style="text-align: center;">{{$sale->conversion_rate??1}}</td>
            <td style="text-align: center;">{{$sale->selling_value_eur}}</td>
            <td style="text-align: center;">{{$sale->tax_amount}}</td>
            <td style="text-align: center;">{{$sale->marketplace_commission}}</td>
            <td style="text-align: center;"></td>
             <td style="text-align: center;">{{$sale->shipping}}</td>
            <td style="text-align: center;">
                @if($sale->shipment)
                    @if($sale->shipment->parcel_company_id == 1)
                        GLS
                    @elseif($sale->shipment->parcel_company_id == 2)
                        EXPEDICO
                    @elseif($sale->shipment->parcel_company_id == 3)
                        REDEX
                    @elseif($sale->shipment->parcel_company_id == 5)
                        PACKETA
                    @elseif($sale->shipment->parcel_company_id == 6)
                        PATHAO
                    @elseif($sale->shipment->parcel_company_id == 4)
                        PACTIC
                    @elseif($sale->shipment->parcel_company_id == 7)
                        FAN COURIER    
                    @else
                        NOT YET
                    @endif
                @else
                    NOT YET
                @endif
            </td>
           
            <td style="text-align: center;">
                @if($sale->is_return)
                   RETURNED
                @else
                    NO
                @endif
            </td>
             <td style="text-align: center;"></td>
              <td style="text-align: center;"></td>
            <td>{{number_format((float)$sale->payments->sum('amount'), 2)}}</td>
            <td style="text-align: center;">{{$sale->order_process_fee}}</td>
            <td style="text-align: center;">{{$sale->other_cost}}</td>
            
           <td style="text-align: center;">{{$fobSum}}</td>
           <td style="text-align: center;"></td>
            <td style="text-align: center;">
                @if($sale->payment_type)
                    @if($sale->payment_type== 1)
                        COD
                    @elseif($sale->payment_type== 2)
                        CHEQUE
                    @elseif($sale->payment_type== 3)
                        BANK TRANSFER
                    @elseif($sale->payment_type== 4)
                        OTHER
                    @elseif($sale->payment_type== 5)
                        COD
                    @elseif($sale->payment_type== 6)
                        SSLCOMMERZ
                    @elseif($sale->payment_type== 7)
                        STRIPE
                    @elseif($sale->payment_type== 0)
                        NEED UPDATE
                    @else
                        NOT YET
                    @endif
                @else
                    NOT YET
                @endif
            </td>
            @if($sale->status == \App\Models\Sale::COMPLETED)
                <td>Confirmed</td>
            @elseif($sale->status == \App\Models\Sale::PENDING)
                <td>Pending</td>
            @elseif($sale->status == \App\Models\Sale::ORDERED)
                <td>Picked Up</td>
            @elseif($sale->status == \App\Models\Sale::ONTHEWAY)
                <td>On The Way</td>
            @elseif($sale->status == \App\Models\Sale::DELIVERED)
                <td>Delivered</td>
            @elseif($sale->status == \App\Models\Sale::CANCLED)
                <td>Canceled</td>
            @elseif($sale->status == \App\Models\Sale::FAILED_ORDER)
                <td>Failed Order</td>
            @elseif($sale->status == \App\Models\Sale::RETURNED)
                <td>Returned</td>
            @endif

             @if($sale->payment_status == \App\Models\Sale::PAID)
                <td>paid</td>
            @elseif($sale->payment_status == \App\Models\Sale::UNPAID)
                <td>unpaid</td>
            @elseif($sale->payment_status == \App\Models\Sale::PARTIAL_PAID)
                <td>partial</td>
            @elseif($sale->payment_status == \App\Models\Sale::REFUND)
                <td>refunded</td>
            @endif
             <td style="text-align: center;">{{$sale->customer->name}}</td>
             <td style="text-align: center;">{{$sale->warehouse->name}}</td>
        </tr>
    @endforeach
    </tbody>
{{--    <tfoot style="border: 2px solid black;">--}}
{{--    <tr class="footer-row">--}}
{{--        <td colspan="2" style="text-align: center;">Total:</td>--}}
{{--        <td></td>--}}
{{--        <td></td>--}}
{{--        <td></td>--}}
{{--        <td></td>--}}
{{--        <td></td>--}}
{{--        <td></td>--}}
{{--        <td>{{ number_format($sum_grand_total)}}</td>--}}
{{--        <td>{{ number_format( $sum_selling_value_euro_total, 2) }}</td>--}}
{{--        <td>{{ number_format($sales->sum('tax_amount'), 2) }}</td>--}}
{{--        <td colspan="14"></td> <!-- Empty cells for the rest of the columns -->--}}
{{--    </tr>--}}
{{--    </tfoot>--}}
</table>
</body>
</html>
