<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title> Sales Return report pdf</title>
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
        <th style="text-align: center;" colspan="23">SALES RETURN REPORT.</th>
    </tr>
    <tr style="background-color: dodgerblue; border: 5px solid black; font-weight: bold;">
        <th style=" text-align: center; ">{{ __('messages.sl_no') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.selling_country') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.selling_market') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.date') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.order_no') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.style_no') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.item_descriptuion') }}</th> 
        
        <th style=" text-align: center; width: 200%">TOTAL RETURN ITEMS</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.currency') }}</th>
        <th style=" text-align: center; width: 200%">RETURN VALUE</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.conversion_rate') }}</th>
        <th style=" text-align: center; width: 200%">RETURN VALUE (EUR)</th>
        <th style=" text-align: center; width: 200%">RETURN VALUE (USD)</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.vat') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.mp_comm') }}</th>
        
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.courier_fee') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.parcel_company') }}</th>
        
        <th style=" text-align: center; width: 200%">SALES RETURN</th>
        <th style=" text-align: center; width: 200%">RECEIVED/REFUNDED</th>
    
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.total_fob') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.payment_status') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.customer') }}</th>
        <th style=" text-align: center; width: 200%">{{ __('messages.pdf.warehouse') }}</th>
    </tr>
    </thead>
    <tbody>
        @php
          $sum_grand_total = 0;
          $sum_selling_value_euro_total = 0;
          $sum_selling_value_usd_total = 0;
        @endphp
    @foreach($saleReturns as $key => $saleReturn)
        @php
            $sum_grand_total += (float)$saleReturn->grand_total;
            $conversionRate = $saleReturn->conversion_rate ?? (optional($saleReturn->sale)->conversion_rate ?? 1);
            $sellingValueEur = (float)($saleReturn->selling_value_eur ?? ($saleReturn->grand_total * $conversionRate));
            $sellingValueUsd = (float)($saleReturn->selling_value_usd ?? 0);
            if ($sellingValueUsd <= 0 && $sellingValueEur > 0) {
                $usdRate = (float) \App\Models\Currency::getEffectiveRate('USD', $saleReturn->date);
                $sellingValueUsd = $usdRate > 0 ? round($sellingValueEur / $usdRate, 2) : 0;
            }
            $sum_selling_value_euro_total += $sellingValueEur;
            $sum_selling_value_usd_total += $sellingValueUsd;

            $panStyles = $saleReturn->saleReturnItems
                ->map(function($item) {
                    return optional(optional($item->product)->productAbstract)->pan_style;
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

            $fobSum = $saleReturn->saleReturnItems->sum(function ($item) {
                return optional(optional($item->product)->productAbstract)->base_cost;
            });
            $item_name = $saleReturn->saleReturnItems->map(function($item) {
                return optional($item->product)->name;
            })->values()
            ->implode(', ');       
        @endphp
        <tr align="center" style="background-color: dodgerblue; border: 5px solid black; font-weight: bold;">
            <td style="text-align: center;">{{$key+1}}</td>
            <td style="text-align: center;">{{$saleReturn->country}}</td>
            <td style="text-align: center;">{{ $saleReturn->sale ? ucfirst(strtolower($saleReturn->sale->market_place)) : '' }}</td>
            <td style="text-align: center;">{{\Carbon\Carbon::parse($saleReturn->date)->format('d-m-Y')}}</td>
            <td style="text-align: center;">{{ $saleReturn->sale ? $saleReturn->sale->order_no : '' }}</td>
            <td style="text-align: center;">{{$panStyles}}</td>
            <td style="text-align: center;">{{$item_name}}</td>
            @if(count($saleReturn->saleReturnItems) > 0)
                <td style="text-align: center;">{{count($saleReturn->saleReturnItems)}}  </td>
            @else
                <td style="text-align: center;" >0</td>
            @endif

            <td style="text-align: center;">{{$saleReturn->currency}}</td>

            <td style="text-align: center;">{{ (float) str_replace(',', '', $saleReturn->grand_total) }}</td>
            <td style="text-align: center;">{{$conversionRate}}</td>
            <td style="text-align: center;">{{ number_format($sellingValueEur, 2) }}</td>
            <td style="text-align: center;">{{ number_format($sellingValueUsd, 2) }}</td>
            <td style="text-align: center;">{{$saleReturn->tax_amount}}</td>
            <td style="text-align: center;">{{optional($saleReturn->sale)->marketplace_commission}}</td>
            <td style="text-align: center;">{{$saleReturn->shipping}}</td>
            <td style="text-align: center;">
                @if($saleReturn->sale && $saleReturn->sale->shipment)
                    @if($saleReturn->sale->shipment->parcel_company_id == 1)
                        GLS
                    @elseif($saleReturn->sale->shipment->parcel_company_id == 2)
                        EXPEDICO
                    @elseif($saleReturn->sale->shipment->parcel_company_id == 3)
                        REDEX
                    @elseif($saleReturn->sale->shipment->parcel_company_id == 5)
                        PACKETA
                    @elseif($saleReturn->sale->shipment->parcel_company_id == 6)
                        PATHAO
                    @elseif($saleReturn->sale->shipment->parcel_company_id == 4)
                        PACTIC
                    @elseif($saleReturn->sale->shipment->parcel_company_id == 7)
                        FAN COURIER    
                    @else
                        NOT YET
                    @endif
                @else
                    NOT YET
                @endif
            </td>
            
            <td style="text-align: center;">YES</td>
            <td>{{number_format((float)$saleReturn->paid_amount, 2)}}</td>
            
            <td style="text-align: center;">{{$fobSum}}</td>
            <td>unpaid</td>
            <td style="text-align: center;">{{$saleReturn->customer->name}}</td>
            <td style="text-align: center;">{{$saleReturn->warehouse->name}}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr class="footer-row" style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="11" style="text-align: right; font-weight: bold;">TOTAL:</td>
            <td style="text-align: center; font-weight: bold;">€{{ number_format($sum_selling_value_euro_total, 2) }}</td>
            <td style="text-align: center; font-weight: bold;">${{ number_format($sum_selling_value_usd_total, 2) }}</td>
            <td colspan="10"></td>
        </tr>
    </tfoot>
</table>
</body>
</html>
