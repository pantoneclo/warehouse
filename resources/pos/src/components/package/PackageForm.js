import React, {useEffect, useState} from 'react';
import {useNavigate} from 'react-router-dom';
import {Form, InputGroup} from 'react-bootstrap-v5';
import moment from 'moment';
import {connect, useDispatch} from 'react-redux';
import {fetchProductsByWarehouse} from '../../store/action/productAction';
import {editSale} from '../../store/action/salesAction';
import ProductSearch from '../../shared/components/product-cart/search/ProductSearch';
import ProductRowTable from '../../shared/components/sales/ProductRowTable';
import {placeholderText, getFormattedMessage, decimalValidate, onFocusInput, getFormattedOptions} from '../../shared/sharedMethod';
import status from '../../shared/option-lists/status.json';
import paymentStatus from '../../shared/option-lists/paymentStatus.json'
import paymentType from '../../shared/option-lists/paymentType.json'
import ReactDatePicker from '../../shared/datepicker/ReactDatePicker';
// import ProductMainCalculation from './ProductMainCalculation';
import {calculateCartTotalAmount, calculateCartTotalTaxAmount} from '../../shared/calculation/calculation';
import {prepareSaleProductArray} from '../../shared/prepareArray/prepareSaleArray';
import ModelFooter from '../../shared/components/modelFooter';
import {addToast} from '../../store/action/toastAction';
import {paymentMethodOptions, salePaymentStatusOptions, saleStatusOptions, statusOptions, toastType} from '../../constants';
import {fetchFrontSetting} from '../../store/action/frontSettingAction';
import ReactSelect from '../../shared/select/reactSelect';
import PackageRowTable from './../../shared/components/package/PackageRowTable';
import {editPackage } from './../../store/action/packageAction';
import AdvanceSearch from '../../shared/components/product-cart/search/AdvanceSearch';
import {fetchAdvancedSearch} from '../../store/action/advancedSearchAction';

const PackageForm = (props) => {
    const {
        addPackageData,
        editSale,
        editPackage,
        advanceSearch,
        id,
        customers,
        warehouses,
        singleSale,
        customProducts,
        products,
        fetchProductsByWarehouse,
        fetchFrontSetting,
        frontSetting,
        packages,
        isQuotation, allConfigData
    } = props;

    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [updateProducts, setUpdateProducts] = useState([]);
    const [quantity, setQuantity] = useState(0);
    const [newCost, setNewCost] = useState('');
    const [newDiscount, setNewDiscount] = useState('');
    const [newTax, setNewTax] = useState('');
    const [subTotal, setSubTotal] = useState('');
    const [newSaleUnit, setNewSaleUnit] = useState('');
    const [isPaymentType,setIsPaymentType] = useState(false)

    const [saleValue, setSaleValue] = useState({

        warehouse_id:  singleSale ? singleSale.warehouse_id : '',
        notes: 'Length 60 x Width: 40 x Height 40'


    });
    const [errors, setErrors] = useState({


        warehouse_id: '',

    });

    useEffect(() => {
        setUpdateProducts(updateProducts)
    }, [updateProducts, quantity, newCost, newDiscount, newTax, subTotal, newSaleUnit])



    useEffect(() => {
        updateProducts.length >= 1 ? dispatch({type: 'DISABLE_OPTION', payload: true}) : dispatch({type: 'DISABLE_OPTION', payload: false})
    }, [updateProducts])

    useEffect(() => {
        fetchFrontSetting();
    }, []);

    useEffect(() => {

        if(singleSale ){
            setSaleValue({

                warehouse_id: singleSale ? singleSale.warehouse_id : '',

            })
        }
    }, [singleSale]);
    console.log(saleValue, "salvalue")

    useEffect(() => {
        if (singleSale) {
            setUpdateProducts(singleSale.package_data);
        }
    }, []);

    useEffect(()=>{
        saleValue.warehouse_id.value && fetchProductsByWarehouse(saleValue?.warehouse_id?.value)
    },[saleValue.warehouse_id.value])

    const handleValidation = () => {
        let error = {};
        let isValid = false;
        const qtyCart = updateProducts.filter((a) => a.quantity === 0);

          if (!saleValue.warehouse_id) {
            error['warehouse_id'] = getFormattedMessage('product.input.warehouse.validate.label');
        }
        else if (qtyCart.length > 0) {
            dispatch(addToast({text: getFormattedMessage('globally.product-quantity.validate.message'), type: toastType.ERROR}))
        } else if (updateProducts.length < 1) {
            dispatch(addToast({text: getFormattedMessage('purchase.product-list.validate.message'), type: toastType.ERROR}))
        }

        else {
            isValid = true;
        }
        setErrors(error);
        return isValid;
    };

    const onWarehouseChange = (obj) => {
        setSaleValue(inputs => ({...inputs, warehouse_id: obj}));
        setErrors('');
    };

    const onCustomerChange = (obj) => {
        setSaleValue(inputs => ({...inputs, customer_id: obj}));
        setErrors('');
    };

    const onChangeInput = (e) => {
        e.preventDefault();
        const {value} = e.target;
        // check if value includes a decimal point
        if (value.match(/\./g)) {
            const [, decimal] = value.split('.');
            // restrict value to only 2 decimal places
            if (decimal?.length > 2) {
                // do nothing
                return;
            }
        }
        setSaleValue(inputs => ({...inputs, [e.target.name]: value && value}));
    };

    const onNotesChangeInput = (e) => {
        e.preventDefault();
        setSaleValue(inputs => ({...inputs, notes : e.target.value}));
    };

    const onStatusChange = (obj) => {
        setSaleValue(inputs => ({...inputs, status_id: obj}));
    };

    const onPaymentStatusChange = (obj) => {
        setSaleValue(inputs => ({...inputs, payment_status: obj}));
        obj.value !== 2 ? setIsPaymentType(true) : setIsPaymentType(false)
        setSaleValue(input => ({...input, payment_type: {label: getFormattedMessage("payment-type.filter.cash.label"), value: 1}}))
    };

    const onPaymentTypeChange = (obj) => {
        setSaleValue(inputs => ({...inputs, payment_type: obj}));
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
            return {...previousState, date: date}
        });
        setErrors('');
    };


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

    const paymentMethodOption = getFormattedOptions(paymentMethodOptions)
    const paymentTypeDefaultValue = paymentMethodOption.map((option) => {
        return {
            value: option.id,
            label: option.name
        }
    })
