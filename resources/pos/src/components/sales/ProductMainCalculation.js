import React from 'react';
import {
    calculateCartTotalAmount,
    calculateSubTotal
} from '../../shared/calculation/calculation';
import { currencySymbolHendling, getFormattedMessage } from '../../shared/sharedMethod';

const ProductMainCalculation = (props) => {
    const { inputValues, updateProducts, frontSetting, allConfigData } = props;

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

    const taxCalRaw = (totalAmountAfterDiscount * taxRate) / (100 + taxRate);
    const taxCal = isNaN(taxCalRaw) ? 0 : taxCalRaw.toFixed(2);

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
                                    {currencySymbolHendling(allConfigData, frontSetting?.value?.currency_symbol, taxCal)} ({taxRate.toFixed(2)}%)
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
                                    {currencySymbolHendling(allConfigData, frontSetting?.value?.currency_symbol, shipping)}
                                </td>
                            </tr>
                            <tr>
                                <td className='py-3'>{getFormattedMessage('purchase.input.cod.label')}</td>
                                <td className='py-3'>
                                    {currencySymbolHendling(allConfigData, frontSetting?.value?.currency_symbol, cod)}
                                </td>
                            </tr>
                            <tr>
                                <td className='py-3 text-primary'>{getFormattedMessage('purchase.grant-total.label')}</td>
                                <td className='py-3 text-primary'>
                                    {currencySymbolHendling(
                                        allConfigData,
                                        frontSetting?.value?.currency_symbol,
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
