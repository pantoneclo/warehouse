import React from 'react';
import {connect} from 'react-redux';

import {getFormattedMessage} from '../../shared/sharedMethod';
import VariantForm from './VariantForm';

const EditVariant = (props) => {
    const {handleClose, show, unit} = props;

    return (
        <>
            {unit &&
            <VariantForm handleClose={handleClose} show={show} singleUnit={unit}
                       title={getFormattedMessage('variant.edit.title')}/>
            }
        </>
    )
};

export default connect(null)(EditVariant);

