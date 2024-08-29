import React from 'react';
import {connect} from 'react-redux';
import {deleteUnit} from '../../store/action/unitsAction';
import DeleteModel from '../../shared/action-buttons/DeleteModel';
import {getFormattedMessage} from '../../shared/sharedMethod';
import { deleteVariant } from '../../store/action/variantAction';

const DeleteVariant = (props) => {
    const {deleteVariant, onDelete, deleteModel, onClickDeleteModel} = props;

    const deleteUserClick = () => {
        deleteVariant(onDelete.id);
        onClickDeleteModel(false);
    };

    return (
        <div>
            {deleteModel && <DeleteModel onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                                         deleteUserClick={deleteUserClick} name={getFormattedMessage('variant.title')}/>}
        </div>
    )
};

export default connect(null, {deleteVariant})(DeleteVariant);
