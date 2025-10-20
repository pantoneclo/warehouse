import React, {useEffect, useState} from 'react';
import {InputGroup} from 'react-bootstrap-v5';
import {connect, useDispatch} from 'react-redux';
import Form from 'react-bootstrap/Form';
import {
    taxAmountMultiply, discountAmountMultiply, subTotalCount, amountBeforeTax
} from '../../calculation/calculation';
import PackageModal from './PackageModal';
import {productSalesDropdown} from '../../../store/action/productSaleUnitAction';
import {currencySymbolHendling, decimalValidate, getFormattedMessage, numValidate} from '../../sharedMethod';
import {getCurrencySymbol} from '../../../constants';
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faPencil, faTrash} from '@fortawesome/free-solid-svg-icons';
import {addToast} from "../../../store/action/toastAction";
import {toastType} from "../../../constants";

const PackageTableBody = (props) => {
    const {
        singleProduct,
        index,
        updateProducts,
        setUpdateProducts,
        productSales,
        productSalesDropdown,
        updateCost,
        updateDiscount,
        updateTax,
        updateSubTotal,
        updateSaleUnit, frontSetting, allConfigData, currencySymbol
    } = props;
    const [isShowModal, setIsShowModal] = useState(false);
    const [updateProductData, setUpdateProductData] = useState([]);
    const dispatch = useDispatch()

    // Get currency symbol - prioritize passed currencySymbol, fallback to global setting
    const currencySymbolToUse = currencySymbol || (frontSetting.value && frontSetting.value.currency_symbol);

    useEffect(() => {
        singleProduct.newItem !== '' && productSalesDropdown(singleProduct.product_unit)
    }, [updateProductData, singleProduct.sale_unit]);

    useEffect(() => {
        singleProduct.sub_total = Number(subTotalCount(singleProduct))
    }, [singleProduct.sub_total]);

    const onProductUpdateInCart = (item) => {
        setUpdateProductData(item);
    };

    const onDeleteCartItem = (id) => {
        const newProduct = updateProducts.filter((item) => item.id !== id);
        setUpdateProducts(newProduct);
    };

    // const totalQty = posAllProducts.filter((product) => product.id === singleProduct.id).map((product) => product.attributes.sold_qty);


    const handleIncrement = () => {
        singleProduct.isSaleReturn || singleProduct.isSaleReturnEdit ?  setUpdateProducts(updateProducts =>
                updateProducts.map((item) => {
                    if (item.id === singleProduct.id) {
                        if (item.quantity >= item.sold_quantity || item.quantity >= singleProduct.stock) {
                            dispatch(addToast({
                                text: (getFormattedMessage("sale-return.product-qty.validate.message")),
                                type: toastType.ERROR
                            }))
                            return item
                        } else {
                            return {...item, quantity: item.quantity++ + 1}
                        }
                    } else {
                        return item
                    }
                })
            )
            :
        setUpdateProducts(updateProducts => updateProducts.map(item => item.id === singleProduct.id && item.quantity < singleProduct.stock
            ? {...item, quantity: item.quantity++ + 1}
            : item,
        ))
    };

    const handleDecrement = () => {
        if (singleProduct.quantity - 1 > 0) {
            setUpdateProducts(updateProducts => updateProducts.map(item => item.id === singleProduct.id
                ? {...item, quantity: item.quantity --- 1}
                : item,
            ))
        }
    };

    const handleChange = (e) => {
        e.preventDefault();
        const {value} = e.target;

        if(parseInt(value, 10) > singleProduct.stock){

            return;
        }

        // check if value includes a decimal point
        if (value.match(/\./g)) {
            const [p, decimal] = value.split('.');
            // restrict value to only 2 decimal places
            if (decimal?.length > 2) {
                // do nothing
                return;
            }

            if(parseInt(p, 10) > singleProduct.stock){
                console.log("BIggg");
                return;
            }
        }

        singleProduct.isSaleReturn || singleProduct.isSaleReturnEdit ? setUpdateProducts(updateProducts =>
            updateProducts.map((item) => {
                if (item.id === singleProduct.id) {
                    if (item.sold_quantity < Number(e.target.value)) {
                        dispatch(addToast({
                            text: (getFormattedMessage("sale-return.product-qty.validate.message")),
                            type: toastType.ERROR
                        }))
                        return {...item, quantity: item.sold_quantity}
                    } else {
                        return {
                            ...item, quantity: Number(e.target.value)
                        }
                    }
                } else {
                    return item
                }
            })
        ) :    setUpdateProducts(updateProducts => updateProducts.map(item => item.id === singleProduct.id
            ? {...item, quantity: Number(value)}
            : item,
            ))
    };

    const onClickShowPackageModal = () => {
        setIsShowModal(true);
        productSalesDropdown(singleProduct.product_unit)
    };

    return (
        <>
        <tr key={index} className='align-middle text-nowrap'>
            <td>
                <h4 className='product-name'>{singleProduct.code}</h4>
                <span>
               {
                         singleProduct && singleProduct.variant
                            ? Object.entries(singleProduct.variant).map(([label, value]) => (
                                <div key={label}>
                                    {`${label}: ${Array.isArray(value) ? value.join(", ") : value}`}
                                </div>
                            ))
                            : ''
                    }
               </span>
                <div className='d-flex align-items-center'>
                    <span className='badge bg-light-success'>
                    <span>{singleProduct.name}</span>
                </span>
                    {singleProduct.isSaleReturn === true || singleProduct.isSaleReturnEdit === true ? null :
                        < span className = 'badge bg-light-primary p-1 ms-1' >
                        < FontAwesomeIcon icon={faPencil} onClick={(e) => onClickShowPackageModal(e)}
                        style={{cursor: 'pointer'}}/>
                        </span>}
                </div>
            </td>
            <td>{currencySymbolHendling(allConfigData, currencySymbolToUse, amountBeforeTax(singleProduct).toFixed(2))}</td>
            <td>{singleProduct.pan_style}</td>
            
            <td>
            {singleProduct.isEdit ?
                singleProduct.stock.length >= 1 ? singleProduct.stock.map((item) => {
                    return(
                        <span className='badge bg-light-warning'>
                        <span>{item.quantity}&nbsp;{singleProduct.short_name}</span>
                        </span>
                    )
                }) : singleProduct.stock === "" ? <span className='badge bg-light-warning'>
                    <span>{singleProduct.sold_quantity}&nbsp;{singleProduct.short_name}</span>
                </span> :null
                :
                singleProduct.stock >= 0 ? <span className='badge bg-light-warning'>
                    <span>{singleProduct.stock}&nbsp;{singleProduct.short_name}</span>
                </span>
                :null}
            </td>
            <td>
                <div className='custom-qty'>
                    <InputGroup className='flex-nowrap'>
                        <InputGroup.Text className='btn btn-primary btn-sm px-4 px-4 pt-2'
                                         onClick={(e) => handleDecrement(e)}>-</InputGroup.Text>
                        <Form.Control aria-label='Product Quantity'
                                      onKeyPress={(event) => decimalValidate(event)}
                                      className='text-center px-0 py-2 rounded-0 hide-arrow'
                                      value={singleProduct.quantity}
                                      type="number"
                                      step={0.01}
                                      min={0.00}
                                      max={5}
                                      onChange={(e) => handleChange(e)}
                        />
                        <InputGroup.Text className='btn btn-primary btn-sm px-4 px-4 pt-2'
                                         onClick={(e) => handleIncrement(e)}>+</InputGroup.Text>
                    </InputGroup>
                </div>
            </td>
            {/* <td>{currencySymbolHendling(allConfigData, frontSetting.value && frontSetting.value.currency_symbol, discountAmountMultiply(singleProduct))}</td>
            <td>{currencySymbolHendling(allConfigData, frontSetting.value && frontSetting.value.currency_symbol, taxAmountMultiply(singleProduct))}</td> */}
            <td>{currencySymbolHendling(allConfigData, frontSetting.value && frontSetting.value.currency_symbol, subTotalCount(singleProduct))}</td>
            {singleProduct.isSaleReturn || singleProduct.isSaleReturnEdit ? null : <td className='text-start'>
                <button className='btn px-2 text-danger fs-3'>
                    <FontAwesomeIcon icon={faTrash} onClick={() => onDeleteCartItem(singleProduct.id)}/>
                </button>
            </td>}
        </tr>
            {isShowModal && <PackageModal product={singleProduct} isShowModal={isShowModal}
                                          frontSetting={frontSetting} updateSubTotal={updateSubTotal}
                                          setIsShowModal={setIsShowModal} updateCost={updateCost}
                                          updateDiscount={updateDiscount} updateTax={updateTax}
                                          productSales={productSales} updateSaleUnit={updateSaleUnit}
                                          onProductUpdateInCart={onProductUpdateInCart}/>}
        </>
    )
};

const mapStateToProps = (state) => {
    const {productSales, allConfigData} = state;
    return {productSales, allConfigData};
};

export default connect(mapStateToProps, {productSalesDropdown})(PackageTableBody);
