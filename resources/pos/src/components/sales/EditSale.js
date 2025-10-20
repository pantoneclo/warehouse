import React, {useEffect} from 'react';
import {connect} from 'react-redux';
import {useParams} from 'react-router-dom';
import SalesForm from './SalesForm';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import {editSale, fetchSale} from '../../store/action/salesAction';
import {fetchAllCustomer} from '../../store/action/customerAction';
import {fetchAllWarehouses} from '../../store/action/warehouseAction';
import {getFormattedMessage, getFormattedOptions} from '../../shared/sharedMethod';
import status from '../../shared/option-lists/status.json';
import paymentStatus from '../../shared/option-lists/paymentStatus.json';
import paymentType from '../../shared/option-lists/paymentType.json';
import Spinner from "../../shared/components/loaders/Spinner";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { saleStatusOptions ,eccomercePlatform, countryOptions, getCurrencySymbol } from '../../constants';
import { useIntl } from 'react-intl';
const EditSale = (props) => {
    const {fetchSale, sales, customers, fetchAllCustomer, warehouses, fetchAllWarehouses, isLoading} = props;
    const {id} = useParams();
    const intl = useIntl();
    useEffect(() => {
        fetchAllCustomer();
        fetchAllWarehouses();
        fetchSale(id);
    }, []);


    const statusFilterOptions = getFormattedOptions(saleStatusOptions)
    const marketplaceFilterOptions =getFormattedOptions(eccomercePlatform)
    console.log(marketplaceFilterOptions, "Edit Marketplace Options")
    const statusDefaultValue =  sales.attributes && sales.attributes.status && statusFilterOptions.filter((option) => option.id === sales.attributes.status)
    const selectedPayment = sales.attributes && sales.attributes.payment_status && paymentStatus.filter((item) => item.value === sales.attributes.payment_status)
    const selectedPaymentType = sales.attributes && sales.attributes.payment_type && paymentType.filter((item) => item.value === sales.attributes.payment_type)
    const test =sales.attributes?.market_place;
    console.log(test, 'Market Place Value')

    // Find the country from countryOptions based on the sale's country
    const saleCountryCode = sales.attributes?.country;
    const selectedCountryFromOptions = countryOptions.find(country => country.code === saleCountryCode);
    console.log("Sale Country Code:", saleCountryCode, "Selected Country:", selectedCountryFromOptions);
    const selectMarketPlace =
        sales.attributes?.market_place &&
        marketplaceFilterOptions.filter((option) =>
            option.name.toLowerCase() === test.toLowerCase()
        );

    console.log(selectMarketPlace, 'selected market place');

console.log(sales.attributes , 'this is from sales attributes')
    const itemsValue = sales && sales.attributes && {
        date: sales.attributes.date,
        warehouse_id: {
            value: sales.attributes.warehouse_id,
            label: sales.attributes.warehouse_name,
        },
        customer_id: {
            value: sales.attributes.customer_id,
            label: sales.attributes.customer_name,
        },
        name:sales.attributes.customer_name,
        phone:sales.attributes.customer_phone,
        email:sales.attributes.customer_email,
        city:sales.attributes.customer_city,
        address:sales.attributes.customer_address,
        tax_rate: sales.attributes.tax_rate,
        tax_amount: sales.attributes.tax_amount,
        discount: sales.attributes.discount,
        shipping: sales.attributes.shipping,
        grand_total: sales.attributes.grand_total,
        amount: sales.attributes.amount,
        cod: sales.attributes.cod,
        sale_items: sales.attributes.sale_items.map((item) => ({
            code: item.product && item.product.code,
            name: item.product && item.product.name,
            pan_style: item.product.product_abstract.pan_style,
            variant: item.product.variant.variant ,
            product_unit: item.product.product_unit,
            product_id: item.product_id,
            short_name: item.sale_unit && item.sale_unit.short_name && item.sale_unit.short_name,
            stock_alert:  item.product && item.product.stock_alert,
            product_price: item.product_price,
            fix_net_unit: item.product_price,
            net_unit_price: item.product_price,
            tax_type: item.tax_type,
            tax_value: item.tax_value,
            tax_amount: item.tax_amount,
            discount_type: item.discount_type,
            discount_value: item.discount_value,
            discount_amount: item.discount_amount,

            isEdit: true,
            stock: item.product && item.product.stocks.filter(item => item.warehouse_id === sales.attributes.warehouse_id),
            sub_total: item.sub_total,
            sale_unit: item.sale_unit && item.sale_unit.id && item.sale_unit.id,
            quantity: item.quantity,
            id: item.id,
            sale_item_id: item.id,
            newItem: '',
        })),
        id: sales.id,
        notes: sales.attributes.note,
        is_Partial : sales.attributes.payment_status,
        parcel_number: sales.attributes?.shipment?.parcel_number? sales.attributes?.shipment?.parcel_number : '',
        parcel_company_id: sales.attributes?.shipment?.parcel_company_id ? sales.attributes?.shipment?.parcel_company_id : '',
        country: selectedCountryFromOptions ? {
            value: selectedCountryFromOptions.code,
            label: selectedCountryFromOptions.name,
            vat: selectedCountryFromOptions.vat,
            currency: selectedCountryFromOptions.currency,
            currencySymbol: selectedCountryFromOptions.currencySymbol || getCurrencySymbol(selectedCountryFromOptions.currency)
        } : sales.attributes?.country,
        order_no:sales.attributes?.order_no? sales.attributes?.order_no : '',
        // market_place:sales.attributes?.market_place?sales.attributes?.market_place:'',


        shipment_id: sales.attributes?.shipment?.id ? sales.attributes?.shipment?.id : '',
        market_place :{

            label: selectMarketPlace && selectMarketPlace[0] && selectMarketPlace[0].name,
            value: selectMarketPlace && selectMarketPlace[0] && selectMarketPlace[0].id

        },
        payment_status: {
            label: selectedPayment && selectedPayment[0] && selectedPayment[0].label,
            value: selectedPayment && selectedPayment[0] && selectedPayment[0].value
        },
        payment_type: {
            label: selectedPaymentType && selectedPaymentType[0] && selectedPaymentType[0].label,
            value: selectedPaymentType && selectedPaymentType[0] && selectedPaymentType[0].value
        },
        status_id: {
            label: statusDefaultValue && statusDefaultValue[0] && statusDefaultValue[0].name,
            value: statusDefaultValue && statusDefaultValue[0] && statusDefaultValue[0].id
        },
        currency: selectedCountryFromOptions ? selectedCountryFromOptions.currency : sales.attributes?.currency,
        currencySymbol: selectedCountryFromOptions ? selectedCountryFromOptions.currencySymbol : getCurrencySymbol(sales.attributes?.currency)
    };
console.log(itemsValue,'itemsValue')
console.log("Final Currency Set:", selectedCountryFromOptions ? selectedCountryFromOptions.currency : sales.attributes?.currency);
    return (
        <MasterLayout>
            <TopProgressBar/>
            <HeaderTitle title={getFormattedMessage('sale.edit.title')} to='/app/sales'/>
            {itemsValue !== undefined  &&
                <SalesForm singleSale={itemsValue} id={id} customers={customers} warehouses={warehouses}/>}
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {sales, customers, warehouses, isLoading} = state;
    return {sales, customers, warehouses, isLoading}
};

export default connect(mapStateToProps, {fetchSale, editSale, fetchAllCustomer, fetchAllWarehouses})(EditSale);
