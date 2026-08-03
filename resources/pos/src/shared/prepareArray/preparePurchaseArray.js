export const preparePurchaseProductArray = (products, warehouseId, isBarcode) => {
    let purchaseProductRowArray = [];
    products.forEach(product => {
        const stockData = product.attributes.stocks?.find(stock => Number(stock.warehouse_id) === Number(warehouseId));
        const price = Number(product.attributes.product_price || product.attributes.product_cost || 0);
        const qty = product.attributes.package_quantity ? product.attributes.package_quantity : isBarcode ? 10 : 1;
        const subTotal = (price * qty).toFixed(2);
        const availableStock = stockData ? Number(stockData.quantity) : (product.attributes.stock ? Number(product.attributes.stock.quantity) : (product.attributes.in_stock ? Number(product.attributes.in_stock) : 0));

        purchaseProductRowArray.push({
            name: product.attributes.name,
            brand: product.attributes.brand_name,
            code: product.attributes.code,
            pan_style: product.attributes.pan_style,
            barcode_url: product.attributes.barcode_url,
            variant: product.attributes.variant ? product.attributes.variant : '',
            stock: availableStock,
            qty: availableStock,
            short_name: product.attributes.purchase_unit_name ? product.attributes.purchase_unit_name.short_name : (product.attributes.sale_unit_name ? product.attributes.sale_unit_name.short_name : 'PC'),
            product_unit: product.attributes.product_unit,
            product_id: product?.attributes?.product_id || product?.id,
            product_cost: product.attributes.product_cost,
            net_unit_cost: product.attributes.product_cost,
            fix_net_unit: price,
            tax_type: product.attributes.tax_type ? product.attributes.tax_type : 1,
            tax_value: product.attributes.order_tax ? product.attributes.order_tax : 0.00,
            tax_amount: 0.00,
            discount_type: '2',
            discount_value: 0.00,
            discount_amount: 0.00,
            purchase_unit: product.attributes.purchase_unit,
            sale_unit: product.attributes.sale_unit ? (product.attributes.sale_unit.id ? Number(product.attributes.sale_unit.id) : Number(product.attributes.sale_unit)) : 1,
            quantity: qty,
            sub_total: subTotal,
            id: product?.attributes?.product_id || product?.id,
            purchase_item_id: '',
            sale_item_id: '',
            product_price: price,
            net_unit_price: price,
        })
    });
    return purchaseProductRowArray;
};
