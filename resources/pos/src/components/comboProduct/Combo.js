import React, { useEffect, useState } from 'react';
import { connect, useSelector } from 'react-redux';
import moment from 'moment';
import { Button } from 'react-bootstrap-v5';
import MasterLayout from '../MasterLayout';
import { fetchCombos } from '../../store/action/comboProductAction';
import ReactDataTable from '../../shared/table/ReactDataTable';
import TabTitle from '../../shared/tab-title/TabTitle';
import { getFormattedDate, getFormattedMessage, placeholderText, currencySymbolHendling } from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import  {fetchFrontSetting}  from '../../store/action/frontSettingAction';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import usePermission from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
import DeleteCombo from './DeleteCombo';

import {allComboProductsExcelAction} from '../../store/action/productExcelAction.js';

const Combo = (props) => {
    const { combos, fetchCombos, totalRecord, isLoading, frontSetting, fetchFrontSetting, allConfigData, allComboProductsExcelAction } = props;

    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    useEffect(() => {
        let cancelToken =  axios.CancelToken.source();
        fetchFrontSetting();
        fetchCombos({},true,cancelToken);
         // Cleanup function to cancel the dispatch when the component is unmounted
         return () => {
            if (isLoading) {
                cancelToken.cancel('Request canceled manually');
            }
        };
        console.log("It's combo", combos)
    }, []);

    const onExcelClick = () => {
        allComboProductsExcelAction();
    };


    const view_permission = usePermission(Permissions.PRODUCT_VIEW);
    const edit_permission = usePermission(Permissions.PRODUCT_EDIT);
    const delete_permission = usePermission(Permissions.PRODUCT_DELETE);
    const create_permission = usePermission(Permissions.PRODUCT_CREATE);

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchCombos(filter, true);
    };

    const goToComboPage = (comboId) => {

        window.location.href = '#/app/combo-products/details/' + comboId;
    };


    const goToEditCombo = (item) => {
        const id = item.id;
        window.location.href = '#/app/combo-products/edit/' + id;
    };

console.log(combos, 'Combo Items ')
    const itemsValue = combos.length >= 0 && combos.map((combo) => {

        return (
            {
                id: combo?.id,
                name: combo?.attributes.name,
                warehouse_id: combo?.attributes?.products[0]?.warehouse_id,
                sku:  combo?.attributes.sku,
                date: getFormattedDate(combo?.attributes.created_at, allConfigData && allConfigData),
                time: moment(combo?.attributes.created_at).format('LT'),
                view_permission: view_permission,
                edit_permission: edit_permission,
                delete_permission: delete_permission,
            }
        )
    });
    console.log(itemsValue,'item values')
    const columns = [

        // {
        //     name: getFormattedMessage("id"),
        //     selector: row => <span>{row.id}</span>,
        //     className: 'package-id',
        //     sortField: 'id',
        //     sortable: true,
        // },
        {
            name: getFormattedMessage('combo.table.combo-name.column.label'),
            selector: row => <span className='product-name'>
                <span>{row.name}</span>
            </span>,
            sortField: 'name',
            sortable: true,
        },
        {
            name: getFormattedMessage("combo.table.sku.column.label"),
            selector: row => <span>{row.sku}</span>,
            sortField: 'sku',
            sortable: true,
        },
        {
            name: "Warehouse",
            selector: row => <span>{row.warehouse_id === 3 ? 'BD' : 'EU'}</span>,
            sortField: 'warehouse_id',
            sortable: true,
        },
        {
            name: getFormattedMessage('globally.react-table.column.created-date.label'),
            selector: row => row.date,
            sortField: 'created_at',
            sortable: false,
            cell: row => {
                return (
                    <span className='badge bg-light-info'>
                        <div className='mb-1'>{row.time}</div>
                        {row.date}
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
            width: '120px',

            cell: row => {

                return (
                    <ActionButton
                        isViewIcon={row.view_permission}
                        goToDetailScreen={goToComboPage}
                        isDeleteMode={row.delete_permission}
                        isEditMode={row.edit_permission}
                        item={row}
                        goToEditProduct={goToEditCombo}
                        onClickDeleteModel={onClickDeleteModel}
                    />
                );


            },
        }
    ];


    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('combo.title')} />
            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                isLoading={isLoading}

                ButtonValue={create_permission ? getFormattedMessage('combo.create.title') : null}
                totalRows={totalRecord}
                to='#/app/combo-products/create'
                isShowFilterField={false}
                isUnitFilter={false}
                isExport
                onExcelClick={onExcelClick}
                 />

           <DeleteCombo onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete} />
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    console.log(state)
    const { combos, totalRecord, isLoading, frontSetting, allConfigData } = state;
    return { combos, totalRecord, isLoading, frontSetting, allConfigData };
};

export default connect(mapStateToProps, { fetchCombos, fetchFrontSetting })(Combo);
