import React, {useEffect} from 'react';
import {Button} from 'react-bootstrap-v5';
import { useParams, useNavigate  } from 'react-router-dom'
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import CreateComboForm from './CreateComboForm';
import { fetchProducts, fetchComboProduct } from '../../store/action/InventoryAction';
import {connect} from 'react-redux';
import {addCombo} from "../../store/action/comboProductAction";

import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
const ComboEdit = (props) => {
    const {combos, fetchComboProduct, fetchProducts, addCombo} = props;
    const { id } = useParams();
    const navigate = useNavigate();
    
    useEffect(() => {
        fetchProducts();
        fetchComboProduct(id);  // Corrected: Only one useEffect to fetch both
    }, [fetchProducts, fetchComboProduct, id]);

 console.log('Edit Combo', combos);

 

    const addComboData = (formValue) => {
        addCombo(formValue, navigate);
    };

    return (
        <MasterLayout>
            <HeaderTitle title={getFormattedMessage('inventory.create.title')} to='/app/inventory'/>
            <CreateComboForm combos={combos} addComboData={addComboData}  id={id} />
        </MasterLayout>
    )
}

const mapStateToProps = (state) => {
    console.log("combo State",state)
    const {combos} = state;
    return {combos}
};

export default connect(mapStateToProps, {fetchComboProduct, fetchProducts, addCombo})(ComboEdit);
