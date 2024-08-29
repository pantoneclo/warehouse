import React, {useEffect} from 'react';
import {Button} from 'react-bootstrap-v5';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import CreateInventoryForm from './CreateInventoryForm';
import { fetchProducts } from '../../store/action/InventoryAction';
import {connect} from 'react-redux';
import {useNavigate} from 'react-router-dom';
import {addInventory} from "../../store/action/InventoryAction";

import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
const CreateInventory = (props) => {
    const {products,fetchProducts, addInventory} = props;
    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);

    const navigate = useNavigate();

    const addInventoryData = (formValue) => {
        addInventory(formValue, navigate);
    };

    return (
        <MasterLayout>
            <HeaderTitle title={getFormattedMessage('inventory.create.title')} to='/app/inventory'/>
            <CreateInventoryForm products={products} addInventoryData={addInventoryData}/>
        </MasterLayout>
    )
}

const mapStateToProps = (state) => {
    const {products} = state;
    return {products}
};

export default connect(mapStateToProps, {fetchProducts, addInventory})(CreateInventory);
