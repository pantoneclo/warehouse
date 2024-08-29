import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { useParams } from 'react-router-dom'
import { fetchProductAbstract } from '../../store/action/productAbstractAction';
import ProductAbsctractForm from './ProductAbsctractForm';
import HeaderTitle from '../header/HeaderTitle';
import MasterLayout from '../MasterLayout';
import { productUnitDropdown } from '../../store/action/productUnitAction';
import { fetchAllunits } from '../../store/action/unitsAction';
import { getFormattedMessage, getFormattedOptions, ucwords } from '../../shared/sharedMethod';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { Filters, taxMethodOptions } from '../../constants';
import { fetchAllBaseUnits } from "../../store/action/baseUnitsAction";


const EditProductAbstract = (props) => {
    const { fetchProductAbstract, productAbstracts, fetchAllBaseUnits, base } = props;
    // console.log(productAbstracts, 'productAbstracts')
    const { id } = useParams();
    const [itemsValue,setItemsValue] = useState([]);


    const getSaleUnit = productAbstracts.length >= 1 && productAbstracts[0]?.attributes.sale_unit_name ? { label: productAbstracts[0]?.attributes.sale_unit_name.name, value: productAbstracts[0]?.attributes.sale_unit_name.id } : ''
    const getPurchaseUnit = productAbstracts.length >= 1 && productAbstracts[0]?.attributes.sale_unit_name ? { label: productAbstracts[0]?.attributes.purchase_unit_name.name, value: productAbstracts[0]?.attributes.purchase_unit_name.id } : ''

    useEffect(() => {

        const itemsValue = productAbstracts?.length >= 1 && productAbstracts.map(product => ({

            name: product?.attributes.name,
            pan_style: product?.attributes.pan_style,
            base_price: product?.attributes.base_price,
            base_cost: product?.attributes.base_cost,

            category: {
                value: product?.attributes.product_category_id,
                label: product?.attributes.product_category_name
            },
            brand: {
                value: product?.attributes.brand_id,
                label: product?.attributes.brand_name
            },
            attributes: Object.entries(product?.attributes.attributes).map(([key, value]) => ({ label: ucwords(key), value: key })),
            //reduce attribute_list such we can directly use it
            attribute_list: Object.entries(product?.attributes.attributes)
                .reduce((result, [key, value]) => {
                    result[key] = value.map((item) => ({ label: item, value: item }));
                    return result;
                }, {}),
            product_unit: Number(product?.attributes.product_unit),
            sale_unit: getSaleUnit,
            purchase_unit: getPurchaseUnit,
            order_tax: product?.attributes.order_tax,
            tax_type: taxMethodOptions
                .filter(item => item.id.toString() === product?.attributes.tax_type)
                .map(item => ({
                    value: item.id,
                    label: getFormattedMessage(item.name),
                }))[0],
            notes: product?.attributes.notes,
            images: product?.attributes.images,

            is_Edit: true,
            id: product.id,
            products: product?.attributes.products,
        }));

        if (itemsValue.length == 1 && itemsValue[0].id == id) {
            setItemsValue(itemsValue);
        }

    }, [productAbstracts]);

    console.log(getFormattedOptions(taxMethodOptions));

    useEffect(() => {
        fetchAllBaseUnits();
        fetchProductAbstract(id);
    }, []);

    const getProductUnit = itemsValue && base.filter((fill) => Number(fill?.id) === Number(itemsValue[0]?.product_unit))
    console.log(itemsValue, 'itemsValue')
    return (
        <MasterLayout>
            <TopProgressBar />
            <HeaderTitle title={getFormattedMessage('product.edit.title')} to='/app/product/abstracts' />
            {Array.isArray(itemsValue) && itemsValue.length >= 1 && <ProductAbsctractForm singleProduct={itemsValue} productUnit={getProductUnit} baseUnits={base} id={id} />}
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    console.log(state, 'state')


    const { base, productAbstracts } = state;
    // console.log(productAbstracts, 'statexx')
    return { base, productAbstracts }
};

export default connect(mapStateToProps, { fetchProductAbstract, fetchAllBaseUnits, productUnitDropdown, fetchAllunits })(EditProductAbstract);

