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

            dispatch(addToast({
                text: getFormattedMessage('stock.update.scheduler.success.message'),
                type: toastType.SUCCESS
            }));

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

        dispatch(addToast({
            text: errorMessage,
            type: toastType.ERROR
        }));

        throw error;
    } finally {
        dispatch(setLoading(false));
    }
};
