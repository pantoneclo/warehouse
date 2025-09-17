import apiConfig from '../../config/apiConfigWthFormData';
import {apiBaseURL, Filters, inventoryActionType, productActionType, toastType, Tokens} from '../../constants';
import {addToast} from './toastAction'
import requestParam from '../../shared/requestParam';
import {setLoading} from './loadingAction';
import axios from 'axios';
import {setSavingButton} from "./saveButtonAction";
import {addInToTotalRecord, setTotalRecord, removeFromTotalRecord} from './totalRecordAction';
import {getFormattedMessage} from '../../shared/sharedMethod';

export const fetchProducts = (filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    let url = apiBaseURL.PRODUCTS_NO_PAGINATE;

    // if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
    //     url += requestParam(filter);
    // }
    if (filter.search) {
        url += `?filter=${filter.search}`;
    }
    apiConfig.get(url)
        .then((response) => {
            dispatch({type: productActionType.FETCH_PRODUCTS, payload: response.data.data});
            dispatch(setTotalRecord(response.data.meta.total));
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};



export const fetchInventories = (filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    let url = apiBaseURL.INVENTORY;
    if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
        url += requestParam(filter);
    }
    apiConfig.get(url)
        .then((response) => {
            dispatch({type: inventoryActionType.FETCH_INVENTORIES, payload: response.data.data});
            dispatch(setTotalRecord(response.data.meta.total));
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const addInventory = (inventory, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    await apiConfig.post(apiBaseURL.ADD_INVENTORY, inventory)
        .then((response) => {
            dispatch({type: inventoryActionType.ADD_INVENTORY, payload: response.data.data});
            console.log('dd',response.data.data);
            dispatch(addToast({text: getFormattedMessage('inventory.success.create.message')}));

            navigate('/app/inventory/'+response.data.data.insert_key)
            dispatch(addInToTotalRecord(1))
            dispatch(setSavingButton(false))
        })
        .catch(({response}) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

// export const deleteSticker = (stickerId) => async (dispatch) => {
//     apiConfig.post(apiBaseURL.INVENTORY +'/delete'+ '/' + stickerId)
//         .then((response) => {
//             dispatch(removeFromTotalRecord(1));
//             dispatch({type: brandsActionType.DELETE_INVENTORY, payload: stickerId});
//             dispatch(addToast({text: getFormattedMessage('brand.success.delete.message')}));
//         })
//         .catch(({response}) => {
//             dispatch(addToast(
//                 {text: response.data.message, type: toastType.ERROR}));
//         });
// };

export const fetchInventory = (inventoryId) => async (dispatch) => {
    dispatch(setLoading(true));
    apiConfig.post(apiBaseURL.INVENTORY + '/' + inventoryId)
        .then((response) => {
            dispatch({type: inventoryActionType.FETCH_INVENTORY, payload: response.data.data || response.data});
            dispatch(setLoading(false));
        })
        .catch(({response}) => {
            dispatch(setLoading(false));
            dispatch(addToast(
                {text: response?.data?.message || 'Error fetching inventory', type: toastType.ERROR}));
        });
};

export const editInventory = (inventoryId, inventory, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true));
    await apiConfig.post(apiBaseURL.INVENTORY + '/' + inventoryId, inventory)
        .then((response) => {
            dispatch({type: inventoryActionType.EDIT_INVENTORY, payload: response.data.data});
            dispatch(addToast({text: getFormattedMessage('inventory.success.edit.message')}));
            navigate('/app/inventory');
            dispatch(setSavingButton(false));
        })
        .catch(({response}) => {
            dispatch(setSavingButton(false));
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const deleteSticker = (stickerId) => async (dispatch) => {
    apiConfig.post(apiBaseURL.INVENTORY +'/delete'+ '/' + stickerId)
        .then((response) => {
            dispatch(removeFromTotalRecord(1));
            dispatch({type: inventoryActionType.DELETE_INVENTORY, payload: stickerId});
            dispatch(addToast({text: 'Sticker Successfully Deleted'}));
        })
        .catch(({response}) => {
            response && dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};
