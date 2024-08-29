import React from 'react';
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faEye, faPenToSquare, faTrash,faPlus, faHome} from '@fortawesome/free-solid-svg-icons';
import {placeholderText} from '../sharedMethod';

const ActionButton = (props) => {
    const {goToEditProduct, item,isVariantAddIcon=false,warehouseAdd,isWarehouseAddIcon=false, onClickDeleteModel = true, isDeleteMode = true, isEditMode = true,goToVariantAddScreen, goToDetailScreen, isViewIcon = false} = props;
    return (
        <>
        {
            isWarehouseAddIcon?
                <button title={placeholderText('globally.warehouse.add.label')}
                        className='btn text-success px-2 fs-3 ps-0 border-0'
                        onClick={(e) => {
                            e.stopPropagation();
                            warehouseAdd(item);
                        }}>
                    <FontAwesomeIcon icon={faHome}/>
                </button> : null

        }
            { isVariantAddIcon?
                <button title={placeholderText('globally.variant.add.label')}
                        className='btn text-success px-2 fs-3 ps-0 border-0'
                        onClick={(e) => {
                            e.stopPropagation();
                            goToVariantAddScreen(item)
                        }}>
                    <FontAwesomeIcon icon={faPlus}/>
                </button> : null
            }
            { isViewIcon ?
                <button title={placeholderText('globally.view.tooltip.label')}
                        className='btn text-success px-2 fs-3 ps-0 border-0'
                        onClick={(e) => {
                            e.stopPropagation();
                            goToDetailScreen(item.id)
                        }}>
                    <FontAwesomeIcon icon={faEye}/>
                </button> : null
            }
            {item.name === 'super_admin' ||item.name==='admin' || item.email === 'superadmin@bitnix.ai' || isEditMode === false ? null :
                <button title={placeholderText('globally.edit.tooltip.label')}
                        className='btn text-primary fs-3 border-0 px-xxl-2 px-1'
                        onClick={(e) => {
                            e.stopPropagation();
                            goToEditProduct(item);
                        }}
                >
                    <FontAwesomeIcon icon={faPenToSquare}/>
                </button>
            }
            {item.name === 'super_admin' ||item.name==='admin' || item.email === 'superadmin@bitnix.ai' || isDeleteMode === false ? null :
                <button title={placeholderText('globally.delete.tooltip.label')}
                        className='btn px-2 pe-0 text-danger fs-3 border-0'
                        onClick={(e) => {
                            e.stopPropagation();
                            onClickDeleteModel(item);
                        }}
                >
                    <FontAwesomeIcon icon={faTrash}/>
                </button>
            }
        </>
    )
};
export default ActionButton;
