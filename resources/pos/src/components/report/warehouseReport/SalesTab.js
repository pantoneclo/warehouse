import React, {useEffect, useState} from 'react';
import moment from 'moment';
import {connect} from 'react-redux';
import ReactDataTable from '../../../shared/table/ReactDataTable';
import {currencySymbolHendling, getFormattedMessage} from '../../../shared/sharedMethod';
import {fetchSales} from '../../../store/action/salesAction';
import {fetchFrontSetting} from '../../../store/action/frontSettingAction';
import {saleExcelAction} from '../../../store/action/salesExcelAction';
import {countryOptions, getCurrencySymbol} from '../../../constants';

const SalesTab = (props) => {
    const {
        isLoading,
        totalRecord,
        fetchSales,
        sales,
        frontSetting,
        fetchFrontSetting,
        warehouseValue,
        saleExcelAction, allConfigData
    } = props;
    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol
    const [isWarehouseValue, setIsWarehouseValue] = useState(false);
    const [displayCurrency, setDisplayCurrency] = useState('EUR');

    useEffect(() => {
        fetchFrontSetting();
    }, [warehouseValue]);

    const itemsValue = currencySymbol && sales.length >= 0 && sales.map(sale => {
        const country = countryOptions.find(country => country.code === sale.attributes?.country);
        const currencySymbolToUse = country?.currencySymbol || getCurrencySymbol(sale.attributes?.currency) || currencySymbol;
        return {
            time: moment(sale.attributes.created_at).format('LT'),
            reference_code: sale.attributes.reference_code,
            customer_name: sale.attributes.customer_name,
            warehouse_name: sale.attributes.warehouse_name,
            status: sale.attributes.status,
            payment_status: sale.attributes.payment_status,
            grand_total: sale.attributes.grand_total,
            selling_value_eur: sale.attributes?.selling_value_eur,
            selling_value_usd: sale.attributes?.selling_value_usd,
            paid_amount: sale.attributes.paid_amount ? sale.attributes.paid_amount : 0.00.toFixed(2),
            id: sale.id,
            currency: currencySymbolToUse
        };
    });

    const totalEur = (itemsValue || []).reduce((acc, curr) => acc + (parseFloat(curr.selling_value_eur) || 0), 0);
    const totalUsd = (itemsValue || []).reduce((acc, curr) => acc + (parseFloat(curr.selling_value_usd) || 0), 0);

    useEffect(() => {
        if(isWarehouseValue === true) {
            saleExcelAction(warehouseValue.value, setIsWarehouseValue);
        }
    }, [isWarehouseValue])

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
            selector: row =>  currencySymbolHendling(allConfigData, row.currency, row.grand_total),
            sortField: 'grand_total',
            sortable: true,
        },
        {
            name: displayCurrency === 'USD' ? 'SELLING VALUE (USD)' : 'SELLING VALUE (EUR)',
            sortField: displayCurrency === 'USD' ? 'selling_value_usd' : 'selling_value_eur',
            sortable: false,
            cell: row => {
                const symbol = displayCurrency === 'USD' ? '$' : '€';
                const val = displayCurrency === 'USD' ? row.selling_value_usd : row.selling_value_eur;
                return (
                    <span className={`fw-bold ${displayCurrency === 'USD' ? 'text-info' : 'text-success'}`}>
                        {symbol}{parseFloat(val || 0).toFixed(2)}
                    </span>
                );
            }
        },
        {
            name: getFormattedMessage('dashboard.recentSales.paid.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.paid_amount),
            sortField: 'paid_amount',
            sortable: true,
        },
        {
            name: getFormattedMessage('dashboard.recentSales.due.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, '0.00'),
            sortField: 'due',
            // sortable: true,
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
                        <span>{getFormattedMessage("payment-status.filter.partial.label")}</span>
                    </span>
                )
            }
        }
    ];

    const onChange = (filter) => {
        fetchSales(filter, true);
    };

    const onExcelClick = () => {
        setIsWarehouseValue(true);
    };

    return (
        <div className='warehouse_sale_report_table'>
            <div className='d-flex align-items-center justify-content-between mb-3 px-1'>
                <div className='d-flex align-items-center'>
                    <span className='fw-bold me-2 text-gray-700'>Currency View:</span>
                    <div className='btn-group' role='group' aria-label='Currency View Switcher'>
                        <button
                            type='button'
                            className={`btn btn-sm ${displayCurrency === 'EUR' ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setDisplayCurrency('EUR')}
                        >
                            💶 EURO (€)
                        </button>
                        <button
                            type='button'
                            className={`btn btn-sm ${displayCurrency === 'USD' ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setDisplayCurrency('USD')}
                        >
                            💵 USD ($)
                        </button>
                    </div>
                </div>
                <span className='badge bg-light-info text-info fs-7'>
                    Sum in {displayCurrency === 'EUR' ? 'EURO (€)' : 'USD ($)'}: {displayCurrency === 'EUR' ? `€${totalEur.toFixed(2)}` : `$${totalUsd.toFixed(2)}`}
                </span>
            </div>
            <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} warehouseValue={warehouseValue}
                            isLoading={isLoading} totalRows={totalRecord} isEXCEL onExcelClick={onExcelClick}
                            isPaymentStatus isStatus isShowFilterField/>
        </div>
    )
};

const mapStateToProps = (state) => {
    const {isLoading, totalRecord, sales, frontSetting} = state;
    return {isLoading, totalRecord, sales, frontSetting}
};

export default connect(mapStateToProps, {fetchFrontSetting, fetchSales, saleExcelAction})(SalesTab);
