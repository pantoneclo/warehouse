import React, {useState} from 'react';
import {connect} from 'react-redux';
import MasterLayout from '../MasterLayout';
import {fetchCurrencies} from '../../store/action/currencyAction';
import ReactDataTable from '../../shared/table/ReactDataTable';
import DeletCurrency from './DeletCurrency';
import CreateCurrency from './CreateCurrency';
import EditCurrency from './EditCurrency';
import CurrencyHistoryModal from './CurrencyHistoryModal';
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faEye} from '@fortawesome/free-solid-svg-icons';
import TabTitle from '../../shared/tab-title/TabTitle';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";

const Currencies = (props) => {
    const {fetchCurrencies, currencies, totalRecord, isLoading} = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [toggle, setToggle] = useState(false);
    const [currency, setCurrency] = useState();
    const [historyModal, setHistoryModal] = useState(false);
    const [historyCurrency, setHistoryCurrency] = useState(null);

    const handleHistoryModal = (item) => {
        setHistoryCurrency(item);
        setHistoryModal(true);
    };

    const handleClose = (item = null) => {
        setToggle(!toggle);
        setCurrency(item);
    };

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchCurrencies(filter, true);
    };

    const itemsValue = currencies.length >= 0 && currencies.map(item => ({
        name: item.attributes.name,
        code: item.attributes.code,
        symbol: item.attributes.symbol,
        id: item.id,
        conversion_rate: item.attributes.conversion_rate
    }));

    const columns = [
        {
            name: getFormattedMessage('globally.input.name.label'),
            selector: row => row.name,
            sortable: true,
            sortField: 'name',
        },
        {
            name: getFormattedMessage('currency.modal.input.code.label'),
            selector: row => row.code,
            sortField: 'code',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-info'>
                            <span>{row.code}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('currency.modal.input.symbol.label'),
            sortField: 'symbol',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-primary'>
                            <span>{row.symbol}</span>
                        </span>
            }
        },
        {
            name: getFormattedMessage('currency.modal.input.symbol.conversion_rate'),
            sortField: 'conversion_rate',
            sortable: true,
            cell: row => {
                return <span className='badge bg-light-primary'>
                            <span>{row.conversion_rate}</span>
                        </span>
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
                    <div className='d-flex align-items-center justify-content-end'>
                        <button
                            type="button"
                            title="Rate Change History"
                            className='btn text-info fs-3 border-0 px-xxl-2 px-1'
                            onClick={(e) => {
                                e.stopPropagation();
                                handleHistoryModal(row);
                            }}
                        >
                            <FontAwesomeIcon icon={faEye}/>
                        </button>
                        <ActionButton item={row} goToEditProduct={handleClose} isEditMode={true}
                                     onClickDeleteModel={onClickDeleteModel}/>
                    </div>
                );
            }
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('currencies.title')}/>
            <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                            totalRows={totalRecord} AddButton={<CreateCurrency/>}/>
            <EditCurrency handleClose={handleClose} show={toggle} currency={currency}/>
            <DeletCurrency onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete}/>
            <CurrencyHistoryModal
                show={historyModal}
                handleClose={() => setHistoryModal(false)}
                currency={historyCurrency}
            />
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {currencies, totalRecord, isLoading} = state;
    return {currencies, totalRecord, isLoading}
};

export default connect(mapStateToProps, {fetchCurrencies})(Currencies);

