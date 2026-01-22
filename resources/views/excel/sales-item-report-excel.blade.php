<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "//www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>Sales Item Report Export</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="10" style="margin-top: 40px;">
    <thead>
    <tr style="background-color: dodgerblue;">
        <th>Date</th>
        <th>Order ID</th>
        <th>PO Number</th>
        <th>SKU</th>
        <th>Product Name</th>
        <th>Country</th>
        <th>Market Place</th>
        <th>FOB (Cost)</th>
        <th>Product Price</th>
        <th>Selling Price (EUR)</th>
        <th>Quantity</th>
        <th>Total (EUR)</th>
        <th>Margin (EUR)</th>
        <th>Available Stock</th>
    </tr>
    </thead>
    <tbody>
    @foreach($reports as $report)
        <tr align="center">
            <td>{{ \Carbon\Carbon::parse($report->sale_date)->format('Y-m-d') }}</td>
            <td>{{ $report->reference_code }}</td>
            <td>{{ $report->po_no }}</td>
            <td>{{ $report->sku }}</td>
            <td>{{ $report->product_name }}</td>
            <td>{{ $report->country_name }}</td>
            <td>{{ $report->market_place }}</td>
            <td>{{ number_format($report->fob, 2) }}</td>
            <td>{{ number_format($report->product_price, 2) }}</td>
            <td>{{ number_format($report->selling_price, 2) }}</td>
            <td>{{ $report->quantity }}</td>
            <td>{{ number_format($report->total, 2) }}</td>
            <td>{{ number_format($report->margin, 2) }}</td>
            <td>{{ $report->available_stock }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
