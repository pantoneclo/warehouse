import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import MasterLayout from '../../MasterLayout';
import TabTitle from '../../../shared/tab-title/TabTitle';
import ReactDataTable from '../../../shared/table/ReactDataTable';
import { getFormattedMessage, placeholderText, currencySymbolHendling } from '../../../shared/sharedMethod';
import axiosApi from '../../../config/apiConfig';
import ReactSelect from '../../../shared/select/reactSelect';
import { fetchAllWarehouses } from '../../../store/action/warehouseAction';
import TopProgressBar from "../../../shared/components/loaders/TopProgressBar";
import { saleStatusOptions, countryOptions, paymentStatusOptions } from '../../../constants';
import DateRangePicker from "../../../shared/datepicker/DateRangePicker";

const SalesItemReport = (props) => {
    const { warehouses, fetchAllWarehouses, totalRecord, isLoading, frontSetting, allConfigData } = props;
    const [reports, setReports] = useState([]);
    const [totalRows, setTotalRows] = useState(0);
    const [loading, setLoading] = useState(true);
    const [warehouseValue, setWarehouseValue] = useState({ label: 'All', value: null });
    const [filter, setFilter] = useState({ page: 1, limit: 10, search: '', sort: 'sale_date', order: 'desc' });
    const currency = frontSetting?.value?.currency_symbol;

    const [filterOptions, setFilterOptions] = useState({ statuses: [], countries: [], paymentStatuses: [] });
    const [statusValue, setStatusValue] = useState({ label: 'All Status', value: null });
    const [paymentStatusValue, setPaymentStatusValue] = useState({ label: 'All', value: null });
    const [countryValue, setCountryValue] = useState({ label: 'All Country', value: null });
    const [selectDate, setSelectDate] = useState();

    useEffect(() => {
        fetchAllWarehouses();
        // Initialize filters from constants
        const statusData = saleStatusOptions.map(s => ({ label: getFormattedMessage(s.name), value: s.id }));
        const countryData = countryOptions.map(c => ({ label: c.name, value: c.code }));
        // Filter out ID 0 (static All) because we manually add All with null value
        const paymentStatusData = paymentStatusOptions.filter(p => p.id !== 0).map(p => ({ label: getFormattedMessage(p.name), value: p.id }));

        statusData.unshift({ label: 'All Status', value: null });
        countryData.unshift({ label: 'All Country', value: null });
        paymentStatusData.unshift({ label: 'All', value: null });

        setFilterOptions({ statuses: statusData, countries: countryData, paymentStatuses: paymentStatusData });
    }, []);

    const onDateSelector = (date) => {
        setSelectDate(date.params);
        if (!date.params) {
            setFilter(prev => ({ ...prev, start_date: null, end_date: null, page: 1 }));
        } else {
            setFilter(prev => ({ ...prev, start_date: date.params.start_date, end_date: date.params.end_date, page: 1 }));
        }
    };

    const fetchReport = async () => {
        setLoading(true);
        try {
            const params = {
                page: filter.page,
                per_page: filter.limit,
                search: filter.search,
                sort: filter.sort,
                order: filter.order,
                warehouse_id: warehouseValue.value,
                status: statusValue.value,
                payment_status: paymentStatusValue.value,
                country_id: countryValue.value,
                start_date: filter.start_date,
                end_date: filter.end_date
            };
            const response = await axiosApi.get('sales-item-report', { params });
            setReports(response.data.data);
            setTotalRows(response.data.total);
            setLoading(false);
        } catch (error) {
            setLoading(false);
            console.error(error);
        }
    };

    useEffect(() => {
        fetchReport();
    }, [filter, warehouseValue, statusValue, countryValue, paymentStatusValue]);

    const onChange = (filterData) => {
        setFilter(prev => ({ ...prev, ...filterData }));
    };

    const onExcelClick = () => {
        const params = {
            page: filter.page,
            per_page: filter.limit,
            search: filter.search,
            sort: filter.sort,
            order: filter.order,
            warehouse_id: warehouseValue.value || '',
            status: statusValue.value || '',
            payment_status: paymentStatusValue.value || '',
            country_id: countryValue.value || '',
            start_date: filter.start_date || '',
            end_date: filter.end_date || ''
        };

        setLoading(true);
        axiosApi.get('sales-item-report-export', { params })
            .then((response) => {
                window.open(response.data.data.url, '_blank');
                setLoading(false);
            })
            .catch((error) => {
                setLoading(false);
                console.error(error);
            });
    };

    const onWarehouseChange = (obj) => {
        setWarehouseValue(obj);
        setFilter(prev => ({ ...prev, page: 1 }));
    };

    const onStatusChange = (obj) => {
        setStatusValue(obj);
        setFilter(prev => ({ ...prev, page: 1 }));
    };

    const onPaymentStatusChange = (obj) => {
        setPaymentStatusValue(obj);
        setFilter(prev => ({ ...prev, page: 1 }));
    };

    const onCountryChange = (obj) => {
        setCountryValue(obj);
        setFilter(prev => ({ ...prev, page: 1 }));
    };

    const columns = [
        {
            name: getFormattedMessage('globally.date.label'),
            selector: row => row.sale_date,
            sortable: true,
            sortField: 'sale_date',
            cell: row => (
                <span>{moment(row.sale_date).format('YYYY-MM-DD')}</span>
            )
        },
        {
            name: 'Order ID',
            selector: row => row.reference_code,
            sortable: true,
            sortField: 'reference_code'
        },
        {
            name: 'SKU',
            selector: row => row.sku,
            sortable: true,
            sortField: 'sku'
        },
        {
            name: getFormattedMessage('product.table.name.column.label'),
            selector: row => row.product_name,
            sortable: true,
            sortField: 'product_name',
            cell: row => (
                <div className="d-flex align-items-center">
                    {/* Image handling could be added here if needed */}
                    <div className="d-flex flex-column">
                        <span>{row.product_name}</span>
                    </div>
                </div>
            )
        },
        {
            name: 'FOB (Cost)',
            selector: row => row.fob,
            sortable: false,
            cell: row => currencySymbolHendling(allConfigData, 'EUR', row.fob)
        },
        {
            name: 'Product Price',
            selector: row => row.product_price,
            sortable: false,
            cell: row => currencySymbolHendling(allConfigData, 'EUR', row.product_price)
        },
        {
            name: 'Selling Price (EUR)',
            selector: row => row.selling_price,
            sortable: false,
            cell: row => currencySymbolHendling(allConfigData, 'EUR', row.selling_price)
        },
        {
            name: 'Quantity',
            selector: row => row.quantity,
            sortable: false,
        },
        {
            name: 'Total (EUR)',
            selector: row => row.total,
            sortable: false,
            cell: row => currencySymbolHendling(allConfigData, 'EUR', row.total)
        },
        {
            name: 'Margin (EUR)',
            selector: row => row.margin,
            sortable: false,
            cell: row => (
                <span className={row.margin >= 0 ? 'text-success' : 'text-danger'}>
                    {currencySymbolHendling(allConfigData, 'EUR', row.margin)}
                </span>
            )
        },
        {
            name: 'Available Stock',
            selector: row => row.available_stock,
            sortable: false,
            cell: row => (
                <span className="badge bg-light-info">{row.available_stock || 0}</span>
            )
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title='Sales Item Report' />
            <div className='row mx-0 mb-md-5'>
                <div className='col-12 col-md-3 mb-3'>
                    <ReactSelect data={warehouses} onChange={onWarehouseChange}
                        defaultValue={warehouseValue}
                        title={getFormattedMessage('warehouse.title')}
                        placeholder={placeholderText('purchase.select.warehouse.placeholder.label')} />
                </div>
                <div className='col-12 col-md-3 mb-3'>
                    <ReactSelect
                        data={filterOptions.statuses}
                        onChange={onStatusChange}
                        defaultValue={statusValue}
                        title="Status"
                        placeholder="Select Status"
                    />
                </div>
                <div className='col-12 col-md-3 mb-3'>
                    <ReactSelect
                        data={filterOptions.paymentStatuses}
                        onChange={onPaymentStatusChange}
                        defaultValue={paymentStatusValue}
                        title={getFormattedMessage('dashboard.recentSales.paymentStatus.label')}
                        placeholder={placeholderText('dashboard.recentSales.paymentStatus.label')}
                    />
                </div>
                <div className='col-12 col-md-3 mb-3'>
                    <ReactSelect
                        data={filterOptions.countries}
                        onChange={onCountryChange}
                        defaultValue={countryValue}
                        title="Country"
                        placeholder="Select Country"
                    />
                </div>
                <div className='col-12 col-md-3 mb-3'>
                    <div className="text-end mb-1">
                        <label className="form-label d-block text-start">{getFormattedMessage('date-picker.filter.title')}</label>
                        {/* Or just use the DateRangePicker directly if it handles label? 
                           Looking at DatePicker: it doesn't seem to have a label prop in the code I saw, 
                           but the div has 'text-end'. 
                           Let's check other usages. ReactDataTable doesn't use label. 
                           ProfitLossReport doesn't use label.
                           Slight inconsistency with ReactSelect which has 'title'. 
                           I'll leave it without label for now or add a custom label. 
                           Standardizing: ReactSelect has title. DatePicker doesn't. 
                           I will add a label for consistency if possible or just the picker.
                        */}
                        <DateRangePicker onDateSelector={onDateSelector} selectDate={selectDate} />
                    </div>
                </div>
            </div>
            <div className='pt-md-7'>
                <ReactDataTable
                    columns={columns}
                    items={reports}
                    onChange={onChange}
                    isLoading={loading}
                    totalRows={totalRows}
                    paginationServer
                    isEXCEL={true}
                    onExcelClick={onExcelClick}
                />
            </div>
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const { warehouses, frontSetting, isLoading, allConfigData } = state;
    return { warehouses, frontSetting, isLoading, allConfigData }
};

export default connect(mapStateToProps, { fetchAllWarehouses })(SalesItemReport);
