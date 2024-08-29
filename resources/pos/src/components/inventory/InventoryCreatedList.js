import React, {useEffect, useState} from "react";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import {Spinner} from 'react-bootstrap';
import {getFormattedMessage,} from "../../shared/sharedMethod";
import {useParams} from 'react-router-dom';

import axiosApi from "../../config/apiConfigWthFormData";
import {apiBaseURL} from "../../constants";
import HeaderTitle from "../header/HeaderTitle";

const InventoryCreatedList = () => {

    const [inventories, setInventories] = useState([]);
    const [totalRecord, setTotalRecord] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [isDownloadLoading, setIsDownloadLoading] = useState(false);
    const [isCallInventoryApi, setIsCallInventoryApi] = useState(false);
    const {id} = useParams();


    useEffect(() => {
        fetchInventories();
    }, []);

    const buildQueryParams = (filter) => {
        const queryParams = Object.keys(filter)
            .map((key) => encodeURIComponent(key) + '=' + encodeURIComponent(filter[key]))
            .join('&');
        return queryParams ? '?' + queryParams : '';
    };

    const fetchInventories = async (filter = {}) => {
        setIsLoading(true);
        try {
            let url = apiBaseURL.INVENTORY_INVOICE + '/' + id;
            const queryParams = buildQueryParams(filter);
            url += queryParams; // Append query parameters to the URL
            const response = await axiosApi.get(url);
            setInventories(Array.isArray(response.data.data) ? response.data.data : []); // Ensure inventories is an array
            // setTotalRecord(response.data.meta.total);
        } catch (error) {
            console.error('Error fetching inventories:', error);
            // Handle error as needed
        } finally {
            setIsLoading(false);
        }
    };

    const onChange = (filter) => {
        fetchInventories(filter);
    };

    const handleDownload = async () => {
        try {
            setIsDownloadLoading(true);
            const formData = new FormData();
            formData.append('id', id); // Assuming `id` is defined in your component's scope

            const response = await axiosApi.post(apiBaseURL.INVENTORY_DOWNLOAD, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                responseType: 'blob', // Important for handling the file download
            });
            // Create a URL for the blob and download it
            const url = window.URL.createObjectURL(new Blob([response.data], {type: 'application/pdf'}));
            const link = document.createElement('a');
            link.href = url;
            const cDate = new Date();
            link.setAttribute('download', 'Inventory-sticker-'+cDate + '.pdf');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('There was an error downloading the file!', error);
        } finally {
            setIsDownloadLoading(false);
        }
    };

    // Ensure inventories is an array before mapping
    const itemsValue = Array.isArray(inventories) ? inventories.map((item) => ({
        id: item.id,
        no_of_items_per_box: item.no_of_items_per_box,
        sticker_meas_unit: item.sticker_meas_unit,
        no_of_boxes: item.no_of_boxes,
        net_wt: item.net_wt,
        gross_wt: item.gross_wt,
        carton_meas: item.carton_meas,
        combos: item.combos
    })) : [];

    return (
        <MasterLayout>
            <TopProgressBar/>

            <HeaderTitle title="Inventory Invoice Download" to='/app/inventory'/>


            {/* <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                title={getFormattedMessage("inventory.title")}
                ButtonValue={getFormattedMessage('inventory.create.title')}
                to='#/app/inventory/create'
                totalRows={totalRecord}
                isLoading={isLoading}
                isCallInventoryApi={isCallInventoryApi}
            /> */}
            {isLoading ? (
                <div className="text-center" style={{textAlign: 'center'}}>
                    <Spinner animation="border" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </Spinner>
                </div>
            ) : (
                <div className="card">


                    <div className="card-body">
                        <table className="table table-bordered">
                            <thead>
                            <tr>
                                <th>{getFormattedMessage('inventory.form.product.label')}</th>
                                <th>Sticker Unit</th>
                                <th>{getFormattedMessage('inventory.form.no_of_boxs.label')}</th>
                                <th>{getFormattedMessage('inventory.form.net_wt.label')}</th>
                                <th>{getFormattedMessage('inventory.form.gross_wt.label')}</th>
                                <th>{getFormattedMessage('inventory.form.carton_meas.label')}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {itemsValue.map((item) => (
                                <tr key={item.id}>
                                    <td>
                                        {item.combos.map((combo,index) => {
                                            return (
                                                <div key={index}>
                                                    {combo.product?.name} - {combo.product?.product_abstract?.pan_style} - {combo.color} - {combo.size} - <b>No. Of Items:  {combo.item_per_box}</b>
                                                    {/*{product.name} - { product.product_abstract.pan_style}*/}
                                                </div>
                                            );
                                        })}
                                    </td>
                                    <td>{item.sticker_meas_unit}</td>
                                    <td>{item.no_of_boxes}</td>
                                    <td>{item.net_wt}</td>
                                    <td>{item.gross_wt}</td>
                                    <td>{item.carton_meas}</td>
                                </tr>
                            ))}
                            </tbody>
                        </table>

                    </div>
                    <div className="card-footer text-center">
                        {isDownloadLoading ? (
                            <button className="btn btn-primary" disabled>

                                Downloading...

                            </button>
                        ) : (


                            <button className="btn btn-primary" onClick={handleDownload}>
                                Download All
                            </button>
                        )}
                    </div>

                </div>
            )}
        </MasterLayout>
    );
};

export default InventoryCreatedList;
