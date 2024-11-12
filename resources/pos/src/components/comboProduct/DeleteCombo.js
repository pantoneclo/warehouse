import React from 'react';
import {connect} from 'react-redux';
import {deleteCombo} from '../../store/action/comboProductAction';
import DeleteModel from '../../shared/action-buttons/DeleteModel';
import {getFormattedMessage} from '../../shared/sharedMethod';

const DeleteCombo = (props) => {
    const {deleteCombo, onDelete, deleteModel, onClickDeleteModel} = props;

    const deleteUserClick = () => {
        deleteCombo(onDelete.id);
        onClickDeleteModel(false);
    };

    return (
        <div>
            {deleteModel && <DeleteModel onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                                         deleteUserClick={deleteUserClick} name={getFormattedMessage('combo.title')}/>}
        </div>
    )
};

export default connect(null, {deleteCombo})(DeleteCombo);
