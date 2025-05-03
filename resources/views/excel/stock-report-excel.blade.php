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
        <th style="width: 250%">{{ __('messages.pdf.fob_price') }}</th>
        <th style="width: 250%">{{ __('messages.pdf.total_fob') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($stocks  as $stock)
        @php
            $product = null;
        // Check if the product has a variant and decode the JSON
        if ($stock->product->variant) {
            // Decode the JSON from the variant column
            $decodedVariant = json_decode($stock->product->variant);
            $product = $decodedVariant->variant ?? null; // Access the variant property
        }
        @endphp
        <tr align="center">
            <td>{{$stock->product->productAbstract->pan_style}}</td>
            <td>{{$stock->warehouse->name}}</td>
            <td>{{$stock->product->code}}</td>
            <td>{{$stock->product->name}}</td>
            <td style="text-align: center;">{{$product->color?? ''}}</td>
            <td style="text-align: center;">{{$product->size?? ''}}</td>
            <td>{{$stock->quantity}}</td>
            <td>{{$stock->product->productAbstract->base_cost}}</td>
            <td>{{$stock->product->productAbstract->base_cost * $stock->quantity}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
