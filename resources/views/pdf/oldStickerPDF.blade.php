<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Del</title>
    <style>
        * {
            font-family: DejaVu Sans, Arial, "Helvetica", Arial, "Liberation Sans", sans-serif;
            font-size: 15px;
        }

        table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding-left: 10px;
            font-size: 15px;
        }

        /*tr:nth-child(even) {*/
        /*    background-color: #dddddd;*/
        /*}*/

        .main-div {
            width: 672px;
            margin: 0 auto; /* Centers the main-div horizontally */
            text-align: center; /* Centers text inside the main-div */
            height: 480px;
            /*border: 2px solid #dddddd;*/
        }
        p {
            font-size: 15px;
        }
        .page-break {
            page-break-after: always;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .product-details {
            width: 100%;
            clear: both;
            margin-bottom: 20px;
        }
        .product-names,
        .product-images {
            float: left;
            width: 50%;
            text-align: left;
            /*margin-left: 100px;*/
        }

        .product-names p,
        .product-images img {
            margin: 5px 0;
        }

        .product-images img {
            height: 80px;
            object-position: center;
            object-fit: contain;
            margin-top: 10px;
            margin-left: 50px;
        }
    </style>

</head>
<body>
<div class="main-div" style="text-align: center;">
    @foreach($inventory as $item)
        @for($boxIndex = 1; $boxIndex <= $item->no_of_boxes; $boxIndex++)
            @php
                $uniqueBrand = [];
            @endphp

            @foreach($item->combo as $combo_item)
                @if(!in_array($combo_item->product->productAbstract->brand->name, $uniqueBrand))
                    <p class="mb-0" style="font-size: 17px;font-weight: bold;">{{ $combo_item->product->productAbstract->brand->name }}</p>
                    @php
                        $uniqueBrand[] = $combo_item->product->productAbstract->brand->name;
                    @endphp
                @endif
            @endforeach


            <div class="barcode text-center" style="text-align: center">
                <img src="{{ public_path($item->combo[0]->barcode_image) }}"
                     alt="Barcode" style="width:70%;object-fit: contain;object-position: center;margin-bottom: 5px;">
            </div>
            <div>
                {{ $item->combo[0] ? $item->combo[0]->sticker_id : '-' }}
            </div>
            <div>
            </div>

            <div style="margin-top:10px;">
                <table class="table">
                    <tbody>
                    <tr>
                        <td>ORDER NO</td>
                        <td>
                            {{ $item->combo[0] ? $item->combo[0]->style : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td>STYLE</td>
                        <td>
                            @php
                                $uniquePanStyles = [];
                            @endphp

                            @foreach($item->combo as $combo_item)
                                @if(!in_array($combo_item->product->name, $uniquePanStyles))
                                    {{ $combo_item->product->name }}
                                    @php
                                        $uniquePanStyles[] = $combo_item->product->name;
                                    @endphp
                                @endif
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td>NET WT</td>
                        <td>{{ $item->net_wt }} &nbsp; KGS</td>
                    </tr>
                    <tr>
                        <td>GROSS WT</td>
                        <td>{{ $item->gross_wt }} &nbsp; KGS</td>
                    </tr>
                    <tr>

                        <td>TOTAL QTY</td>
                        <td>
                            @php
                                $total_qty = 0;
                                $items_per_box = [];
                                foreach($item->combo as $combo_item){
                                    $items_per_box[] = $combo_item->item_per_box;
                                    $total_qty += $combo_item->item_per_box;
                                }
                            @endphp
                            {{ implode(' + ', $items_per_box) }} = {{ $total_qty }} {{ $item->sticker_meas_unit }}

                        </td>
                    </tr>
                    <tr>
                        <td>CARTON MEAS</td>
                        <td>{{ $item->carton_meas }}</td>
                    </tr>
                    <tr>
                        <td>CARTON NO</td>
                        <td>  &nbsp;	 &nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	&nbsp;	 OF  </td>
                    </tr>
                    </tbody>
                </table>

            </div>

            <div>
                <div class="product-details clearfix">
                    <div class="product-names">

                        @foreach($item->combo as $combo_item)
                            <p>{{ $combo_item->color }} 	&nbsp; - &nbsp; {{ $combo_item->size }} 	&nbsp; - &nbsp;{{ $combo_item->item_per_box }} &nbsp;	&nbsp; {{ $item->sticker_meas_unit }}</p>
                        @endforeach
                    </div>
                    <div class="product-images">
                        {{--                        @foreach($item->combo as $imgeItem)--}}
                        @if ($item->combo && $item->combo[0]->product->productAbstract)
                            @if (isset($item->combo[0]->product->productAbstract->image_url) && isset($item->combo[0]->product->productAbstract->image_url['imageUrls']) && is_array($item->combo[0]->product->productAbstract->image_url['imageUrls']))

                                {{--                                    @foreach ($imgeItem->product->productAbstract->image_url['imageUrls'] as $imageUrl)--}}
                                @php
                                    $cleanUrl = str_replace('https://whms.trueclassic.eu/', '', $item->combo[0]->product->productAbstract->image_url['imageUrls'][0]);
                                    $publicPath = public_path($cleanUrl);
                                @endphp
                                <img src="{{ $publicPath }}" alt="Product Image"
                                     style="height: 90px;object-position: center;object-fit: contain">
                                {{--                                    @endforeach--}}
                            @endif
                        @endif
                        {{--                        @endforeach--}}
                    </div>
                </div>
            </div>

            <div>
                <p style="text-align: center;font-size: 12px">MADE IN BANGLADESH</p>
            </div>

            @if ($boxIndex < $item->no_of_boxes)
                <div class="page-break"></div>
            @endif
        @endfor
        <div class="page-break"></div>
    @endforeach
</div>

</body>
</html>
