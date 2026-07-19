import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import MasterLayout from '../../MasterLayout';
import TabTitle from '../../../shared/tab-title/TabTitle';
import {
    faPenToSquare
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { currencySymbolHendling, getFormattedMessage, placeholderText } from '../../../shared/sharedMethod';
import ReactDataTable from '../../../shared/table/ReactDataTable';
import { fetchFrontSetting } from '../../../store/action/frontSettingAction';
import { fetchSalesReturn } from '../../../store/action/salesReturnAction';
import { totalSaleReturnReportExcel } from '../../../store/action/totalSaleReturnReportExcel';
import { fetchCurrencies } from '../../../store/action/currencyAction';
import TopProgressBar from "../../../shared/components/loaders/TopProgressBar";
import EditSaleReturnReportModal from './EditSaleReturnReportModal';

const SaleReturnReport = (props) => {
    const {
        isLoading,
        totalRecord,
        fetchFrontSetting,
        fetchSalesReturn,
        salesReturn,
        frontSetting,
        dates,
        totalSaleReturnReportExcel,
        allConfigData,
        currencies,
        fetchCurrencies
    } = props;
    const [isEditSaleReportOpen, setIsEditSaleReportOpen] = useState(false);
    const [selectedSaleReportItem, setSelectedSaleReportItem] = useState(null);
    const [isWarehouseValue, setIsWarehouseValue] = useState(false);
    const filterDatesRef = useRef({ start_date: null, end_date: null });
    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol

    useEffect(() => {
        fetchFrontSetting();
    }, []);

    useEffect(() => {
        fetchSalesReturn();
    }, [fetchSalesReturn]);

    useEffect(() => {
        fetchCurrencies();
    }, [fetchCurrencies])

    useEffect(() => {
        if (isWarehouseValue === true) {
            totalSaleReturnReportExcel(filterDatesRef.current, setIsWarehouseValue);
        }
    }, [isWarehouseValue])

    const handleEditSaleReportClick = (item) => {
        setSelectedSaleReportItem(item);
        setIsEditSaleReportOpen(true);
    };

    const handleCloseModal = () => {
        setIsEditSaleReportOpen(false);
        setSelectedSaleReportItem(null);
    };

    const itemsValue = currencySymbol && salesReturn.length > 0 ? salesReturn.map(sale => {
        const currency = currencies.find(currency => currency.attributes.code === sale.attributes?.currency);
        return {
            reference_code: sale.attributes?.reference_code,
            customer_name: sale.attributes?.customer_name,
            customer_id: sale.attributes?.customer_id,
            warehouse_name: sale.attributes?.warehouse_name,
            warehouse_id: sale.attributes?.warehouse_id,
            status: sale.attributes?.status,
            payment_status: sale.attributes?.payment_status || 2, // Default return as unpaid or from sale
            tax_rate: sale.attributes?.tax_rate ? sale.attributes.tax_rate : parseFloat(0.00).toFixed(2),
            tax_amount: sale.attributes?.tax_amount ? sale.attributes.tax_amount : parseFloat(0.00).toFixed(2),
            shipping: sale.attributes?.shipping,
            discount: sale.attributes?.discount,
            grand_total: sale.attributes?.grand_total,
            payment_type: sale.attributes?.payment_type,
            paid_amount: sale.attributes?.paid_amount ? sale.attributes.paid_amount : parseFloat(0.00).toFixed(2),
            received_amount: sale.attributes?.paid_amount ? sale.attributes.paid_amount : parseFloat(0.00).toFixed(2),
            
            // Loaded from associated sale
            marketplace_commission: sale.attributes?.marketplace_commission ? sale.attributes.marketplace_commission : parseFloat(0.00).toFixed(2),
            order_process_fee: sale.attributes?.order_process_fee ? sale.attributes.order_process_fee : parseFloat(0.00).toFixed(2),
            other_income: sale.attributes?.other_income ? sale.attributes.other_income : parseFloat(0.00).toFixed(2),
            courier_fee: sale.attributes?.courier_fee ? sale.attributes.courier_fee : parseFloat(0.00).toFixed(2),
            other_cost: sale.attributes?.other_cost ? sale.attributes.other_cost : parseFloat(0.00).toFixed(2),
            selling_value_eur: sale.attributes?.selling_value_eur ? sale.attributes.selling_value_eur : parseFloat(0.00).toFixed(2),

            country: sale.attributes?.country,
            note: sale.attributes?.note,
            barcode_symbol: sale.attributes?.barcode_symbol,
            market_place: sale.attributes?.market_place,
            order_no: sale.attributes?.order_no,
            date: moment(sale.attributes.date).format('YYYY-MM-DD'),
            time: moment(sale.attributes.created_at).format('LT'),
            currency: currency?.attributes?.symbol || '',
            sale_return_items: sale.attributes?.sale_return_items || '',
            shipment: sale.attributes?.shipment,
            sale_id: sale.attributes?.sale_id,
            id: sale.id
        }
    }) : [];

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
            name: getFormattedMessage('order.no'),
            selector: row => row.order_no,
            sortField: 'order_no',
            sortable: true,
        },
        {
            name: getFormattedMessage('react-data-table.date.column.label'),
            selector: row => row.date,
            sortField: 'date',
            sortable: false,
            cell: row => {
                return (
                    row.date && <span className='badge bg-light-info' >
                        <div className='mb-1'>{row.time}</div>
                        <div>{row.date}</div>
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('customer.title'),
            selector: row => row.customer_name,
            sortField: 'customer_name',
            sortable: false,
        },

        {
            name: "VAT RATE",
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.tax_rate),
            sortField: 'tax_rate',
            sortable: true,
        },
        {
            name: getFormattedMessage('globally.detail.order.vat'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.tax_amount),
            sortField: 'tax_amount',
            sortable: true,
        },
        {
            name: getFormattedMessage('purchase.grant-total.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.grand_total),
            sortField: 'grand_total',
            sortable: true,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paid.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.paid_amount),
            sortField: 'paid_amount',
            sortable: true,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paymentStatus.label'),
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
            name: ' SELLING VALUE (EUR)',
            selector: row => row.selling_value_eur,
            sortField: 'selling_value_eur',
            sortable: true,
        },
        {
            name: 'TOTAL RETURN ITEMS',
            selector: row => row.sale_return_items?.length,
            sortField: 'marketplace_commission',
            sortable: true,
        },
        {
            name: getFormattedMessage('sale.marketplace.commission.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.marketplace_commission),
            sortField: 'marketplace_commission',
            sortable: true,
        },

        {
            name: getFormattedMessage('sale.courier.fee.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.courier_fee),
            sortField: 'courier_fee',
            sortable: true,
        },
        {
            name: getFormattedMessage('sale.other.income.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.other_income),
            sortField: 'other_income',
            sortable: true,
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
            name: 'Parcel Company',
            sortField: 'shipment',
            sortable: false,
            cell: row => {
                return row.shipment ? (
                    row.shipment.parcel_company_id === 1 ? (
                        <span className='badge bg-light-success'>
                            <span>GLS</span>
                        </span>
                    ) : row.shipment.parcel_company_id === 2 ? (
                        <span className='badge bg-light-danger'>
                            <span>EXPEDICO</span>
                        </span>
                    ) : row.shipment.parcel_company_id === 3 ? (
                        <span className='badge bg-light-warning'>
                            <span>REDEX</span>
                        </span>
                    ) : ''
                ) : '';
            }
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            cell: row => {
                return (
                    <FontAwesomeIcon icon={faPenToSquare} className="fs-1" style={{ cursor: 'pointer' }} onClick={() => handleEditSaleReportClick(row)} />
                )
            },
        },
    ];

    const onChange = (filter) => {
        filterDatesRef.current = {
            start_date: filter.start_date || null,
            end_date: filter.end_date || null,
        };
        fetchSalesReturn(filter, true);
    };

    const onExcelClick = () => {
        setIsWarehouseValue(true);
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('sale-return.reports.title')} />
            <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                totalRows={totalRecord} isShowDateRangeField isEXCEL isShowFilterField
                isStatus onExcelClick={onExcelClick}
                customStyles={{
                    rows: {
                        style: (row) => row.isFooter ? { fontWeight: 'bold', backgroundColor: '#f8f8f8' } : {},
                    },
                }}
            />
            {isEditSaleReportOpen && (
                <EditSaleReturnReportModal
                    isEditSaleReportOpen={isEditSaleReportOpen}
                    setIsEditSaleReportOpen={handleCloseModal}
                    onEditSaleReportClick={handleCloseModal}
                    saleReportItem={selectedSaleReportItem}
                />
            )}
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const { salesReturn, currencies, frontSetting, isLoading, totalRecord, dates, allConfigData } = state;
    return { salesReturn, currencies, frontSetting, isLoading, totalRecord, dates, allConfigData }
};

export default connect(mapStateToProps, { fetchFrontSetting, fetchSalesReturn, totalSaleReturnReportExcel, fetchCurrencies })(SaleReturnReport);
