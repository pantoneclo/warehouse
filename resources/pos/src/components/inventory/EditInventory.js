import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import TopProgressBar from '../../shared/components/loaders/TopProgressBar';
import { Spinner } from 'react-bootstrap';
import { getFormattedMessage } from '../../shared/sharedMethod';
import axiosApi from "../../config/apiConfigWthFormData";
import { apiBaseURL } from "../../constants";
import { addToast } from '../../store/action/toastAction';
import { toastType } from '../../constants';
import { useDispatch } from 'react-redux';

const EditInventory = () => {
    const { insert_key } = useParams();
    const navigate = useNavigate();
    const dispatch = useDispatch();

    const [inventories, setInventories] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        fetchInventories();
    }, [insert_key]);

    const fetchInventories = async () => {
        setIsLoading(true);
        try {
            // Directly use the insert_key from URL params
            const url = apiBaseURL.INVENTORY_INVOICE + '/' + insert_key;
            const response = await axiosApi.get(url);
            setInventories(Array.isArray(response.data.data) ? response.data.data : []);
        } catch (error) {
            console.error('Error fetching inventories:', error);
            dispatch(addToast({
                text: 'Error fetching inventory data',
                type: toastType.ERROR
            }));
        } finally {
            setIsLoading(false);
        }
    };

    // Handle input changes for inventory fields
    const handleInventoryChange = (index, field, value) => {
        const updatedInventories = [...inventories];
        updatedInventories[index][field] = value;
        setInventories(updatedInventories);
    };

    // Handle input changes for combo fields
    const handleComboChange = (inventoryIndex, comboIndex, field, value) => {
        const updatedInventories = [...inventories];
        updatedInventories[inventoryIndex].combos[comboIndex][field] = value;
        setInventories(updatedInventories);
    };

    // Save changes
    const handleSave = async () => {
        setIsSaving(true);
        try {
            // Check if we have data to save
            if (!inventories || inventories.length === 0) {
                throw new Error('No inventory data to save');
            }

            // Prepare data for saving
            const saveData = inventories.map(item => ({
                id: item.id,
                insert_key: item.insert_key,
                sticker_meas_unit: item.sticker_meas_unit,
                no_of_boxes: item.no_of_boxes,
                net_wt: item.net_wt,
                gross_wt: item.gross_wt,
                carton_meas: item.carton_meas,
                combos: item.combos.map(combo => ({
                    id: combo.id,
                    product_id: combo.product_id,
                    item_per_box: combo.item_per_box,
                    variant_id: combo.variant_id,
                    color: combo.color,
                    size: combo.size,
                    style: combo.style
                }))
            }));

            // Debug: Log the data being sent
            console.log('Saving data:', { inventories: saveData });
            console.log('API URL:', apiBaseURL.INVENTORY + '/update/' + insert_key);

            // Call API to save changes - use the specific update route with insert_key
            const response = await axiosApi.post(apiBaseURL.INVENTORY + '/update/' + insert_key, {
                inventories: saveData
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            console.log('Save response:', response.data);

            dispatch(addToast({
                text: getFormattedMessage('inventory.success.edit.message'),
                type: toastType.SUCCESS
            }));

            navigate('/app/inventory');
        } catch (error) {
            console.error('Error saving inventory:', error);
            dispatch(addToast({
                text: error.response?.data?.message || 'Error saving inventory',
                type: toastType.ERROR
            }));
        } finally {
            setIsSaving(false);
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
            <TopProgressBar />
            <HeaderTitle title={getFormattedMessage('inventory.edit.title')} to='/app/inventory' />

            {isLoading ? (
                <div className="text-center" style={{textAlign: 'center'}}>
                    <Spinner animation="border" role="status">
                        <span className="visually-hidden">Loading...</span>
                    </Spinner>
                </div>
            ) : (
                <div className="card">
                    <div className="card-header d-flex justify-content-between align-items-center">
                        <h5 className="mb-0">Edit Inventory</h5>
                        <div>
                            <button
                                className="btn btn-secondary me-2"
                                onClick={() => navigate('/app/inventory')}
                                disabled={isSaving}
                            >
                                Cancel
                            </button>
                            <button
                                className="btn btn-primary"
                                onClick={handleSave}
                                disabled={isSaving}
                            >
                                {isSaving ? 'Saving...' : 'Save Changes'}
                            </button>
                        </div>
                    </div>
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
                            {itemsValue.map((item, inventoryIndex) => (
                                <tr key={item.id}>
                                    <td>
                                        {item.combos.map((combo, comboIndex) => {
                                            return (
                                                <div key={comboIndex} className="mb-2 p-2 border rounded">
                                                    <div className="mb-1">
                                                        <strong>{combo.product?.name} - {combo.product?.product_abstract?.pan_style} - {combo.color} - {combo.size}</strong>
                                                    </div>
                                                    <div className="row">
                                                        <div className="col-md-6">
                                                            <label className="form-label">No. Of Items:</label>
                                                            <input
                                                                type="number"
                                                                className="form-control form-control-sm"
                                                                value={combo.item_per_box}
                                                                onChange={(e) => handleComboChange(inventoryIndex, comboIndex, 'item_per_box', parseInt(e.target.value) || 0)}
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </td>
                                    <td>
                                        <select
                                            className="form-control"
                                            value={item.sticker_meas_unit}
                                            onChange={(e) => handleInventoryChange(inventoryIndex, 'sticker_meas_unit', e.target.value)}
                                        >
                                            <option value="PCS">PCS</option>
                                            <option value="Pack">Pack</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            className="form-control"
                                            value={item.no_of_boxes}
                                            onChange={(e) => handleInventoryChange(inventoryIndex, 'no_of_boxes', parseInt(e.target.value) || 0)}
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={item.net_wt || ''}
                                            onChange={(e) => handleInventoryChange(inventoryIndex, 'net_wt', e.target.value)}
                                            placeholder="e.g. 8.52"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={item.gross_wt || ''}
                                            onChange={(e) => handleInventoryChange(inventoryIndex, 'gross_wt', e.target.value)}
                                            placeholder="e.g. 10.32"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={item.carton_meas || ''}
                                            onChange={(e) => handleInventoryChange(inventoryIndex, 'carton_meas', e.target.value)}
                                            placeholder="e.g. 60X40X30 CM"
                                        />
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </MasterLayout>
    );
};

export default EditInventory;
