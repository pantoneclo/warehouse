import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { connect, useDispatch, useSelector } from 'react-redux';
import MasterLayout from '../MasterLayout';
import TabTitle from '../../shared/tab-title/TabTitle';
import ReactDataTable from '../../shared/table/ReactDataTable';
import { fetchSales } from '../../store/action/salesAction';
import DeleteSale from './DeleteSale';
import { currencySymbolHendling, getFormattedDate, getFormattedMessage, placeholderText } from '../../shared/sharedMethod';
import { salePdfAction } from '../../store/action/salePdfAction';
import { saleInvoiceAction } from '../../store/action/salePdfAction';
import ActionDropDownButton from '../../shared/action-buttons/ActionDropDownButton';
import { fetchFrontSetting } from '../../store/action/frontSettingAction';
import ShowPayment from '../../shared/showPayment/ShowPayment';
import CreatePaymentModal from "./CreatePaymentModal";
import { fetchSalePayments } from "../../store/action/salePaymentAction";
import { callSaleApi } from "../../store/action/saleApiAction";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import usePermission from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
import {fetchCurrencies} from '../../store/action/currencyAction';
const Sales = (props) => {
    const {
        sales,
        fetchSales,
        totalRecord,
        isLoading,
        salePdfAction,
        saleInvoiceAction,
        fetchFrontSetting,
        currencies,
        fetchCurrencies,
        frontSetting,
        isCallSaleApi,
        allConfigData
    } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isShowPaymentModel, setIsShowPaymentModel] = useState(false);
    const [isCreatePaymentOpen, setIsCreatePaymentOpen] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [createPaymentItem, setCreatePaymentItem] = useState({})
    const { allSalePayments } = useSelector(state => state)
    const [tableArray, setTableArray] = useState([])
    useEffect(() => {
        fetchFrontSetting();
        fetchCurrencies();
    }, []);

    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol

    const onChange = (filter) => {
        fetchSales(filter, true);
    };

    //sale edit function
    const goToEdit = (item) => {
        const id = item.id;
        window.location.href = '#/app/sales/edit/' + id;
    };

    // delete sale function
    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };
    const dispatch = useDispatch()

    const onShowPaymentClick = (item) => {
        setIsShowPaymentModel(!isShowPaymentModel);
        setCreatePaymentItem(item)
        if (item) {
            dispatch(fetchSalePayments(item.id))
        }
    };

    const onCreatePaymentClick = (item) => {
        setIsCreatePaymentOpen(!isCreatePaymentOpen);
        setCreatePaymentItem(item)
        if (item) {
            dispatch(fetchSalePayments(item.id))
        }
    };

    //sale details function
    const goToDetailScreen = (ProductId) => {
        window.location.href = '#/app/sales/detail/' + ProductId;
    };

    //onClick pdf function
    const onPdfClick = (id) => {
        saleInvoiceAction(id);
        console.log("Invoice Download");
    };

    const onCreateSaleReturnClick = (item) => {
        const id = item.id;
        window.location.href = item.is_return === 1 ? '#/app/sales/return/edit/' + id : "#/app/sales/return/" + id;
    };
    const view_permission = usePermission(Permissions.SALE_VIEW);
    const edit_permission = usePermission(Permissions.SALE_EDIT);
    const delete_permission = usePermission(Permissions.SALE_DELETE);
    const create_permission = usePermission(Permissions.SALE_CREATE);
    const create_sale_return_permission = usePermission(Permissions.MANAGE_SALE_RETURN);
    const create_payment_permission = usePermission(Permissions.MANAGE_SALE_PAYMENT);


    const itemsValue = currencySymbol && sales.length >= 0 && sales.map(sale => ({
        date: getFormattedDate(sale.attributes.date, allConfigData && allConfigData),
        // date_for_payment: sale.attributes.date,
        time: moment(sale.attributes.created_at).format('LT'),
        reference_code: sale.attributes.reference_code,
        customer_name: sale.attributes.customer_name,
        warehouse_name: sale.attributes.warehouse_name,
        status: sale.attributes.status,
        payment_status: sale.attributes.payment_status,
        payment_type: sale.attributes.payment_type,
        grand_total: sale.attributes.grand_total,
        paid_amount: sale.attributes.paid_amount ? sale.attributes.paid_amount : 0.00.toFixed(2),
        id: sale.id,
        currency: currencySymbol,
        country:sale.attributes.country,
        is_return: sale.attributes.is_return,
        view_permission: view_permission,
        edit_permission: edit_permission,
        delete_permission: delete_permission,
        create_sale_return_permission:create_sale_return_permission,
        create_payment_permission:create_payment_permission,
        order_no:sale.attributes.order_no,
        market_place:sale.attributes.market_place,
    }));

    useEffect(() => {
        const grandTotalSum = () => {
            let x = 0;
            itemsValue.length && itemsValue.map((item) => {
                x = x + Number(item.grand_total);
                return x;
            });
            return x;
        }
        const paidTotalSum = (itemsValue) => {
            let x = 0;
            itemsValue.length && itemsValue.map((item) => {
                x = x + Number(item.paid_amount);
                return x;
            });
            return x;
        }
        if (sales.length) {
            const newObject = itemsValue.length && {
                date: "",
                time: "",
                reference_code: "Total",
                customer_name: "",
                warehouse_name: "",
                status: "",
                payment_status: "",
                payment_type: "",
                grand_total: grandTotalSum(itemsValue),
                paid_amount: paidTotalSum(itemsValue),
                id: "",
                currency: currencySymbol
            }
            const newItemValue = itemsValue.length && newObject && itemsValue.concat(newObject)
            const latestArray = newItemValue.map((item) => item)
            newItemValue.length && setTableArray(latestArray)
        }
    }, [sales])

    const columns = [
        {
            name: "Order NO",
            sortField: 'order_no',
            sortable: false,
            cell: row => {
                return (

                    <span className='badge bg-light-danger'>
                        <span>{row.order_no}</span>
                    </span>
                );
            }
        },
        {
            name: getFormattedMessage('customer.title'),
            selector: row => row.customer_name,
            sortField: 'customer_name',
            sortable: false,
        },
        {
            name: getFormattedMessage('globally.input.country.label'),
            sortField: 'country',
            selector: row => row.country,
            sortable: false,
        },
        {
            name: getFormattedMessage('marketplace.label'),
            sortField: 'market_place',
            selector: row => row.market_place,
            sortable: false,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paymentStatus.label'),
            sortField: 'payment_status',
            sortable: false,
            cell: row => {
                return (
                    row.payment_status === 1 &&
                    <span className='badge bg-light-success'>
                        <span>{getFormattedMessage("payment-status.filter.paid.label")}</span>
                    </span> ||
                    row.payment_status === 2 &&
                    <span className='badge bg-light-danger'>
                        <span>{getFormattedMessage("payment-status.filter.unpaid.label")}</span>
                    </span> ||
                    row.payment_status === 3 &&
                    <span className='badge bg-light-warning'>
                        {/*<span>{getFormattedMessage("payment-status.filter.unpaid.label")}</span>*/}
                        <span>{getFormattedMessage("payment-status.filter.partial.label")}</span>
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
            cell: row => {
                if (row.edit_permission || row.view_permission || row.delete_permission) {
                    return (
                        <ActionDropDownButton
                            item={row}
                            goToEditProduct={goToEdit}
                            onClickDeleteModel={onClickDeleteModel}
                            onPdfClick={onPdfClick}
                            title={getFormattedMessage('sale.title')}
                            isPaymentShow={true}
                            isCreatePayment={row.create_payment_permission}
                            isPdfIcon={true}
                            isEditMode={row.edit_permission}
                            isDeleteMode={row.delete_permission}
                            isViewIcon={row.view_permission}
                            isCreateSaleReturn={row.create_sale_return_permission}
                            goToDetailScreen={goToDetailScreen}
                            onShowPaymentClick={onShowPaymentClick}
                            onCreatePaymentClick={onCreatePaymentClick}
                            onCreateSaleReturnClick={onCreateSaleReturnClick}
                        />
                    );
                } else {
                    return <p className='badge text-warning'>{getFormattedMessage('no.permission.message')}</p>; // or any other fallback UI if none of the permissions are true
                }
            }
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
                    row.status === 2 &&
                    <span className='badge bg-primary'>
                        <span>{getFormattedMessage("status.filter.pending.label")}</span>
                    </span> ||
                    row.status === 1 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage("status.filter.received.label")}</span>
                    </span> ||
                    row.status === 3 &&
                    <span className='badge bg-light-warning'>
                        <span>{getFormattedMessage("status.filter.ordered.label")}</span>
                    </span>||
                    row.status === 4 &&
                    <span className='badge bg-light-info'>
                        <span>{getFormattedMessage("status.filter.ontheway.label")}</span>
                    </span>||
                    row.status === 5 &&
                    <span className='badge bg-light-success'>
                        <span>{getFormattedMessage("status.filter.delivered.label")}</span>
                    </span>||
                    row.status === 6 &&
                    <span className='badge bg-light-danger'>
                        <span>{getFormattedMessage("status.filter.cancelled.label")}</span>
                    </span>||

                   row.status === 7 &&
                   <span className='badge bg-light-danger'>
                        <span>{getFormattedMessage("status.filter.order_failed.label")}</span>
                    </span>
                    ||

                    row.status === 8 &&
                    <span className='badge bg-light-warning'>
                        <span>{getFormattedMessage("status.filter.returned.label")}</span>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('purchase.grant-total.label'),
            sortField: 'grand_total',
            cell: row => {
                return row.reference_code === "Total" ? <span
                    className="fw-bold fs-4">{currencySymbolHendling(allConfigData, row.currency, row.grand_total)}</span> :
                    <span>{currencySymbolHendling(allConfigData, row.currency, row.grand_total)}</span>
            },
            sortable: true,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paid.label'),
            sortField: 'paid_amount',
            cell: row => {
                return row.reference_code === "Total" ? <span
                    className="fw-bold fs-4">
                    {currencySymbolHendling(allConfigData, row.currency, row.paid_amount)}</span> :
                    <span>{currencySymbolHendling(allConfigData, row.currency, row.paid_amount)}</span>
            },
            sortable: true,
        },

        {
            name: getFormattedMessage('select.payment-type.label'),
            sortField: 'payment_type',
            sortable: false,
            cell: row => {
                return (
                    row.payment_type === 1 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('cash.label')}</span>
                    </span> ||
                    row.payment_type === 2 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('payment-type.filter.cheque.label')}</span>
                    </span> ||
                    row.payment_type === 3 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('payment-type.filter.bank-transfer.label')}</span>
                    </span> ||
                    row.payment_type === 4 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('payment-type.filter.other.label')}</span>
                    </span>||
                    row.payment_type === 5 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('payment-type.filter.cod.label')}</span>
                    </span>||
                    row.payment_type === 6 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('payment-type.filter.ssl.label')}</span>
                    </span>||
                    row.payment_type === 7 &&
                    <span className='badge bg-light-primary'>
                        <span>{getFormattedMessage('payment-type.filter.stripe.label')}</span>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('globally.react-table.column.created-date.label'),
            selector: row => row.date,
            sortField: 'date',
            sortable: true,
            cell: row => {
                return (
                    row.date && <span className='badge bg-light-info'>
                        <div className='mb-1'>{row.time}</div>
                        <div>{row.date}</div>
                    </span>
                )
            }
        },





    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('sales.title')} />
            <div className='sale_table'>
                <ReactDataTable
                columns={columns}
                items={tableArray}
                buttonImport={false}
                isExport={false}
                to='#/app/sales/create'
                ButtonValue={create_permission?getFormattedMessage('sale.create.title'):null}
                isShowPaymentModel={isShowPaymentModel}
                isCallSaleApi={isCallSaleApi}
                isShowDateRangeField={false}
                 onChange={onChange}
                totalRows={totalRecord}
                goToEdit={goToEdit}
                isLoading={isLoading}
                isShowFilterField={false}
                isPaymentStatus={false}
                isStatus={false}
                isPaymentType={false} />
            </div>
            <DeleteSale onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete} />
            <ShowPayment setIsShowPaymentModel={setIsShowPaymentModel} currencySymbol={currencySymbol}
                allSalePayments={allSalePayments} createPaymentItem={createPaymentItem}
                onShowPaymentClick={onShowPaymentClick} isShowPaymentModel={isShowPaymentModel} />
            <ShowPayment allConfigData={allConfigData} setIsShowPaymentModel={setIsShowPaymentModel} currencySymbol={currencySymbol} allSalePayments={allSalePayments} createPaymentItem={createPaymentItem} onShowPaymentClick={onShowPaymentClick} isShowPaymentModel={isShowPaymentModel} />
            <CreatePaymentModal setIsCreatePaymentOpen={setIsCreatePaymentOpen} onCreatePaymentClick={onCreatePaymentClick} isCreatePaymentOpen={isCreatePaymentOpen} createPaymentItem={createPaymentItem} />
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const { sales,currencies, totalRecord, isLoading, frontSetting, isCallSaleApi, allConfigData } = state;
    return { sales,currencies, totalRecord, isLoading, frontSetting, isCallSaleApi, allConfigData };
};

export default connect(mapStateToProps, { fetchSales, salePdfAction,saleInvoiceAction, fetchCurrencies, fetchFrontSetting })(Sales);
