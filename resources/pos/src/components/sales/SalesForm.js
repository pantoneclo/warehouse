import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Form, InputGroup } from 'react-bootstrap-v5';
import moment from 'moment';
import { connect, useDispatch } from 'react-redux';
import { fetchProductsByWarehouse } from '../../store/action/productAction';
import { editSale } from '../../store/action/salesAction';
import ProductSearch from '../../shared/components/product-cart/search/ProductSearch';
import ProductRowTable from '../../shared/components/sales/ProductRowTable';
import {
    placeholderText,
    getFormattedMessage,
    decimalValidate,
    onFocusInput,
    getFormattedOptions,
    numValidate
} from '../../shared/sharedMethod';
import status from '../../shared/option-lists/status.json';
import paymentStatus from '../../shared/option-lists/paymentStatus.json'
import paymentType from '../../shared/option-lists/paymentType.json'
import ReactDatePicker from '../../shared/datepicker/ReactDatePicker';
import ProductMainCalculation from './ProductMainCalculation';
import { calculateCartTotalAmount, calculateCartTotalTaxAmount } from '../../shared/calculation/calculation';
import { prepareSaleProductArray } from '../../shared/prepareArray/prepareSaleArray';
import ModelFooter from '../../shared/components/modelFooter';
import { addToast } from '../../store/action/toastAction';
import { paymentMethodOptions, salePaymentStatusOptions, saleStatusOptions, statusOptions, toastType, eccomercePlatform, countryOptions} from '../../constants';
import { fetchFrontSetting } from '../../store/action/frontSettingAction';
import ReactSelect from '../../shared/select/reactSelect';
import AdvanceSearch from '../../shared/components/product-cart/search/AdvanceSearch';
import { fetchAdvancedSearch } from '../../store/action/advancedSearchAction';
import { preparePurchaseProductArray } from '../../shared/prepareArray/preparePurchaseArray';
import { shippingCompanyNames ,getLabelById} from '../../constants'
import { get } from 'lodash';
import {fetchCurrencies} from "../../store/action/currencyAction";
import { useIntl } from 'react-intl';
const SalesForm = (props) => {
    const {
        addSaleData,
        editSale,
        id,
        customers,
        warehouses,
        singleSale,
        customProducts,
        products,
        advanceSearch,
        fetchProductsByWarehouse,
        fetchFrontSetting,
        currencies,
        fetchCurrencies,
        frontSetting,
        isQuotation, allConfigData
    } = props;
    const intl = useIntl();
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [updateProducts, setUpdateProducts] = useState([]);
    const [quantity, setQuantity] = useState(0);
    const [newCost, setNewCost] = useState('');
    const [newDiscount, setNewDiscount] = useState('');
    const [newTax, setNewTax] = useState('');
    const [subTotal, setSubTotal] = useState('');
    const [newSaleUnit, setNewSaleUnit] = useState('');
    const [isPaymentType, setIsPaymentType] = useState(false)

    const [uploadedFile, setUploadedFile] = useState(null);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => {
                setSaleValue(prev => ({ ...prev, file: reader.result })); // base64 string
            };
            reader.readAsDataURL(file);
        }
    };

    const [saleValue, setSaleValue] = useState({
        date: new Date(),
        warehouse_id: '',
        tax_rate: 0.00,
        tax_amount: 0.00,
        discount: 0.00,
        shipping: 0.00,
        cod: 0.00,
        grand_total: 0.00,
        notes: singleSale ? singleSale.notes : '',
        received_amount: 0,
        paid_amount: 0,
        status_id: { label: getFormattedMessage("status.filter.received.label"), value: 1 },
        payment_status: { label: getFormattedMessage("payment-status.filter.unpaid.label"), value: 2 },
        payment_type: { label: getFormattedMessage("payment-type.filter.cash.label"), value: 1 },
        parcel_number: '',
        parcel_company_id: '',
        shipment_id: '',
        order_no:'',
        country:'',
        market_place:'',
        currency:'',
        name:'',
        email:'',
        phone:'',
        address:'',
        city:'',
        file:null
    });
    const [errors, setErrors] = useState({
        date: '',
        warehouse_id: '',
        status_id: '',
        payment_status: '',
        payment_type: ''
    });

    useEffect(() => {
        setUpdateProducts(updateProducts)
    }, [updateProducts, quantity, newCost, newDiscount, newTax, subTotal, newSaleUnit])

    useEffect(() => {
        updateProducts.length >= 1 ? dispatch({ type: 'DISABLE_OPTION', payload: true }) : dispatch({ type: 'DISABLE_OPTION', payload: false })
    }, [updateProducts])

    useEffect(() => {
        fetchFrontSetting();
        fetchCurrencies();
    }, []);
