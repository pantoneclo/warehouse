import React, {useEffect} from 'react';
import {connect} from 'react-redux';
import {useNavigate} from 'react-router-dom';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import {getFormattedMessage} from '../../shared/sharedMethod';
import {fetchAllBaseUnits} from "../../store/action/baseUnitsAction";
import {fetchAllWarehouses} from '../../store/action/warehouseAction';
import {addProductAbstract} from '../../store/action/productAbstractAction';

import ProductAbsctractForm from './ProductAbsctractForm';

const CreateProductAbstract = (props) => {
    const {addProductAbstract,fetchAllBaseUnits,base,warehouses} = props;
    const navigate = useNavigate();

    useEffect(() => {
        fetchAllWarehouses();
        fetchAllBaseUnits();

    }, []);

    const addProductAbstractData = (formValue) => {
        addProductAbstract(formValue, navigate);
    };

    return (
        <MasterLayout>
            <HeaderTitle title='Product Create' to='/app/product/abstracts'/>
            <ProductAbsctractForm singleProduct={null} addProductAbstractData={addProductAbstractData} baseUnits={base}  warehouses={warehouses}/>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {warehouses,base,totalRecord} = state;
    return {warehouses, base,totalRecord}


};

export default connect(mapStateToProps, {fetchAllBaseUnits,fetchAllWarehouses,addProductAbstract})(CreateProductAbstract);
