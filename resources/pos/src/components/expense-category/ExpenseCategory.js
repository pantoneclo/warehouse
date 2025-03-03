import React, {useState} from 'react';
import MasterLayout from '../MasterLayout';
import {connect} from 'react-redux';
import ReactDataTable from '../../shared/table/ReactDataTable';
import DeleteExpenseCategory from './DeleteExpenseCategory';
import {fetchExpenseCategories} from '../../store/action/expenseCategoryAction';
import CreateExpenseCategory from './CreateExpenseCategory';
import EditExpenseCategory from './EditExpenseCategory';
import TabTitle from '../../shared/tab-title/TabTitle';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import usePermission from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
const ExpenseCategory = (props) => {
    const {fetchExpenseCategories, expenseCategories, totalRecord, isLoading} = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [editModel, setEditModel] = useState(false);
    const [expenseCategory, setExpenseCategory] = useState();

    const handleClose = (item) => {
        setEditModel(!editModel)
        setExpenseCategory(item);
    };

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchExpenseCategories(filter, true);
    };
    const edit_permission = usePermission(Permissions.EXPENSE_CATEGORY_EDIT);
    const delete_permission = usePermission(Permissions.EXPENSE_CATEGORY_DELETE);
    const create_permission = usePermission( Permissions.EXPENSE_CATEGORY_CREATE);
    console.log("expenseCategories",edit_permission,delete_permission,create_permission)
    const itemsValue = expenseCategories.length >= 0 && expenseCategories.map(expense => ({
        name: expense.attributes.name,
        id: expense.id,
        edit_permission: edit_permission,
        delete_permission: delete_permission,
    }));

    const columns = [
        {
            name: getFormattedMessage('globally.input.name.label'),
            selector: row => row.name,
            sortField: 'name',
            sortable: true,
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            cell: row => <ActionButton item={row} goToEditProduct={handleClose}  
             isEditMode={row.edit_permission}
            isDeleteMode={row.delete_permission} 
                                      onClickDeleteModel={onClickDeleteModel}/>
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('expense-categories.title')}/>
                <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                                AddButton={create_permission?<CreateExpenseCategory/>:null}
                                totalRows={totalRecord}/>
                <EditExpenseCategory handleClose={handleClose} show={editModel} expenseCategory={expenseCategory}/>
                <DeleteExpenseCategory onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                                       onDelete={isDelete}/>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {expenseCategories, totalRecord, isLoading} = state;
    return {expenseCategories, totalRecord, isLoading}
};

export default connect(mapStateToProps, {fetchExpenseCategories})(ExpenseCategory);