console.log("Currencies", currencies);
    useEffect(() => {
        if (singleSale && !isQuotation) {
            setSaleValue({
                date: singleSale ? moment(singleSale.date).toDate() : '',
                quotation_id: singleSale ? singleSale.quotation_id : '',
                warehouse_id: singleSale ? singleSale.warehouse_id : '',
                tax_rate: singleSale.tax_rate ? singleSale.tax_rate.toFixed(2) : '0.00',
                tax_amount: singleSale.tax_amount ? singleSale.tax_amount.toFixed(2) : '0.00',
                discount: singleSale.discount ? singleSale.discount.toFixed(2) : '0.00',
                shipping: singleSale.shipping ? singleSale.shipping.toFixed(2) : '0.00',
                cod: singleSale.cod ? singleSale.cod.toFixed(2) : '0.00',
                grand_total: singleSale ? singleSale.grand_total : '0.00',
                status_id: singleSale ? singleSale.status_id : '',
                payment_status: singleSale.is_Partial === 3 ? { "label": getFormattedMessage('payment-status.filter.partial.label'), "value": 3 } : singleSale ? singleSale.payment_status : '',
                payment_type: singleSale ? singleSale.payment_type : '',
                parcel_number: singleSale ? singleSale.parcel_number : '',
                parcel_company_id: singleSale ? singleSale.parcel_company_id : '',
                shipment_id: singleSale ? singleSale.shipment_id : '',
                country:singleSale?singleSale.country:'',
                order_no:singleSale?singleSale.order_no:'',
                market_place:singleSale?singleSale.market_place:'',
                currency:singleSale?singleSale.currency:'',
                name:singleSale?singleSale.name:'',
                email:singleSale?singleSale.email:'',
                phone:singleSale?singleSale.phone:'',
                address:singleSale?singleSale.address:'',
                city:singleSale?singleSale.city:'',
                file:singleSale?singleSale.file:'',
            })
        }
        if (singleSale && isQuotation) {
            setSaleValue({
                date: singleSale ? moment(singleSale.date).toDate() : '',
                quotation_id: singleSale ? singleSale.quotation_id : '',
                warehouse_id: singleSale ? singleSale.warehouse_id : '',
                tax_rate: singleSale.tax_rate ? singleSale.tax_rate.toFixed(2) : '0.00',
                tax_amount: singleSale.tax_amount ? singleSale.tax_amount.toFixed(2) : '0.00',
                discount: singleSale.discount ? singleSale.discount.toFixed(2) : '0.00',
                shipping: singleSale.shipping ? singleSale.shipping.toFixed(2) : '0.00',
                cod: singleSale.cod ? singleSale.cod.toFixed(2) : '0.00',
                grand_total: singleSale ? singleSale.grand_total : '0.00',
                status_id: singleSale ? singleSale.status_id : '',
                payment_status: saleValue.payment_status ? saleValue.payment_status : '',
                payment_type: { label: getFormattedMessage("payment-type.filter.cash.label"), value: 1 },
                parcel_number: singleSale ? singleSale.parcel_number : '',
                parcel_company_id: singleSale ? singleSale.parcel_company_id : '',
                shipment_id: singleSale ? singleSale.shipment_id : '',
                country:singleSale?singleSale.country:'',
                order_no:singleSale?singleSale.order_no:'',
                market_place:singleSale?singleSale.market_place:'',
                currency:singleSale?singleSale.currency:'',
                name:singleSale?singleSale.name:'',
                email:singleSale?singleSale.email:'',
                phone:singleSale?singleSale.phone:'',
                address:singleSale?singleSale.address:'',
                city:singleSale?singleSale.city:'',
                file:singleSale?singleSale.file:'',
            })
        }
    }, [singleSale]);

    useEffect(() => {
        if (singleSale) {
            setUpdateProducts(singleSale.sale_items);
        }
    }, []);

    useEffect(() => {
        saleValue.warehouse_id.value && fetchProductsByWarehouse(saleValue?.warehouse_id?.value)
    }, [saleValue.warehouse_id.value])

    const handleValidation = () => {
        let error = {};
        let isValid = false;
        const qtyCart = updateProducts.filter((a) => a.quantity === 0);
        if (!saleValue.date) {
            error['date'] = getFormattedMessage('globally.date.validate.label');
        } else if (!saleValue.warehouse_id) {
            error['warehouse_id'] = getFormattedMessage('product.input.warehouse.validate.label');
        }  else if (qtyCart.length > 0) {
            dispatch(addToast({ text: getFormattedMessage('globally.product-quantity.validate.message'), type: toastType.ERROR }))
        } else if (updateProducts.length < 1) {
            dispatch(addToast({ text: getFormattedMessage('purchase.product-list.validate.message'), type: toastType.ERROR }))
        } else if (!saleValue.status_id) {
            error['status_id'] = getFormattedMessage("globally.status.validate.label")
        } else if (!saleValue.payment_status) {
            error['payment_status'] = getFormattedMessage("globally.payment.status.validate.label")
        } else if (!saleValue.payment_type) {
            error['payment_type'] = getFormattedMessage("globally.payment.type.validate.label")
        } else {
            isValid = true;
        }
        setErrors(error);
        return isValid;
    };

    const onWarehouseChange = (obj) => {
        setSaleValue(inputs => ({ ...inputs, warehouse_id: obj }));
        setErrors('');
    };


    const onParcelCompanyChange = (obj) => {
        setSaleValue(inputs => ({ ...inputs, parcel_company_id: obj }));
        setErrors('');

    };

    const onCurrencyChange = (obj) => {

        if(singleSale){
            singleSale.currency = obj
        }

        setSaleValue(inputs => ({ ...inputs, currency: obj }));
        setErrors('');
    };


    const onChangeInput = (e) => {
        e.preventDefault();
        const { value } = e.target;
        // check if value includes a decimal point
        if (value.match(/\./g)) {
            const [, decimal] = value.split('.');
            // restrict value to only 2 decimal places
            if (decimal?.length > 2) {
                // do nothing
                return;
            }
        }
        setSaleValue(inputs => ({ ...inputs, [e.target.name]: value && value }));
    };


    const onChangeText = (e) => {
        e.preventDefault();
        setSaleValue(inputs => ({ ...inputs, [e.target.name]: e.target.value }));
    };

    const onChangeEmail = (e) => {
        e.preventDefault();
        const { value } = e.target;
        setSaleValue(inputs => ({...inputs, [e.target.name]: value && value}))
    };

    const onNotesChangeInput = (e) => {
        e.preventDefault();
        setSaleValue(inputs => ({ ...inputs, notes: e.target.value }));
    };

    const onStatusChange = (obj) => {
        setSaleValue(inputs => ({ ...inputs, status_id: obj }));
    };

    const onPaymentStatusChange = (obj) => {
        setSaleValue(inputs => ({ ...inputs, payment_status: obj }));
        obj.value !== 2 ? setIsPaymentType(true) : setIsPaymentType(false)
        setSaleValue(input => ({ ...input, payment_type: { label: getFormattedMessage("payment-type.filter.cash.label"), value: 1 } }))
    };

    const onPaymentTypeChange = (obj) => {
        setSaleValue(inputs => ({ ...inputs, payment_type: obj }));
    };

    const updatedQty = (qty) => {
        setQuantity(qty);
    };

    const updateCost = (cost) => {
        setNewCost(cost);
    };

    const updateDiscount = (discount) => {
        setNewDiscount(discount);
    };

    const updateTax = (tax) => {
        setNewTax(tax);
    };

    const updateSubTotal = (subTotal) => {
        setSubTotal(subTotal);
    };

    const updateSaleUnit = (saleUnit) => {
        setNewSaleUnit(saleUnit);
    };

    const handleCallback = (date) => {
        setSaleValue(previousState => {
            return { ...previousState, date: date }
        });
        setErrors('');
    };
    const handleMarketplaceChange = (obj) => {
        setSaleValue(inputs => ({ ...inputs, market_place: obj }));
    };

    handleCountryChange

    const handleCountryChange = (obj) => {
        console.log("Selected Country Object Before Fix:", obj); // Debugging
        const fullCountry = countryNamesDefault.find(c => c.value === obj.value);
        console.log("Full Country Object After Fix:", fullCountry); // Debugging
        setSaleValue(inputs => ({
            ...inputs,
            country: fullCountry,
            tax_rate: fullCountry?.vat ?? 0,
            currency: fullCountry.currency
        }));

        onCurrencyChange(fullCountry.currency)
    };

    console.log('saleValue',saleValue)


    const statusFilterOptions = getFormattedOptions(saleStatusOptions)
    const statusDefaultValue = statusFilterOptions.map((option) => {
        return {
            value: option.id,
            label: option.name
        }
    })

    const paymentStatusFilterOptions = getFormattedOptions(salePaymentStatusOptions)
    const paymentStatusDefaultValue = paymentStatusFilterOptions.map((option) => {
        return {
            value: option.id,
            label: option.name
        }
    })
    const marketplaceFilterOption =getFormattedOptions(eccomercePlatform)
    console.log(marketplaceFilterOption, "Marketplace Options");
    const marketplaceNamesDefault = marketplaceFilterOption.map((option)=>{
        return {
            value:option.id,
            label:option.name
        }

    })
    console.log(marketplaceNamesDefault, "Marketplace NamesDefault Options");
    const countryFilterOption =getFormattedOptions(countryOptions, intl)
    console.log(countryFilterOption)
    const countryNamesDefault = countryFilterOption.map((option)=>{
        return {
            value:option.code,
            label:option.name,
            vat:option.vat,
            currency:option.currency
        }

    })
    console.log("Country Options:", countryNamesDefault);
    const paymentMethodOption = getFormattedOptions(paymentMethodOptions)
    const paymentTypeDefaultValue = paymentMethodOption.map((option) => {
        return {
            value: option.id,
            label: option.name
        }

    })

    const currencyNameDefault = currencies.map((option)=> {
        return{
            value: option.attributes.code,
            label:option.attributes.code
        }
    })

