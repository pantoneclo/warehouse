import React, {useEffect} from 'react';
import {Button} from 'react-bootstrap-v5';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import CreateComboForm from './CreateComboForm';
import { fetchAllProducts } from '../../store/action/productAction';
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

    console.log("All Product From ComboCreate", products);

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
