import React, {useEffect, useState} from 'react';
import {connect} from 'react-redux';
import moment from 'moment';
import MasterLayout from '../MasterLayout';
// import {fetchUnits} from '../../store/action/unitsAction';
import ReactDataTable from '../../shared/table/ReactDataTable';
// import DeleteUnits from './DeleteUnits';
// import CreateUnits from './CreateUnits';
// import EditUnits from './EditUnits';
import TabTitle from '../../shared/tab-title/TabTitle';
import {getFormattedDate, getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { fetchVariants } from './../../store/action/variantAction';
import CreateVariant from './CreateVariant';
import DeleteVariant from './DeleteVariant';
import EditVariant from './EditVariant';

const Variants = (props) => {
    const {units,variants, fetchVariants,totalRecord, isLoading, allConfigData} = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [editModel, setEditModel] = useState(false);
    const [unit, setUnit] = useState();

    const handleClose = (item) => {
        setEditModel(!editModel);
        setUnit(item);
    };

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {

        fetchVariants(filter, true);
    };

    const itemsValue = variants.length >= 0 && variants.map(unit => {
        // const variantKeysValues = Object.entries(unit.attributes.variant);

        const variantValues =  Object.entries(unit.attributes.variant).map(([key, value]) => `${key}: ${value}`).join(', ') ;



        return (
            {
                date: getFormattedDate(unit.attributes.created_at, allConfigData && allConfigData),
                time: moment(unit.attributes.created_at).format('LT'),
                name: unit.attributes.name,
                variant: variantValues,
               
                id: unit.id
            }
        )
    });

    const columns = [
        {
            name: getFormattedMessage('globally.input.id.label'),
            selector: row => row.id,
            sortField: 'name',
            sortable: true,
        },

        {
            name: getFormattedMessage('variant.row.title'),
            sortField: 'variant',
            sortable: true,
            cell: row => {
                return (
                    row.variant  &&
                    <span className='badge bg-light-success'>
                        <span>{row.variant}</span>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('globally.react-table.column.created-date.label'),
            selector: row => row.date,
            sortField: 'created_at',
            sortable: true,
            cell: row => {
                return (
                    <span className='badge bg-light-info'>
                        <div className='mb-1'>{row.time}</div>
                        <div>{row.date}</div>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            cell: row => <ActionButton item={row} goToEditProduct={handleClose} isEditMode={true}
                                       onClickDeleteModel={onClickDeleteModel}/>
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('variant.title')}/>
            <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                            AddButton={<CreateVariant/>}
                            title={getFormattedMessage('unit.modal.input.base-unit.label')}
                            totalRows={totalRecord} isShowFilterField isUnitFilter/>
            <EditVariant handleClose={handleClose} show={editModel} unit={unit}/>
            <DeleteVariant onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                         onDelete={isDelete}/>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    console.log(state);
    const {units,variants, totalRecord, isLoading, allConfigData} = state;
    return {units, variants,totalRecord, isLoading, allConfigData}
};

export default connect(mapStateToProps, {fetchVariants})(Variants);

