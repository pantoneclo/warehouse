import React from 'react';
import {
    calculateCartTotalAmount,
    calculateSubTotal,
    calculateCartTotalTaxAmount,
    taxAmountMultiply
} from '../../shared/calculation/calculation';
import { currencySymbolHendling, smartCurrencySymbolHendling, getFormattedMessage } from '../../shared/sharedMethod';
import { getCurrencySymbol } from '../../constants';

const ProductMainCalculation = (props) => {
    const { inputValues, updateProducts, frontSetting, allConfigData } = props;

    // Get currency symbol from country or fallback to global setting
    const currencySymbolToUse = inputValues?.currencySymbol ||
        getCurrencySymbol(inputValues?.currency) ||
        (frontSetting?.value?.currency_symbol);

    // Utility to safely parse numbers or fallback to 0
    const parseOrZero = (value) => {
        const num = parseFloat(value);
        return isNaN(num) ? 0 : num;
    };

    // Parse all input values safely
    const shipping = parseOrZero(inputValues.shipping);
    const cod = parseOrZero(inputValues.cod);
    const discount = parseOrZero(inputValues.discount);
    const taxRate = parseOrZero(inputValues.tax_rate);

    // Safe values to pass into calculation functions
    const safeInputValues = {
        ...inputValues,
        shipping,
        cod,
        discount,
        tax_rate: taxRate,
    };

    const subtotal = calculateSubTotal(updateProducts);
    const totalAmountAfterDiscount = subtotal + shipping + cod - discount;

    const taxCal = calculateCartTotalTaxAmount(updateProducts, safeInputValues);
    const allItemsTax = updateProducts.reduce((sum, item) => sum + parseFloat(taxAmountMultiply(item)), 0);

    return (
        <div className='col-xxl-5 col-lg-6 col-md-6 col-12 float-end'>
            <div className='card'>
                <div className='card-body pt-7 pb-2'>
                    <div className='table-responsive'>
                        <table className='table border'>
                            <tbody>
                                <tr>
                                    <td className='py-3'>{getFormattedMessage('purchase.input.order-tax.label')}</td>
                                    <td className='py-3'>
                                        {currencySymbolHendling(allConfigData, frontSetting?.value?.currency_symbol, taxCal)}
                                        {inputValues.order_tax_type === '2' ? "(Inc)" : "(Exc)"}
                                    </td>
                                </tr>
                                <tr>
                                    <td className='py-3'>{getFormattedMessage('purchase.order-item.table.tax.column.label')}</td>
                                    <td className='py-3'>
                                        {currencySymbolHendling(allConfigData, frontSetting?.value?.currency_symbol, allItemsTax)}
                                    </td>
                                </tr>
                                <tr>
                                    <td className='py-3'>{getFormattedMessage('purchase.order-item.table.discount.column.label')}</td>
                                    <td className='py-3'>
                                        {currencySymbolHendling(allConfigData, frontSetting?.value?.currency_symbol, discount)}
                                    </td>
                                </tr>
                                <tr>
                                    <td className='py-3'>{getFormattedMessage('purchase.input.shipping.label')}</td>
                                    <td className='py-3'>
                                        {currencySymbolHendling(allConfigData, currencySymbolToUse, shipping)}
                                    </td>
                                </tr>
                                <tr>
                                    <td className='py-3'>{getFormattedMessage('purchase.input.cod.label')}</td>
                                    <td className='py-3'>
                                        {currencySymbolHendling(allConfigData, currencySymbolToUse, cod)}
                                    </td>
                                </tr>
                                <tr>
                                    <td className='py-3 text-primary'>{getFormattedMessage('purchase.grant-total.label')}</td>
                                    <td className='py-3 text-primary'>
                                        {currencySymbolHendling(
                                            allConfigData,
                                            currencySymbolToUse,
                                            calculateCartTotalAmount(updateProducts, safeInputValues)
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductMainCalculation;
