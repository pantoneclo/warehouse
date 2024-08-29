import apiConfig from '../../config/apiConfigWthFormData';
import {
    apiBaseURL,
    Filters,
    inventoryActionType,
    productAbstractActionType,
    productActionType,
    toastType,
    Tokens
} from '../../constants';
import {addToast} from './toastAction'
import {setTotalRecord, addInToTotalRecord, removeFromTotalRecord} from './totalRecordAction';
import requestParam from '../../shared/requestParam';
import {setLoading} from './loadingAction';
import {getFormattedMessage} from '../../shared/sharedMethod';
import axios from 'axios';

export const fetchInventoryStickers = (filter = {}, isLoading = true) => async (dispatch) => {
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

export const deleteProduct = (productId) => async (dispatch) => {
    apiConfig.delete(apiBaseURL.PRODUCTS + '/' + productId)
        .then((response) => {
            dispatch(removeFromTotalRecord(1));
            dispatch({type: productActionType.DELETE_PRODUCT, payload: productId});
            dispatch(addToast({text: getFormattedMessage('product.success.delete.message')}));
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const fetchAllProducts = () => async (dispatch) => {
    apiConfig.get(`products?page[size]=0`)
        .then((response) => {
            dispatch({type: productActionType.FETCH_ALL_PRODUCTS, payload: response.data.data});
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

