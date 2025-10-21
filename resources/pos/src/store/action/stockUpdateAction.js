import apiConfig from '../../config/apiConfig';
import {apiBaseURL, stockUpdateActionType, toastType} from '../../constants';
import requestParam from '../../shared/requestParam';
import {addToast} from './toastAction';
import {setLoading} from './loadingAction';
import {getFormattedMessage} from '../../shared/sharedMethod';

export const triggerStockUpdateScheduler = (warehouseId = null) => async (dispatch) => {
    dispatch(setLoading(true));

    try {
        // Use default warehouse_id if none provided (process both warehouses 1 and 3)
        const requestData = {};
        if (warehouseId) {
            requestData.warehouse_id = warehouseId;
        }

        const response = await apiConfig.post(apiBaseURL.STOCK_TRIGGER_UPDATE_SCHEDULER, requestData);
        const data = response.data;

        if (data.success) {
            dispatch({
                type: stockUpdateActionType.TRIGGER_STOCK_UPDATE_SUCCESS,
                payload: data
            });

            // Show different message based on status
            if (data.status === 'queued') {
                dispatch(addToast({
                    text: getFormattedMessage('stock.update.scheduler.queued.message'),
                    type: toastType.INFO
                }));
            } else {
                dispatch(addToast({
                    text: getFormattedMessage('stock.update.scheduler.success.message'),
                    type: toastType.SUCCESS
                }));
            }

            return data;
        } else {
            throw new Error(data.message || 'Stock update failed');
        }

    } catch (error) {
        console.error('Error triggering stock update scheduler:', error);

        const errorMessage = error.response?.data?.message || error.message || 'Stock update failed';

        dispatch({
            type: stockUpdateActionType.TRIGGER_STOCK_UPDATE_FAILURE,
            payload: errorMessage
        });

        // Handle specific error cases
        if (error.response?.status === 409) {
            // Already running
            dispatch(addToast({
                text: errorMessage,
                type: toastType.WARNING
            }));
        } else {
            dispatch(addToast({
                text: errorMessage,
                type: toastType.ERROR
            }));
        }

        throw error;
    } finally {
        dispatch(setLoading(false));
    }
};

// New action to check stock update status
export const checkStockUpdateStatus = () => async (dispatch) => {
    try {
        const response = await apiConfig.get(apiBaseURL.STOCK_UPDATE_STATUS);
        const data = response.data;

        if (data.success) {
            dispatch({
                type: stockUpdateActionType.CHECK_STOCK_UPDATE_STATUS_SUCCESS,
                payload: data
            });

            return data;
        } else {
            throw new Error(data.message || 'Failed to check stock update status');
        }

    } catch (error) {
        console.error('Error checking stock update status:', error);

        dispatch({
            type: stockUpdateActionType.CHECK_STOCK_UPDATE_STATUS_FAILURE,
            payload: error.response?.data?.message || error.message || 'Failed to check status'
        });

        throw error;
    }
};
