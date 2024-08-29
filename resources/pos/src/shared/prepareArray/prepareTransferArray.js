export const prepareTransferArray = (products ,isBarcode) => {
    let transferProductRowArray = [];
    products.forEach(product => {
        transferProductRowArray.push({
            name: product.attributes.name,
            code: product.attributes.code,
            pan_style: product.attributes.pan_style,
            barcode_url: product.attributes.barcode_url,
            variant: product.attributes.variant? product.attributes.variant : '',
            stock: product.attributes.stock ? product.attributes.stock.quantity : "",
            short_name: product.attributes.purchase_unit_name.short_name,
            product_unit: product.attributes.product_unit,
            product_id: product?.attributes?.product_id,
            product_cost: product.attributes.product_cost,
            net_unit_cost: product.attributes.product_cost,
            fix_net_unit: product.attributes.product_cost,
            tax_type: product.attributes.tax_type ? product.attributes.tax_type : 1,
            tax_value: product.attributes.order_tax ? product.attributes.order_tax : 0.00,
            tax_amount: 0.00,
            discount_type: '2',
            discount_value: 0.00,
            discount_amount: 0.00,
            purchase_unit: product.attributes.purchase_unit,
            quantity: product.attributes.package_quantity?product.attributes.package_quantity: isBarcode? 10 : 1,
            sub_total: 0.00,
            id: product?.attributes?.product_id,
            purchase_item_id: '',
            product_price: product.attributes.product_price,
            variant: product.attributes.variant? product.attributes.variant : '',
        })
    });
    return transferProductRowArray;
};
