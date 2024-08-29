import React, { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import { Col, Row } from 'react-bootstrap';
import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
import ReactSelect from "../../shared/select/reactSelect";
import { Tokens } from "../../constants";
import ReactDataTable from "../../shared/table/ReactDataTable";
import { fetchInventories } from "../../store/action/InventoryAction";
import CreateInventory from "./CreateInventory";
import DeleteStickers from "./DeleteSticker";

import axiosApi from "../../config/apiConfigWthFormData";
import { apiBaseURL } from "../../constants";
import ActionButton from "../../shared/action-buttons/ActionButton";
import requestParam from '../../shared/requestParam';

const Inventory = () => {
    const [inventories, setInventories] = useState([]);
    const [totalRecord, setTotalRecord] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [isCallInventoryApi, setIsCallInventoryApi] = useState(false);

    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);

    useEffect(() => {
        fetchInventories();
    }, []);


    const onClickDeleteModel = (isDelete = null) => {
        console.log('ccccee');
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
        fetchInventories();
    };


    const buildQueryParams = (filter) => {
        const queryParams = Object.keys(filter)
            .map((key) => encodeURIComponent(key) + '=' + encodeURIComponent(filter[key]))
            .join('&');
        return queryParams ? '?' + queryParams : '';
    };

    const fetchInventories = async (filter = {}) => {
        setIsLoading(true);
        try {
            let url = apiBaseURL.INVENTORY;
            if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
                url += requestParam(filter);
            }
            // let url = apiBaseURL.INVENTORY;
            // const queryParams = buildQueryParams(filter);
            // url += queryParams; // Append query parameters to the URL
            const response = await axiosApi.get(url);
            setInventories(response.data.data.data);
            setTotalRecord(response.data.meta.total);
        } catch (error) {
            console.error('Error fetching inventories:', error);
            // Handle error as needed
        } finally {
            setIsLoading(false);
        }
    };

    const goToProductDetailPage = (row) => {
        console.log('product id',row)
        window.location.href = '#/app/inventory/' + row.insert_key;
    };

    const onChange = (filter) => {
        fetchInventories(filter, true)
    };

    const itemsValue = inventories.map((item) => ({
        insert_key: item.insert_key,
        created_at: item.created_at,
        style: item.combos?.[0]?.style,
        id: item.id,
    }));

    const columns = [
        {
            name: getFormattedMessage('inventory.form.sticker_no.label'),
            selector: (row) => row.insert_key,
            sortable: true,
            cell: (row) => <span>{row.insert_key}</span>,
        },
        {
            name: "Style",
            selector: (row) => row.style,
            sortable: true,
            cell: (row) => <span>{row.style}</span>,
        },
        {
            name: getFormattedMessage('inventory.form.created_at.label'),
            selector: (row) => row.created_at,
            sortable: true,
            cell: (row) => <span>{row.created_at}</span>,
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            width: '120px',
            selector: (row) => row.insert_key,
            cell: (row) =>
                <ActionButton
                    isViewIcon={true}
                    goToDetailScreen={() => goToProductDetailPage(row)} // Pass the entire row object
                    item={row}
                    isEditMode={false}
                    isDeleteMode={true}
                    onClickDeleteModel={onClickDeleteModel}
                />
        }
    ];


    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Inventory Sticker" />
            <h1>Inventory Sticker</h1>

            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                title={getFormattedMessage("inventory.title")}
                ButtonValue={getFormattedMessage('inventory.create.title')}
                to='#/app/inventory/create'
                totalRows={totalRecord}
                isLoading={isLoading}
                isCallInventoryApi={isCallInventoryApi}
            />
            <DeleteStickers
                onClickDeleteModel={onClickDeleteModel}
                deleteModel={deleteModel}
                onDelete={isDelete}
            />
        </MasterLayout>
    );
};

export default Inventory;
