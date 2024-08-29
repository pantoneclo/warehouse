import React, {useEffect} from 'react';
import {Button} from 'react-bootstrap-v5';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import CreateComboForm from './CreateComboForm';
import { fetchProducts } from '../../store/action/InventoryAction';
import {connect} from 'react-redux';
import {useNavigate} from 'react-router-dom';
import {addCombo} from "../../store/action/comboProductAction";

import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
const ComboCreate = (props) => {
    const {products,fetchProducts, addCombo} = props;
    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);

    const navigate = useNavigate();

    const addComboData = (formValue) => {
        addCombo(formValue, navigate);
    };

    return (
        <MasterLayout>
            <HeaderTitle title={getFormattedMessage('inventory.create.title')} to='/app/inventory'/>
            <CreateComboForm products={products} addComboData={addComboData}/>
        </MasterLayout>
    )
}

const mapStateToProps = (state) => {
    const {products} = state;
    return {products}
};

export default connect(mapStateToProps, {fetchProducts, addCombo})(ComboCreate);
