import React from 'react';
import {connect} from 'react-redux';
import {deleteSticker} from '../../store/action/InventoryAction';
import DeleteModel from '../../shared/action-buttons/DeleteModel';
import {getFormattedMessage} from '../../shared/sharedMethod';

const DeleteStickers = (props) => {
    const {deleteSticker, onDelete, deleteModel, onClickDeleteModel} = props;

    const deleteUserClick = () => {
        deleteSticker(onDelete.id);
        onClickDeleteModel(false);
    };

    return (
        <div>
            {deleteModel && <DeleteModel onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                                         deleteUserClick={deleteUserClick} title='Delete Sticker'
                                         name='Sticker'/>}
        </div>
    )
};

export default connect(null, {deleteSticker})(DeleteStickers);
