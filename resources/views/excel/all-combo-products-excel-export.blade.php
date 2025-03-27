<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Combo Products Excel Export</title>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="10">
    <thead>
    <tr style="background-color: dodgerblue;">
        <th>{{ __('messages.pdf.combo_code') }}</th>
        <th>{{ __('messages.pdf.product') }}</th>
        <th>{{ __('messages.pdf.product_code') }}</th>
        <th>{{ __('messages.pdf.color') }}</th>
        <th>{{ __('messages.pdf.size') }}</th>
        <th>{{ __('messages.pdf.price') }}</th>

        @foreach($warehouses as $warehouse)
            <th>{{ $warehouse->name }}</th>
        @endforeach

        <th>{{ __('messages.pdf.total_stock') }}</th>
        <th>{{ __('messages.pdf.created_on') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($comboProducts as $combo)
        @php
            $product = $combo->product;
            $variant = null;

            if ($product && $product->variant) {
                $decodedVariant = json_decode($product->variant);
                $variant = $decodedVariant->variant ?? null;
            }

            $totalQuantity = 0;
        @endphp
        <tr align="center">
            <td>{{ $combo->code }}</td> <!-- Combo Code -->
            <td>{{ $product->name ?? '' }}</td> <!-- Product Name -->
            <td>{{ $product->code ?? '' }}</td> <!-- Product Code -->
            <td>{{ $variant->color ?? '' }}</td> <!-- Color -->
            <td>{{ $variant->size ?? '' }}</td> <!-- Size -->
            <td>{{ $product->product_price ?? '' }}</td> <!-- Price -->

            @foreach($warehouses as $warehouse)
                @php
                    $warehouseQuantity = \App\Models\ManageStock::where('product_id', $product->id ?? 0)
                        ->where('warehouse_id', $warehouse->id)
                        ->sum('quantity');
                    $totalQuantity += $warehouseQuantity;
                @endphp
                <td>{{ $warehouseQuantity }}</td>
            @endforeach

            <td>{{ $totalQuantity }}</td>
            <td>{{ \Carbon\Carbon::parse($product->created_at ?? now())->isoFormat('Do MMM, YYYY') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