// console.log(updateProducts, "updateProducts")
const generateUniqueCode = () => {

    const prefix = 'PKG';
    const timestamp = Date.now();


    const customCode = `${prefix}_${timestamp}`;

    return customCode;
  };


    // const prepareFormData = (prepareData) => {
    //     // console.log(prepareData, "prepareDataxxx")

    //     const formValue = {

    //         code :generateUniqueCode(),
    //         barcode_symbol:1,
    //         warehouse_id: prepareData.warehouse_id.value ? prepareData.warehouse_id.value : prepareData.warehouse_id,
    //         package_data: updateProducts,

    //     }
    //     // console.log(formValue, "prepareData")
    //     return formValue
    // };
    console.log(updateProducts, "updateProducts")
    const prepareFormData = (data) => {
        console.log(updateProducts,'data')
        const formData = new FormData();
        formData.append('code',generateUniqueCode());
        formData.append('notes',data.notes);
        formData.append('warehouse_id', data.warehouse_id.value ? data.warehouse_id.value : data.warehouse_id);
        // formData.append('package_data', updateProducts);
        updateProducts.forEach((product, index) => {
            formData.append(`package_data[${index}][product_id]`, product.product_id);
            formData.append(`package_data[${index}][variant_id]`, product.variant_id);
            formData.append(`package_data[${index}][quantity]`, product.quantity);
        });

        formData.append('barcode_symbol', 1);

        return formData;
    };
    const onSubmit = (event) => {
        // console.log(saleValue , "saleValue")
        event.preventDefault();
        const valid = handleValidation();
        if (valid) {
            if (singleSale ) {
                editPackage(id, prepareFormData(saleValue), navigate);
            } else {
                addPackageData(prepareFormData(saleValue));
                setSaleValue(saleValue);
            }
        }
    };

    const onBlurInput = (el) => {
        if (el.target.value === '') {
            if(el.target.name === "shipping"){
                setSaleValue({...saleValue, shipping: "0.00"});
            }
            if(el.target.name === "discount"){
                setSaleValue({...saleValue, discount: "0.00"});
            }
            if(el.target.name === "tax_rate"){
                setSaleValue({...saleValue, tax_rate: "0.00"});
            }
        }
    }

    return (
        <div className='card'>
            <div className='card-body'>
                {/*<Form>*/}
                    <div className='row'>

                        <div className='col-md-4 'style={{zIndex: '100',position:'relative'}} >
                            <ReactSelect name='warehouse_id' data={warehouses} onChange={onWarehouseChange}
                                         title={getFormattedMessage('warehouse.title')} errors={errors['warehouse_id']}
                                         defaultValue={saleValue.warehouse_id} value={saleValue.warehouse_id} addSearchItems={singleSale}
                                         isWarehouseDisable={true}
                                         placeholder={placeholderText('purchase.select.warehouse.placeholder.label')}/>
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
                                {getFormattedMessage('package.product.column.label')}:
                            </label>
                            <span className='required'/>
                            <PackageRowTable updateProducts={updateProducts} setUpdateProducts={setUpdateProducts}
                                             updatedQty={updatedQty} frontSetting={frontSetting}
                                             updateCost={updateCost} updateSubTotal={updateSubTotal}
                                             updateSaleUnit={updateSaleUnit}
                            />
                        </div>

                        <div className='mb-3'>
                            <label className='form-label'>
                                {getFormattedMessage('globally.input.notes.label')}: </label>
                            <textarea name='notes' className='form-control' value={saleValue?.notes}
                                          placeholder={placeholderText('globally.input.notes.placeholder.label')}
                                          onChange={(e) => onNotesChangeInput(e)}
                            />
                        </div>
                        <ModelFooter onEditRecord={singleSale} onSubmit={onSubmit} link='/app/packages'/>
                    </div>
                {/*</Form>*/}
            </div>
        </div>
    )
}

const mapStateToProps = (state) => {
    console.log(state , "state from package form")
    const {purchaseProducts, products,advanceSearch, frontSetting, allConfigData} = state;
    return {customProducts: prepareSaleProductArray(advanceSearch), purchaseProducts,advanceSearch, products, frontSetting, allConfigData}
}

export default connect(mapStateToProps, {editSale,editPackage, fetchProductsByWarehouse, fetchFrontSetting,fetchAdvancedSearch})(PackageForm)

