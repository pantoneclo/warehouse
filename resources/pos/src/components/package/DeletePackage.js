import React from 'react';
import {connect} from 'react-redux';
import DeleteModel from '../../shared/action-buttons/DeleteModel';
import {deleteSale} from '../../store/action/salesAction';
import {getFormattedMessage} from '../../shared/sharedMethod';
import { deletePackage } from './../../store/action/packageAction';

const DeletePackage = (props) => {
    const {deletePackage, onDelete, deleteModel, onClickDeleteModel} = props;

    const deleteSaleClick = () => {
        console.log(onDelete.id);
        deletePackage(onDelete.id);
        onClickDeleteModel(false);
    };

    return (
        <div>
            {deleteModel && <DeleteModel onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                                         deleteUserClick={deleteSaleClick} name={getFormattedMessage('packages.title')}/>}
        </div>
    )
};

export default connect(null, {deletePackage})(DeletePackage);
