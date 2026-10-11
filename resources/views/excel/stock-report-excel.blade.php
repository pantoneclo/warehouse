<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title> Sale return report pdf</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.ico') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Fonts -->
    <!-- General CSS Files -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="10" style="margin-top: 40px;">
    <thead>
    <tr style="background-color: dodgerblue;">
        <th style="width: 200%">{{ __('messages.pdf.po') }}</th>
        <th style="width: 200%">{{ __('messages.pdf.warehouse') }}</th>
        <th style="width: 200%">{{ __('messages.pdf.code') }}</th>
        <th style="width: 300%">{{ __('messages.pdf.name') }}</th>
        <th style="text-align: center; width: 300%">{{ __('messages.pdf.color') }}</th>
        <th style=" text-align: center; width: 300%">{{ __('messages.pdf.size') }}</th>
        <th style="width: 250%">{{ __('messages.pdf.current_stock') }}</th>
        <th style="width: 250%">COST PRICE (EUR)</th>
        <th style="width: 250%">TOTAL COST (EUR)</th>
        <th style="width: 250%">TOTAL COST (USD)</th>
        <th style="width: 250%">SELLING PRICE (EUR)</th>
        <th style="width: 250%">TOTAL SELLING (EUR)</th>
        <th style="width: 250%">TOTAL SELLING (USD)</th>
    </tr>
    </thead>
    <tbody>
    @php
        $usdRate = (float) (\App\Models\Currency::where('code', 'USD')->value('conversion_rate') ?? 0.92);
        $grandTotalQty = 0;
        $grandTotalCostEur = 0;
        $grandTotalCostUsd = 0;
        $grandTotalSellingEur = 0;
        $grandTotalSellingUsd = 0;
    @endphp
    @foreach($stocks as $stock)
        @php
            $product = null;
            if ($stock->product->variant) {
                $decodedVariant = json_decode($stock->product->variant);
                $product = $decodedVariant->variant ?? null;
            }
            $costPrice = (float) ($stock->product->productAbstract->base_cost ?? $stock->product->product_cost ?? 0);
            $sellingPrice = (float) ($stock->product->product_price ?? 0);
            $qty = (int) $stock->quantity;

            $totalCostEur = $costPrice * $qty;
            $totalCostUsd = $usdRate > 0 ? round($totalCostEur / $usdRate, 2) : 0;
            $totalSellingEur = $sellingPrice * $qty;
            $totalSellingUsd = $usdRate > 0 ? round($totalSellingEur / $usdRate, 2) : 0;

            $grandTotalQty += $qty;
            $grandTotalCostEur += $totalCostEur;
            $grandTotalCostUsd += $totalCostUsd;
            $grandTotalSellingEur += $totalSellingEur;
            $grandTotalSellingUsd += $totalSellingUsd;
        @endphp
        <tr align="center">
            <td>{{$stock->product->productAbstract->pan_style ?? ''}}</td>
            <td>{{$stock->warehouse->name ?? ''}}</td>
            <td>{{$stock->product->code ?? ''}}</td>
            <td>{{$stock->product->name ?? ''}}</td>
            <td style="text-align: center;">{{$product->color ?? ''}}</td>
            <td style="text-align: center;">{{$product->size ?? ''}}</td>
            <td>{{$qty}}</td>
            <td>{{number_format($costPrice, 2)}}</td>
            <td>{{number_format($totalCostEur, 2)}}</td>
            <td>{{number_format($totalCostUsd, 2)}}</td>
            <td>{{number_format($sellingPrice, 2)}}</td>
            <td>{{number_format($totalSellingEur, 2)}}</td>
            <td>{{number_format($totalSellingUsd, 2)}}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background-color: #f2f2f2; font-weight: bold;">
            <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL:</td>
            <td>{{$grandTotalQty}}</td>
            <td>-</td>
            <td>€{{number_format($grandTotalCostEur, 2)}}</td>
            <td>${{number_format($grandTotalCostUsd, 2)}}</td>
            <td>-</td>
            <td>€{{number_format($grandTotalSellingEur, 2)}}</td>
            <td>${{number_format($grandTotalSellingUsd, 2)}}</td>
        </tr>
    </tfoot>
</table>
</body>
</html>
