import React, {useEffect, useState} from 'react';
import moment from 'moment';
import {connect} from 'react-redux';
import MasterLayout from '../MasterLayout';
import TabTitle from '../../shared/tab-title/TabTitle';
import ReactDataTable from '../../shared/table/ReactDataTable';
import DeleteSaleReturn from './DeleteSaleReturn';
import {fetchSalesReturn} from '../../store/action/salesReturnAction';
import {currencySymbolHendling, getFormattedDate, getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import {fetchFrontSetting} from '../../store/action/frontSettingAction';
import ActionDropDownButton from '../../shared/action-buttons/ActionDropDownButton';
import {countryOptions, getCurrencySymbol} from '../../constants';
import {downloadSaleReturnPdf} from '../../store/action/downloadSaleReturnPdfAction';
import ShowPayment from '../../shared/showPayment/ShowPayment';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import  usePermission  from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
const SaleReturn = (props) => {
    const {
        salesReturn,
        fetchSalesReturn,
        totalRecord,
        isLoading,
        frontSetting,
        fetchFrontSetting,
        downloadSaleReturnPdf,
        allConfigData
    } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [isShowPaymentModel, setIsShowPaymentModel] = useState(false);

    useEffect(() => {
        fetchFrontSetting();
    }, []);

    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol

    const onShowPaymentClick = () => {
        setIsShowPaymentModel(!isShowPaymentModel);
    };

    const onChange = (filter) => {
        fetchSalesReturn(filter, true);
    };

    const goToEdit = (item) => {
        const id = item.id;
        window.location.href = '#/app/sale-return/edit/' + id;
    };

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onPdfClick = (id) => {
        downloadSaleReturnPdf(id);
    };

    const goToDetailScreen = (id) => {
        window.location.href = '#/app/sale-return/detail/' + id;
    };
    const view_permission = usePermission(Permissions.SALE_RETURN_VIEW);
    const edit_permission = usePermission(Permissions.SALE_RETURN_EDIT);
    const delete_permission = usePermission(Permissions.SALE_RETURN_DELETE);
    const create_sale_return_permission = usePermission(Permissions.MANAGE_SALE_RETURN);

    console.log ("salesReturn", view_permission, edit_permission, delete_permission, create_sale_return_permission)

    const itemsValue = currencySymbol && salesReturn.length >= 0 && salesReturn.map(sale => {
        // Get currency symbol from country mapping first, fallback to global currency symbol
        const country = countryOptions.find(country => country.code === sale.attributes?.country);
        const currencySymbolToUse = country?.currencySymbol || getCurrencySymbol(sale.attributes?.currency) || currencySymbol;

        return {
            created_at: getFormattedDate(sale.attributes.date, allConfigData && allConfigData),
            time: moment(sale.attributes.created_at).format('LT'),
            reference_code: sale.attributes.reference_code,
            customer_name: sale.attributes.customer_name,
            warehouse_name: sale.attributes.warehouse_name,
            status: sale.attributes.status,
            payment_status: sale.attributes.payment_status,
            grand_total: sale.attributes.grand_total,
            paid_amount: sale.attributes.paid_amount ? sale.attributes.paid_amount : 0.00.toFixed(2),
            id: sale.id,
            currency: currencySymbolToUse,
            country: sale.attributes.country,
            view_permission: view_permission,
            edit_permission: edit_permission,
            delete_permission: delete_permission,
        };
    });

    const columns = [
        {
            name: getFormattedMessage('dashboard.recentSales.reference.label'),
            sortField: 'reference_code',
            sortable: false,
            cell: row => {
                return <span className='badge bg-light-danger'>
                            <span>{row.reference_code}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('customer.title'),
            selector: row => row.customer_name,
            sortField: 'customer_name',
            sortable: false,
        },
        {
            name: getFormattedMessage('warehouse.title'),
            selector: row => row.warehouse_name,
            sortField: 'warehouse_name',
            sortable: false,
        },
        {
            name: getFormattedMessage('purchase.select.status.label'),
            sortField: 'status',
            sortable: false,
            cell: row => {
                return (
                    row.status === 1 &&
                    <span className='badge bg-light-success'>
                        <span>{getFormattedMessage("status.filter.received.label")}</span>
                    </span> ||
                    row.status === 2 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage("status.filter.pending.label")}</span>
                    </span> ||
                    row.status === 3 &&
                    <span className='badge bg-light-warning'>
                        <span>{getFormattedMessage("status.filter.ordered.label")}</span>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('purchase.grant-total.label'),
            selector: row => currencySymbolHendling(allConfigData ,row.currency, row.grand_total),
            sortField: 'grand_total',
            sortable: true,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paid.label'),
            selector: row => currencySymbolHendling(allConfigData ,row.currency, row.paid_amount),
            sortField: 'paid_amount',
            sortable: true,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paymentStatus.label'),
            selector: row => row.payment_status,
            sortField: 'payment_status',
            sortable: false,
            cell: row => {
                return (
                    <span className='badge bg-light-warning'>
                        <span>{getFormattedMessage("payment-status.filter.unpaid.label")}</span>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('globally.react-table.column.created-date.label'),
            selector: row => row.created_at,
            sortField: 'created_at',
            sortable: true,
            cell: row => {
                return (
                    <span className='badge bg-light-info'>
                        <div className='mb-1'>{row.time}</div>
                        <div>{row.created_at}</div>
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
            cell: row => <ActionDropDownButton item={row}  
            isEditMode={row.edit_permission}
            isPdfIcon={false}
            isDeleteMode={row.delete_permission}
            isViewIcon={row.view_permission}
            onClickDeleteModel={onClickDeleteModel} onPdfClick={onPdfClick}
            title={getFormattedMessage('sale-return.title')}
            goToDetailScreen={goToDetailScreen}
            onShowPaymentClick={onShowPaymentClick} goToEditProduct={goToEdit}
             // isPaymentShow={true}
            />
        }
    ]

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('sales-return.title')}/>
            <div className='sale_return_table'>
            <ReactDataTable columns={columns} items={itemsValue} to='#/app/sale-return/create' isShowDateRangeField
                            // ButtonValue={getFormattedMessage('sale-return.create.title')}
                            onChange={onChange} totalRows={totalRecord} goToEdit={goToEdit} isLoading={isLoading}
                            isShowFilterField isStatus/>
            </div>
            <DeleteSaleReturn onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel}
                              onDelete={isDelete}/>
            <ShowPayment onShowPaymentClick={onShowPaymentClick} isShowPaymentModel={isShowPaymentModel}/>
        </MasterLayout>
    )
}

const mapStateToProps = (state) => {
    const {salesReturn, totalRecord, isLoading, frontSetting, allConfigData} = state;
    return {salesReturn, totalRecord, isLoading, frontSetting, allConfigData};
}

export default connect(mapStateToProps, {fetchSalesReturn, fetchFrontSetting, downloadSaleReturnPdf})(SaleReturn);
