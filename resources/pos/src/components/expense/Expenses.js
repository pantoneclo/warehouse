import React, {useEffect, useState} from 'react';
import {connect} from 'react-redux';
import moment from 'moment';
import MasterLayout from '../MasterLayout';
import {useNavigate} from 'react-router-dom';
import ReactDataTable from '../../shared/table/ReactDataTable';
import {fetchExpenses} from '../../store/action/expenseAction';
import DeleteExpense from './DeleteExpense';
import TabTitle from '../../shared/tab-title/TabTitle';
import {currencySymbolHendling, getFormattedDate, getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import {fetchFrontSetting} from '../../store/action/frontSettingAction';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import usePermission from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
const Expenses = (props) => {
    const {fetchExpenses, expenses, totalRecord, isLoading, frontSetting, fetchFrontSetting, allConfigData} = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const navigate = useNavigate();

    useEffect(() => {
        fetchFrontSetting();
    }, []);

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchExpenses(filter, true);
    };

    const goToEditProduct = (item) => {
        const id = item.id;
        navigate(`/app/expenses/edit/${id}`);
    };

    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol


    const edit_permission = usePermission(Permissions.EXPENSE_EDIT);
    const delete_permission = usePermission(Permissions.EXPENSE_DELETE);
    const create_permission = usePermission( Permissions.MANAGE_EXPENSES_CATEGORIES);

    const itemsValue = currencySymbol && expenses.length >= 0 && expenses.map(expense => ({
        date: getFormattedDate(expense.attributes.date, allConfigData && allConfigData),
        time: moment(expense.attributes.created_at).format('LT'),
        reference_code: expense.attributes.reference_code,
        title: expense.attributes.title,
        warehouse_name: expense.attributes.warehouse_name,
        expense_category_name: expense.attributes.expense_category_name,
        amount: expense.attributes.amount,
        details: expense.attributes.details,
        id: expense.id,
        currency: currencySymbol,
      
        edit_permission: edit_permission,
        delete_permission: delete_permission,

    }));

    const columns = [
        {
            name: getFormattedMessage('dashboard.recentSales.reference.label'),
            sortField: 'reference_code',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-danger'>
                            <span>{row.reference_code}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('expense.input.title.label'),
            selector: row => row.title,
            sortField: 'title',
            sortable: false,
        },
        {
            name: getFormattedMessage('warehouse.title'),
            selector: row => row.warehouse_name,
            sortField: 'warehouse_name',
            sortable: false,
        },
        {
            name: getFormattedMessage('expense-category.title'),
            selector: row => row.expense_category_name,
            sortField: 'expense_category_name',
            sortable: false,
        },
        {
            name: getFormattedMessage('expense.input.amount.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.amount),
            sortField: 'amount',
            sortable: true,
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
            cell: row => <ActionButton
            item={row} 
            goToEditProduct={goToEditProduct}
            isEditMode={row.edit_permission}
            isDeleteMode={row.delete_permission} 
            onClickDeleteModel={onClickDeleteModel}/>
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar/>
            <TabTitle title={placeholderText('expenses.title')}/>
            <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                            ButtonValue={create_permission?getFormattedMessage('expense.create.title'):null} totalRows={totalRecord}
                            to='#/app/expenses/create'/>
            <DeleteExpense onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete}/>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {expenses, totalRecord, isLoading, frontSetting, allConfigData} = state;
    return {expenses, totalRecord, isLoading, frontSetting, allConfigData}
};

export default connect(mapStateToProps, {fetchExpenses, fetchFrontSetting})(Expenses);

