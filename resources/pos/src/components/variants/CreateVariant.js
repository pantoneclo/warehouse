import React, {useState} from 'react';
import {Button} from 'react-bootstrap-v5';
import {connect} from 'react-redux';
import {addUnit} from '../../store/action/unitsAction';
import {addVariant} from '../../store/action/variantAction';
import VariantForm from './VariantForm';
import {getFormattedMessage} from '../../shared/sharedMethod';
import { useNavigate } from 'react-router';

const CreateVariant = (props) => {
    const {addVariant} = props;
    const [show, setShow] = useState(false);
    const handleClose = () => setShow(!show);
    const navigate = useNavigate();

    const addVariantsData = (productValue) => {
        addVariant(productValue,navigate);
    };

    return (
        <div className='text-end w-sm-auto'>
            <Button variant='primary mb-lg-0 mb-md-0 mb-4' onClick={handleClose}>
                {getFormattedMessage('variant.create.title')}
            </Button>
            <VariantForm addVariantsData={addVariantsData} handleClose={handleClose} show={show}
                       title={getFormattedMessage('variant.create.title')}/>
        </div>

    )
};

export default connect(null, {addVariant})(CreateVariant);
