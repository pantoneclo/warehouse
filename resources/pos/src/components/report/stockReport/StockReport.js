import React, {useEffect, useState} from 'react';
import MasterLayout from '../../MasterLayout';
import TabTitle from '../../../shared/tab-title/TabTitle';
import {currencySymbolHendling, getFormattedMessage, placeholderText} from '../../../shared/sharedMethod';
import ReactDataTable from '../../../shared/table/ReactDataTable';
import {connect} from 'react-redux';
import ReactSelect from '../../../shared/select/reactSelect';
import {fetchAllWarehouses} from '../../../store/action/warehouseAction';
import {fetchFrontSetting} from '../../../store/action/frontSettingAction';
import {stockReportAction} from '../../../store/action/stockReportAction';
import {totalStockReportExcel} from '../../../store/action/totalStockReportExcel';
import {fetchCurrencies} from '../../../store/action/currencyAction';
import {Button} from "react-bootstrap-v5";
import TopProgressBar from "../../../shared/components/loaders/TopProgressBar";
import {faEye} from "@fortawesome/free-solid-svg-icons";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";

const StockReport = (props) => {
    const {
        isLoading,
        totalRecord,
        fetchFrontSetting,
        stockReports,
        fetchAllWarehouses,
        totalStockReportExcel,
        frontSetting,
        warehouses,
        stockReportAction,
        allConfigData,
        currencies,
        fetchCurrencies
    } = props;
    const [warehouseValue, setWarehouseValue] = useState({label: 'All', value: frontSetting?.value?.default_warehouse});
    const [isWarehouseValue, setIsWarehouseValue] = useState(false);
    const [displayCurrency, setDisplayCurrency] = useState('EUR');
    const currencySymbol = frontSetting && frontSetting.value && frontSetting.value.currency_symbol
    const array = warehouses && warehouses
    const selectWarehouseArray = frontSetting && array.filter((item) => item.id === Number(frontSetting?.value?.default_warehouse))

    useEffect(() => {
        stockReportAction(warehouseValue.value ? warehouseValue.value : frontSetting?.value?.default_warehouse)
    }, [frontSetting, warehouseValue])

    useEffect(() => {
        fetchAllWarehouses();
        fetchCurrencies();
    }, []);

    useEffect(() => {
        fetchFrontSetting();
    }, []);

    useEffect(() => {
        if (isWarehouseValue === true) {
            totalStockReportExcel(warehouseValue.value ? warehouseValue.value : frontSetting?.value?.default_warehouse, setIsWarehouseValue);
            setIsWarehouseValue(false);
        }
    }, [isWarehouseValue])

    const usdCurrency = currencies?.find(c => (c.attributes ? c.attributes.code : c.code) === 'USD');
    const usdRate = Number(usdCurrency?.attributes ? usdCurrency.attributes.conversion_rate : usdCurrency?.conversion_rate) || 0.92;

    const itemsValue = currencySymbol && stockReports.length >= 0 && stockReports.map(stockReport => {
        const costEur = Number(stockReport.attributes?.product?.product_cost || 0);
        const priceEur = Number(stockReport.attributes?.product?.product_price || 0);
        const qty = Number(stockReport.attributes?.quantity || 0);
        const costUsd = usdRate > 0 ? (costEur / usdRate) : 0;
        const priceUsd = usdRate > 0 ? (priceEur / usdRate) : 0;
        const totalCostEur = costEur * qty;
        const totalCostUsd = costUsd * qty;
        const totalPriceEur = priceEur * qty;
        const totalPriceUsd = priceUsd * qty;

        return {
            pan_style: stockReport.attributes?.product?.product_abstract?.pan_style,
            code: stockReport.attributes.product.code,
            name: stockReport.attributes.product.name,
            product_category_name: stockReport.attributes.product_category_name,
            product_cost: stockReport.attributes.product.product_cost,
            product_price: stockReport.attributes.product.product_price,
            product_unit: stockReport.attributes.product_unit_name,
            current_stock: qty,
            cost_eur: costEur,
            cost_usd: costUsd,
            price_eur: priceEur,
            price_usd: priceUsd,
            total_cost_eur: totalCostEur,
            total_cost_usd: totalCostUsd,
            total_price_eur: totalPriceEur,
            total_price_usd: totalPriceUsd,
            id: stockReport.attributes.product_id,
            currency: currencySymbol
        };
    });

    const totalQty = (itemsValue || []).reduce((acc, curr) => acc + (Number(curr.current_stock) || 0), 0);
    const totalCostEur = (itemsValue || []).reduce((acc, curr) => acc + (Number(curr.total_cost_eur) || 0), 0);
    const totalCostUsd = (itemsValue || []).reduce((acc, curr) => acc + (Number(curr.total_cost_usd) || 0), 0);
    const totalPriceEur = (itemsValue || []).reduce((acc, curr) => acc + (Number(curr.total_price_eur) || 0), 0);
    const totalPriceUsd = (itemsValue || []).reduce((acc, curr) => acc + (Number(curr.total_price_usd) || 0), 0);

    const onChange = (filter) => {
        stockReportAction(warehouseValue.value ? warehouseValue.value : frontSetting?.value?.default_warehouse, filter)
    };

    const onWarehouseChange = (obj) => {
        setWarehouseValue(obj);
    };

    const onExcelClick = () => {
        setIsWarehouseValue(true);
    };

    const onReportsClick = (item) => {
        const id = item.id;
        window.location.href = '#/app/report/report-detail-stock/' + id;
    };

    const currSymbol = displayCurrency === 'USD' ? '$' : '€';

    const columns = [
        {
            name: "PO",
            sortField: 'code',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-danger'>
                            <span>{row.pan_style}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('globally.react-table.column.code.label'),
            sortField: 'code',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-danger'>
                            <span>{row.code}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('supplier.table.name.column.title'),
            selector: row => row.name,
            sortField: 'name',
            sortable: false,
        },
        {
            name: getFormattedMessage('product.product-details.category.label'),
            selector: row => row.product_category_name,
            sortField: 'product_category_name',
            sortable: false,
        },
        {
            name: displayCurrency === 'USD' ? 'Cost (USD)' : 'Cost (EUR)',
            sortField: 'product_cost',
            sortable: true,
            cell: row => {
                const val = displayCurrency === 'USD' ? row.cost_usd : row.cost_eur;
                return <span>{currSymbol}{val.toFixed(2)}</span>;
            }
        },
        {
            name: displayCurrency === 'USD' ? 'Price (USD)' : 'Price (EUR)',
            sortField: 'product_price',
            sortable: false,
            cell: row => {
                const val = displayCurrency === 'USD' ? row.price_usd : row.price_eur;
                return <span>{currSymbol}{val.toFixed(2)}</span>;
            }
        },
        {
            name: getFormattedMessage('current.stock.label'),
            sortField: 'current_stock',
            sortable: false,
            cell: row => {
                return <div>
                    <div className='badge bg-light-info me-2'>
                        <span>{row.current_stock}</span>
                    </div>
                    <span className='badge bg-light-success me-2'>
                        <span>{row.product_unit}</span>
                    </span>
                </div>
            }
        },
        {
            name: displayCurrency === 'USD' ? 'Total Cost (USD)' : 'Total Cost (EUR)',
            sortable: false,
            cell: row => {
                const val = displayCurrency === 'USD' ? row.total_cost_usd : row.total_cost_eur;
                return <span className='fw-bold text-dark'>{currSymbol}{val.toFixed(2)}</span>;
            }
        },
        {
            name: displayCurrency === 'USD' ? 'Total Retail (USD)' : 'Total Retail (EUR)',
            sortable: false,
            cell: row => {
                const val = displayCurrency === 'USD' ? row.total_price_usd : row.total_price_eur;
                return <span className='fw-bold text-success'>{currSymbol}{val.toFixed(2)}</span>;
            }
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            width: '115px',
            cell: row => <button className="btn btn-sm btn-primary" variant="primary" onClick={() => onReportsClick(row)}>
                {getFormattedMessage("reports.title")}
            </button>
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('stock.reports.title')}/>

            <div className='row mb-4'>
                <div className='col-md-4 mb-3 mb-md-0'>
                    {selectWarehouseArray[0] ? <ReactSelect data={array} onChange={onWarehouseChange}
                                                            defaultValue={selectWarehouseArray[0] ? {
                                                                label: selectWarehouseArray[0].attributes.name,
                                                                value: selectWarehouseArray[0].id
                                                            } : ''}
                                                            title={getFormattedMessage('warehouse.title')} errors={''}
                                                            isRequired
                                                            placeholder={placeholderText('purchase.select.warehouse.placeholder.label')}/> : null}
                </div>
                <div className='col-md-8 d-flex align-items-center justify-content-md-end'>
                    <div className='d-flex align-items-center me-3'>
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
                </div>
            </div>

            {/* Stock Valuation Summary Widgets */}
            <div className='row mb-4'>
                <div className='col-md-4 mb-3 mb-md-0'>
                    <div className='card bg-light-primary p-4 border-0 shadow-sm'>
                        <span className='text-muted fw-bold fs-7 mb-1'>TOTAL STOCK UNITS</span>
                        <span className='fw-bolder fs-3 text-primary'>{totalQty} units</span>
                    </div>
                </div>
                <div className='col-md-4 mb-3 mb-md-0'>
                    <div className='card bg-light-info p-4 border-0 shadow-sm'>
                        <span className='text-muted fw-bold fs-7 mb-1'>TOTAL STOCK COST VALUE</span>
                        <span className='fw-bolder fs-3 text-info'>
                            €{totalCostEur.toFixed(2)} <span className='fs-6 text-muted'>(${totalCostUsd.toFixed(2)})</span>
                        </span>
                    </div>
                </div>
                <div className='col-md-4'>
                    <div className='card bg-light-success p-4 border-0 shadow-sm'>
                        <span className='text-muted fw-bold fs-7 mb-1'>TOTAL STOCK RETAIL VALUE</span>
                        <span className='fw-bolder fs-3 text-success'>
                            €{totalPriceEur.toFixed(2)} <span className='fs-6 text-muted'>(${totalPriceUsd.toFixed(2)})</span>
                        </span>
                    </div>
                </div>
            </div>

            <div className='pt-2'>
                <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                                totalRows={totalRecord} isEXCEL onExcelClick={onExcelClick}/>
            </div>
        </MasterLayout>
    )
};
const mapStateToProps = (state) => {
    const {isLoading, totalRecord, warehouses, frontSetting, stockReports, allConfigData, currencies} = state;
    return {isLoading, totalRecord, warehouses, frontSetting, stockReports, allConfigData, currencies}
};

export default connect(mapStateToProps, {
    fetchAllWarehouses,
    totalStockReportExcel,
    fetchFrontSetting,
    stockReportAction,
    fetchCurrencies
})(StockReport);
