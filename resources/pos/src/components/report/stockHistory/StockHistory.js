import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import MasterLayout from '../../MasterLayout';
import TabTitle from '../../../shared/tab-title/TabTitle';
import ReactDataTable from '../../../shared/table/ReactDataTable';
import { getFormattedMessage, placeholderText } from '../../../shared/sharedMethod';
import axiosApi from '../../../config/apiConfig';
import ReactSelect from '../../../shared/select/reactSelect';
import { fetchAllWarehouses } from '../../../store/action/warehouseAction';
import TopProgressBar from "../../../shared/components/loaders/TopProgressBar";

const StockHistory = (props) => {
    const { warehouses, fetchAllWarehouses, totalRecord, isLoading, frontSetting } = props;
    const [histories, setHistories] = useState([]);
    const [totalRows, setTotalRows] = useState(0);
    const [loading, setLoading] = useState(true);
    const [warehouseValue, setWarehouseValue] = useState({ label: 'All', value: null });
    const [filter, setFilter] = useState({ page: 1, limit: 10, search: '', sort: 'created_at', order: 'desc' });

    useEffect(() => {
        fetchAllWarehouses();
    }, []);

    useEffect(() => {
        if (frontSetting?.value?.default_warehouse) {
            // Optional: Set default warehouse if needed, but 'All' is better for history
        }
    }, [frontSetting]);

    const fetchHistory = async () => {
        setLoading(true);
        try {
            const params = {
                page: filter.page,
                per_page: filter.limit,
                search: filter.search,
                sort: filter.sort,
                order: filter.order,
                warehouse_id: warehouseValue.value
            };
            const response = await axiosApi.get('stock-history', { params });
            setHistories(response.data.data);
            setTotalRows(response.data.total);
            setLoading(false);
        } catch (error) {
            setLoading(false);
            console.error(error);
        }
    };

    useEffect(() => {
        fetchHistory();
    }, [filter, warehouseValue]);

    const onChange = (filterData) => {
        setFilter(prev => ({ ...prev, ...filterData }));
    };

    const onWarehouseChange = (obj) => {
        setWarehouseValue(obj);
        setFilter(prev => ({ ...prev, page: 1 }));
    };

    const columns = [
        {
            name: getFormattedMessage('globally.date.label'),
            selector: row => row.created_at,
            sortable: true,
            sortField: 'created_at',
            cell: row => (
                <span>{moment(row.created_at).format('YYYY-MM-DD HH:mm')}</span>
            )
        },
        {
            name: getFormattedMessage('warehouse.title'),
            selector: row => row.warehouse_name,
            sortable: false,
        },
        {
            name: getFormattedMessage('product.table.name.column.label'),
            selector: row => row.product_name,
            sortable: false,
            cell: row => (
                <div>
                    <div>{row.product_name}</div>
                    <small className='text-muted'>{row.product_code}</small>
                </div>
            )
        },
        {
            name: 'Action', // Manual string as translation might not exist
            selector: row => row.action,
            sortable: false,
            cell: row => (
                <span className="badge bg-light-primary">{row.action}</span>
            )
        },
        {
            name: 'Change',
            selector: row => row.quantity_change,
            sortable: true,
            sortField: 'quantity',
            cell: row => (
                <span className={row.quantity_change > 0 ? 'text-success' : 'text-danger'}>
                    {row.quantity_change > 0 ? '+' : ''}{row.quantity_change}
                </span>
            )
        },
        {
            name: 'New Qty',
            selector: row => row.new_quantity,
            sortable: false,
        },
        {
            name: 'User',
            selector: row => row.user_name,
            sortable: false,
        },
        {
            name: 'Note',
            selector: row => row.note,
            sortable: false,
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title='Stock History' />
            <div className='mx-auto mb-md-5 col-12 col-md-4'>
                <ReactSelect data={warehouses} onChange={onWarehouseChange}
                    defaultValue={warehouseValue}
                    title={getFormattedMessage('warehouse.title')}
                    placeholder={placeholderText('purchase.select.warehouse.placeholder.label')} />
            </div>
            <div className='pt-md-7'>
                <ReactDataTable
                    columns={columns}
                    items={histories}
                    onChange={onChange}
                    isLoading={loading}
                    totalRows={totalRows}
                    paginationServer // To enable server-side pagination in ReactDataTable if supported
                />
            </div>
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const { warehouses, frontSetting, isLoading } = state;
    return { warehouses, frontSetting, isLoading }
};

export default connect(mapStateToProps, { fetchAllWarehouses })(StockHistory);