console.log("Defalut Currency Option", currencyNameDefault)

    const prepareFormData = (prepareData) => {
        const formValue = {

            date: moment(prepareData.date).toDate(),
            is_sale_created: "true",
            quotation_id: prepareData ? prepareData.quotation_id : '',
            warehouse_id: prepareData.warehouse_id.value ? prepareData.warehouse_id.value : prepareData.warehouse_id,
            discount: prepareData.discount,
            tax_rate: prepareData.tax_rate,
            tax_amount: calculateCartTotalTaxAmount(updateProducts, saleValue),
            sale_items: updateProducts,
            shipping: prepareData.shipping,
            cod: prepareData.cod,
            grand_total: calculateCartTotalAmount(updateProducts, saleValue),
            received_amount: 0,
            paid_amount: 0,
            note: prepareData.notes,
            status: prepareData.status_id.value ? prepareData.status_id.value : prepareData.status_id,
            payment_status: prepareData.payment_status.value ? prepareData.payment_status.value : prepareData.payment_status,
            payment_type: prepareData.payment_status.value === 2 ? 0 : prepareData.payment_type.value ? prepareData.payment_type.value : prepareData.payment_type,
            parcel_company_id: prepareData.parcel_company_id.value ? prepareData.parcel_company_id.value : prepareData.parcel_company_id,
            parcel_number: prepareData.parcel_number,
            shipment_id: prepareData.shipment_id ? prepareData.shipment_id : '',
            country:prepareData.country.value,
            order_no:prepareData.order_no,
            market_place:prepareData.market_place.label,
            currency: typeof prepareData.currency === 'object' ? prepareData.currency.value : prepareData.currency || '',
            name:prepareData.name,
            email:prepareData.email,
            phone:prepareData.phone,
            address:prepareData.address,
            city:prepareData.city,
            file:prepareData.file,
        }
        return formValue
    };

    const onSubmit = (event) => {
        event.preventDefault();
        const valid = handleValidation();
        if (valid) {
            if (singleSale && !isQuotation) {
                editSale(id, prepareFormData(saleValue), navigate);
            } else {

                addSaleData(prepareFormData(saleValue));
                setSaleValue(saleValue);
            }
        }
    };

    const onBlurInput = (el) => {
        if (el.target.value === '') {
            if (el.target.name === "shipping") {
                setSaleValue({ ...saleValue, shipping: "0.00" });
            }
            if (el.target.name === "discount") {
                setSaleValue({ ...saleValue, discount: "0.00" });
            }
            if (el.target.name === "tax_rate") {
                setSaleValue({ ...saleValue, tax_rate: "0.00" });
            }
        }
    }
    console.log(saleValue?.parcel_company_id, 'saleValue.parcel_company_id')
    const label = getLabelById(singleSale?.parcel_company_id);
    console.log(label, 'label')
    const parcel_company_id = { label: label, value: singleSale?.parcel_company_id }
    let currencyToFilterBy;
    if (singleSale) {
        // Update case - use the currency from singleSale
        console.log("Edit Currency Option", singleSale.currency);
        currencyToFilterBy = singleSale.currency;
    } else {
        // Create case - use the currency from saleValue.country
        currencyToFilterBy = saleValue.country?.currency;
    }
    const filtredCurrency = currencyNameDefault.find(
        currency => currency.value === currencyToFilterBy
    );

