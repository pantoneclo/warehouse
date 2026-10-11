import React, {useEffect, useState} from 'react';
import {connect} from 'react-redux';
import ReactDataTable from '../../../shared/table/ReactDataTable';
import {currencySymbolHendling, getFormattedMessage} from '../../../shared/sharedMethod';
import {fetchSalesReturn} from '../../../store/action/salesReturnAction';
import {fetchFrontSetting} from '../../../store/action/frontSettingAction';
import {saleReturnExcelAction} from '../../../store/action/salesReturnExcelAction';
import {countryOptions, getCurrencySymbol} from '../../../constants';

const SaleReturnTab = (props) => {
    const {
        isLoading,
        totalRecord,
        fetchSalesReturn,
        salesReturn,
        frontSetting,
        fetchFrontSetting,
        warehouseValue, saleReturnExcelAction, allConfigData
    } = props;
    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol
    const [isWarehouseValue, setIsWarehouseValue] = useState(false);
    const [displayCurrency, setDisplayCurrency] = useState('EUR');

    useEffect(() => {
        if(isWarehouseValue === true) {
            saleReturnExcelAction(warehouseValue.value, setIsWarehouseValue);
        }
    }, [isWarehouseValue])

    useEffect(() => {
        fetchFrontSetting();
    }, [warehouseValue]);

    const itemsValue = currencySymbol && salesReturn.length >= 0 && salesReturn.map(sale => {
        const country = countryOptions.find(country => country.code === sale.attributes?.country);
        const currencySymbolToUse = country?.currencySymbol || getCurrencySymbol(sale.attributes?.currency) || currencySymbol;
        return {
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
            name: getFormattedMessage('purchase.grant-total.label'),
            selector: row => currencySymbolHendling(allConfigData, row.currency, row.grand_total),
            sortField: 'grand_total',
            sortable: true,
        },
        {
            name: displayCurrency === 'USD' ? 'RETURN VALUE (USD)' : 'RETURN VALUE (EUR)',
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
            name: getFormattedMessage('purchase.select.status.label'),
            sortField: 'status',
            sortable: false,
            cell: row => {
                return (
                    row.status === 1 &&
                    <span className='badge bg-light-success'>
                        <span>{getFormattedMessage("status.filter.returned.label")}</span>
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
    ];

    const onChange = (filter) => {
        fetchSalesReturn(filter, true);
    };

    const onExcelClick = () => {
        setIsWarehouseValue(true);
    };

    return (
        <div className='warehouse_sale_return_report_table'>
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
            <ReactDataTable columns={columns} isEXCEL items={itemsValue} onChange={onChange}
                            isLoading={isLoading} warehouseValue={warehouseValue} totalRows={totalRecord}
                            onExcelClick={onExcelClick} isStatus isShowFilterField/>
        </div>
    )
};

const mapStateToProps = (state) => {
    const {isLoading, totalRecord, salesReturn, frontSetting} = state;
    return {isLoading, totalRecord, salesReturn, frontSetting}
};

export default connect(mapStateToProps, {fetchFrontSetting, fetchSalesReturn, saleReturnExcelAction})(SaleReturnTab);
