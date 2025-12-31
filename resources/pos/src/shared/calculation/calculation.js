export const subTotalCount = (cartItem) => {
    const totalAmount = taxAmount(cartItem) + amountBeforeTax(cartItem);
    return Number(+totalAmount * cartItem.quantity).toFixed(2);
}

export const discountAmount = (cartItem) => {
    if (cartItem.discount_type === '1' || cartItem.discount_type === 1) {
        return ((+cartItem.fix_net_unit / 100) * +cartItem.discount_value);
    } else if (cartItem.discount_type === '2' || cartItem.discount_type === 2) {
        return +cartItem.discount_value;
    }
    return +cartItem.discount_amount.toFixed(2);
};

export const discountAmountMultiply = (cartItem) => {
    let discountMultiply = discountAmount(cartItem);
    return (+discountMultiply * cartItem.quantity).toFixed(2);
}

export const taxAmount = (cartItem) => {
    if (cartItem.tax_type === '2' || cartItem.tax_type === 2) {
        return ((+cartItem.fix_net_unit - discountAmount(cartItem)) * +cartItem.tax_value) / (100 + +cartItem.tax_value);
    } else if (cartItem.tax_type === '1' || cartItem.tax_type === 1) {
        return ((+cartItem.fix_net_unit - discountAmount(cartItem)) * +cartItem.tax_value) / 100;
    }

    const taxAmount = cartItem.tax_amount != null ? cartItem.tax_amount : 0;
    return +taxAmount.toFixed(2);
}

export const taxAmountMultiply = (cartItem) => {
    let taxMultiply = taxAmount(cartItem);
    return (+taxMultiply * cartItem.quantity).toFixed(2);
}

export const amountBeforeTax = (cartItem) => {
    let price = +cartItem.net_unit_price;
    const unitCost = +price - discountAmount(cartItem);
    const inclusiveTax = +unitCost - taxAmount(cartItem);
    let finalCalPrice = cartItem.tax_type === '1' || cartItem.tax_type === 1 ? +unitCost : +inclusiveTax;
    return +finalCalPrice.toFixed(2);
}

//Grand Total Calculation
// export const calculateCartTotalTaxAmount = (carts, inputValue) => {
//     if (!inputValue || !inputValue.tax_rate) return "0.00"; // Prevent errors
//
//     let taxValue = parseFloat(inputValue.tax_rate);
//     let totalAmountBeforeTax = 0;
//
//     carts.forEach(cartItem => {
//         totalAmountBeforeTax += parseFloat(cartItem.sub_total || 0);
//     });
//
//     let totalAmountAfterDiscount = totalAmountBeforeTax + Number(inputValue.shipping) + Number(inputValue.cod) - Number(inputValue.discount);
//
//     let totalTax = (totalAmountAfterDiscount * taxValue) / (100 + taxValue);
//
//     return totalTax.toFixed(2);
// };


export const calculateCartTotalTaxAmount = (carts, inputValue) => {
    if (!inputValue || !inputValue.tax_rate) return "0.00";

    let taxValue = parseFloat(inputValue.tax_rate) || 0;
    let shipping = parseFloat(inputValue.shipping) || 0;
    let cod = parseFloat(inputValue.cod) || 0;
    let discount = parseFloat(inputValue.discount) || 0;
    let taxType = inputValue.order_tax_type || '1'; // Default to Exclusive if missing

    let totalAmountBeforeTax = 0;

    carts.forEach(cartItem => {
        totalAmountBeforeTax += parseFloat(cartItem.sub_total || 0);
    });

    // Tax is calculated on (Subtotal + Shipping + COD - Discount)
    let totalAmountAfterDiscount = totalAmountBeforeTax + shipping + cod - discount;

    let totalTax = 0;
    if (taxType === '2' || taxType === 2) {
        // Inclusive: Tax = (Total * Rate) / (100 + Rate)
        totalTax = (totalAmountAfterDiscount * taxValue) / (100 + taxValue);
    } else {
        // Exclusive: Tax = (Total * Rate) / 100
        totalTax = (totalAmountAfterDiscount * taxValue) / 100;
    }

    return isNaN(totalTax) ? "0.00" : totalTax.toFixed(2);
};



export const calculateSubTotal = (carts) => {
    let subTotalAmount = 0;
    carts.forEach(cartItem => {
        subTotalAmount = subTotalAmount + Number(subTotalCount(cartItem))
    })
    return +subTotalAmount;
}

export const calculateCartTotalAmount = (carts, inputValue) => {
    let finalTotalAmount
    const value = inputValue && inputValue;
    let taxType = value.order_tax_type || '1';

    let totalAmountAfterDiscount = calculateSubTotal(carts) - value.discount
    let totalTax = 0;

    // Recalculate tax for accuracy (mirroring calculateCartTotalTaxAmount logic)
    // Note: totalAmountAfterDiscount here doesn't include shipping/cod yet, matching original structure?
    // Wait, original structure:
    // finalTotalAmount = totalAmountAfterDiscount + shipping + cod
    // But tax was ignored in final sum?

    // Correct logic:
    // Base = Subtotal + Shipping + COD - Discount
    let baseAmount = calculateSubTotal(carts) + Number(value.shipping) + Number(value.cod) - Number(value.discount);

    if (taxType === '2' || taxType === 2) {
        // Inclusive: Grand Total is just the Base Amount (Tax is inside)
        finalTotalAmount = baseAmount;
    } else {
        // Exclusive: Grand Total = Base Amount + Tax
        // Tax = (Base * Rate) / 100
        let taxCal = (baseAmount * value.tax_rate / 100);
        finalTotalAmount = baseAmount + taxCal;
    }

    return (parseFloat(finalTotalAmount).toFixed(2))
}
