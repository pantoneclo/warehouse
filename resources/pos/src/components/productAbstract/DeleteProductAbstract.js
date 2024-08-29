import React from 'react';
import {connect} from 'react-redux';
import {deleteProductAbstract} from '../../store/action/productAbstractAction';
import DeleteModel from '../../shared/action-buttons/DeleteModel';
import {getFormattedMessage} from '../../shared/sharedMethod';

const DeleteProductAbstract = (props) => {
    const {deleteProductAbstract, onDelete, deleteModel, onClickDeleteModel} = props;

    const deleteUserClick = () => {
        deleteProductAbstract(onDelete.id);
        onClickDeleteModel(false);
    };

    return (
        <div>
            {deleteModel && <DeleteModel onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                                         deleteUserClick={deleteUserClick} name={getFormattedMessage('product.abstract.delete.title')}/>}
        </div>
    )
};

export default connect(null, {deleteProductAbstract})(DeleteProductAbstract);