// Find the selected marketplace based on shipment_id
    const selectMarketplace = marketplaceNamesDefault.find(market=> market.label == saleValue.label)
    const selectedCountry = countryNamesDefault.find(c => c.value === saleValue.country);



console.log(saleValue.market_place, "saleValue Marketplace")

    return (
        <div className='card'>
            <div className='card-body'>
                {/*<Form>*/}
                <div className='row'>
                    <div className='col-md-4'>
                        <label className='form-label'>
                            {getFormattedMessage('react-data-table.date.column.label')}:
                        </label>
                        <span className='required'/>
                        <div className='position-relative'>
                            <ReactDatePicker onChangeDate={handleCallback} newStartDate={saleValue.date}/>
                        </div>
                        <span
                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['date'] ? errors['date'] : null}</span>
                    </div>
                    <div className='col-md-4' style={{zIndex: 500}}>
                        <ReactSelect name='warehouse_id' data={warehouses} onChange={onWarehouseChange}
                                     title={getFormattedMessage('warehouse.title')} errors={errors['warehouse_id']}
                                     defaultValue={saleValue.warehouse_id} value={saleValue.warehouse_id}
                                     addSearchItems={singleSale}
                                     isWarehouseDisable={true}
                                     placeholder={placeholderText('purchase.select.warehouse.placeholder.label')}/>
                    </div>


                    <div className='col-md-4 mb-5'>
                        <label className='form-label'>
                            {getFormattedMessage("globally.input.name.label")}:
                        </label>
                        <span className='required'/>
                        <input type='text' name='name' value={saleValue.name}
                               placeholder={placeholderText("globally.input.name.placeholder.label")}
                               className='form-control' autoFocus={true}
                               onChange={(e) => onChangeInput(e)}/>
                        <span
                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['name'] ? errors['name'] : null}</span>
                    </div>
                    <div className='col-md-4 mb-5'>
                        <label
                            className='form-label'>
                            {getFormattedMessage("globally.input.email.label")}:
                        </label>
                        <span className='required'/>
                        <input type='email' name='email' className='form-control'
                               placeholder={placeholderText("globally.input.email.placeholder.label")}
                               onChange={(e) => onChangeEmail(e)}
                               value={saleValue.email}/>
                        <span
                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['email'] ? errors['email'] : null}</span>
                    </div>

                    <div className='col-md-4 mb-5'>
                        <label
                            className='form-label'>
                            {getFormattedMessage("globally.input.phone-number.label")}:
                        </label>
                        <span className='required'/>
                        <input type='text' name='phone' className='form-control' pattern='[0-9]*'
                               placeholder={placeholderText("globally.input.phone-number.placeholder.label")}
                               onKeyPress={(event) => numValidate(event)}
                               onChange={(e) => onChangeInput(e)}
                               value={saleValue.phone}/>
                        <span
                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['phone'] ? errors['phone'] : null}</span>
                    </div>

                    <div className='col-md-4 mb-5'>
                        <label
                            className='form-label'>
                            {getFormattedMessage("globally.input.city.label")}:
                        </label>
                        <span className='required'/>
                        <input type='text' name='city' className='form-control'
                               placeholder={placeholderText("globally.input.city.placeholder.label")}
                               onChange={(e) => onChangeText(e)}
                               value={saleValue.city}/>
                        <span
                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['city'] ? errors['city'] : null}</span>
                    </div>

                    <div className='col-md-8 mb-3'>
                        <label
                            className='form-label'>
                            {getFormattedMessage("globally.input.address.label")}:
                        </label>
                        <span className='required'/>
                        <textarea type='text' rows="2" cols="50" name='address' className='form-control'
                                  placeholder={placeholderText("globally.input.address.placeholder.label")}
                                  onChange={(e) => onChangeText(e)}
                                  value={saleValue.address}/>
                        <span
                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['address'] ? errors['address'] : null}</span>
                    </div>

                    <div className='mb-5'>
                        <label className='form-label'>
                            {getFormattedMessage('product.title')}:
                        </label>
                        <AdvanceSearch
                            values={saleValue}
                            products={advanceSearch}
                            handleValidation={handleValidation}
                            updateProducts={updateProducts}
                            setUpdateProducts={setUpdateProducts}
                            customProducts={customProducts}
                            fetchAdvancedSearch={fetchAdvancedSearch}
                        />
                    </div>
                    <div>
                        <label className='form-label'>
                            {getFormattedMessage('purchase.order-item.table.label')}:
                        </label>
                        <span className='required'/>
                        <ProductRowTable updateProducts={updateProducts} setUpdateProducts={setUpdateProducts}
                                         updatedQty={updatedQty} frontSetting={frontSetting}
                                         updateCost={updateCost} updateDiscount={updateDiscount}
                                         updateTax={updateTax} updateSubTotal={updateSubTotal}
                                         updateSaleUnit={updateSaleUnit}
                        />
                    </div>
                    <div className='col-12'>
                        <ProductMainCalculation inputValues={saleValue} allConfigData={allConfigData}
                                                updateProducts={updateProducts} frontSetting={frontSetting}/>
                    </div>


                    <div className='col-md-4 mb-3'>
                        <label
                            className='form-label'>{getFormattedMessage('purchase.input.order-tax.label')}: </label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)'
                                   className='form-control'
                                   disabled={true}
                                   type='text' name='tax_rate' value={saleValue.tax_rate}
                                   onBlur={(event) => onBlurInput(event)} onFocus={(event) => onFocusInput(event)}
                                   onKeyPress={(event) => decimalValidate(event)}
                                   onChange={(e) => {
                                       onChangeInput(e)
                                   }}/>
                            <InputGroup.Text>%</InputGroup.Text>
                        </InputGroup>
                    </div>

                    <div className='col-md-4 mb-3'>
                        <Form.Label
                            className='form-label'>{getFormattedMessage('purchase.order-item.table.discount.column.label')}: </Form.Label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)'
                                   className='form-control'
                                   type='text' name='discount' value={saleValue.discount}
                                   onBlur={(event) => onBlurInput(event)} onFocus={(event) => onFocusInput(event)}
                                   onKeyPress={(event) => decimalValidate(event)}
                                   onChange={(e) => onChangeInput(e)}
                            />
                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                        </InputGroup>
                    </div>


                    <div className='col-md-4 mb-3'>
                        <label
                            className='form-label'>{getFormattedMessage('purchase.input.cod.label')}: </label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)' type='text'
                                   className='form-control'
                                   name='cod' value={saleValue.cod}
                                   onBlur={(event) => onBlurInput(event)} onFocus={(event) => onFocusInput(event)}
                                   onKeyPress={(event) => decimalValidate(event)}
                                   onChange={(e) => onChangeInput(e)}
                            />
                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                        </InputGroup>
                    </div>


                    <div className='col-md-4 mb-3'>
                        <label
                            className='form-label'>{getFormattedMessage('purchase.input.shipping.label')}: </label>
                        <InputGroup>
                            <input aria-label='Dollar amount (with dot and two decimal places)' type='text'
                                   className='form-control'
                                   name='shipping' value={saleValue.shipping}
                                   onBlur={(event) => onBlurInput(event)} onFocus={(event) => onFocusInput(event)}
                                   onKeyPress={(event) => decimalValidate(event)}
                                   onChange={(e) => onChangeInput(e)}
                            />
                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                        </InputGroup>
                    </div>
                    <div className='col-md-4'>
                        <ReactSelect multiLanguageOption={statusFilterOptions}
                                     onChange={onStatusChange} name='status_id'
                                     title={getFormattedMessage('purchase.select.status.label')}
                                     value={saleValue.status_id} errors={errors['status_id']}
                                     defaultValue={statusDefaultValue[0]}
                                     placeholder={getFormattedMessage('purchase.select.status.label')}/>
                    </div>
                    {/* parcel start */}


                    <div className='col-md-4'>

                        <ReactSelect title={getFormattedMessage('pacel.company.label.name')}
                                     data={shippingCompanyNames}
                                     onChange={onParcelCompanyChange}
                                     placeholder={placeholderText('pacel.company.select.label')}
                                     defaultValue={parcel_company_id}

                                     name='parcel_company_id'
                        />
                    </div>

                    <div className='col-md-4 mb-3'>
                        <label
                            className='form-label'>{getFormattedMessage('parcel.number.label')}: </label>
                        <InputGroup>
                            <input
                                type='text'
                                className='form-control'
                                name='parcel_number'
                                placeholder={placeholderText('parcel.number.placeholder.label')}

                                value={saleValue.parcel_number}
                                onChange={(e) => onChangeInput(e)}
                            />

                        </InputGroup>
                    </div>

                    <div className='col-md-4'>
                        <ReactSelect
                            data={countryNamesDefault}
                            onChange={handleCountryChange}
                            name='country'
                            title={getFormattedMessage('globally.input.country.label')}
                            value={selectedCountry}
                            // errors={errors['payment_status']}
                            defaultValue={countryNamesDefault[0]}
                            placeholder={placeholderText('globally.input.country.placeholder.label')}/>
                    </div>

                    <div className='col-md-4 mb-3'>
                        <label
                            className='form-label'>{getFormattedMessage('order.no')}: </label>
                        <InputGroup>
                            <input
                                type='text'
                                className='form-control'
                                name='order_no'
                                placeholder={placeholderText('globally.input.order.no.label')}

                                value={saleValue.order_no}
                                onChange={(e) => onChangeInput(e)}
                            />

                        </InputGroup>
                    </div>
                    <div className='col-md-4'>
                        <ReactSelect
                            multiLanguageOption={marketplaceFilterOption}
                            onChange={handleMarketplaceChange}
                            name='market_place'
                            title={getFormattedMessage('marketplace.label')}
                            value={saleValue.market_place}
                            // errors={errors['payment_status']}
                            defaultValue={marketplaceNamesDefault[0]}
                            placeholder={placeholderText('globally.input.marketplace.label')}/>
                    </div>


                    {/* parcel end */}
                    <br/>

                    <div className='col-md-4'>
                        <ReactSelect multiLanguageOption={paymentStatusFilterOptions} onChange={onPaymentStatusChange}
                                     name='payment_status'
                                     title={getFormattedMessage('dashboard.recentSales.paymentStatus.label')}
                                     value={saleValue.payment_status} errors={errors['payment_status']}
                                     defaultValue={paymentStatusDefaultValue[0]}
                                     placeholder={placeholderText('sale.select.payment-status.placeholder')}/>
                    </div>
                    <div className='col-md-4'>
                        <ReactSelect title={getFormattedMessage('select.payment-type.label')}
                                     name='payment_type'
                                     value={saleValue.payment_type} errors={errors['payment_type']}
                                     placeholder={placeholderText('sale.select.payment-type.placeholder')}
                                     defaultValue={paymentTypeDefaultValue[4]}
                                     multiLanguageOption={paymentMethodOption}
                                     onChange={onPaymentTypeChange}
                        />
                    </div>
                    <div className='col-md-4'>

                        <ReactSelect title={getFormattedMessage('currency.label.name')}
                                     data={currencyNameDefault}
                                     onChange={onCurrencyChange}
                                     placeholder={placeholderText('currency.select.label')}
                                     defaultValue={currencyNameDefault[0]}
                                     isCurrencyDisable={true}
                                     value={filtredCurrency}
                                     name='currency'

                        />
                    </div>
                    <div className='col-md-4'>
                        <label htmlFor="uploadFile" className='form-label'>Upload File</label>
                        <input
                            type="file"
                            id="uploadFile"
                            name="uploadFile"
                            className="form-control"
                            accept=".pdf,.ppt,.pptx,.doc,.docx,image/*"
                            onChange={handleFileChange}
                        />
                    </div>

                    <div className='mb-3'>
                        <label className='form-label'>
                            {getFormattedMessage('globally.input.notes.label')}: </label>
                        <textarea name='notes' className='form-control' value={saleValue.notes}
                                  placeholder={placeholderText('globally.input.notes.placeholder.label')}
                                  onChange={(e) => onNotesChangeInput(e)}
                        />
                    </div>
                    <ModelFooter onEditRecord={singleSale} onSubmit={onSubmit} link='/app/sales'/>
                </div>
                {/*</Form>*/}
            </div>
        </div>
    )
}

const mapStateToProps = (state) => {
    console.log(state, 'state from sales form')
    const {purchaseProducts, products, advanceSearch, currencies, frontSetting, allConfigData} = state;
    return {
        customProducts: preparePurchaseProductArray(advanceSearch),
        purchaseProducts,
        products,
        advanceSearch,
        currencies,
        frontSetting,
        allConfigData
    }
}

export default connect(mapStateToProps, {
    editSale,
    fetchCurrencies,
    fetchProductsByWarehouse,
    fetchFrontSetting,
    fetchAdvancedSearch
})(SalesForm)

