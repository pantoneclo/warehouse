import apiConfig from '../../config/apiConfigWthFormData';
import {apiBaseURL, Filters, productAbstractActionType, toastType, Tokens} from '../../constants';
import {addToast} from './toastAction'
import {setTotalRecord, addInToTotalRecord, removeFromTotalRecord} from './totalRecordAction';
import requestParam from '../../shared/requestParam';
import {setLoading} from './loadingAction';
import {getFormattedMessage} from '../../shared/sharedMethod';
import {setSavingButton} from "./saveButtonAction";
import { callImportProductApi } from './importProductApiAction';
import axios from 'axios';

// Create a cancel token source

export const fetchProductAbstracts = (filter = {}, isLoading = true, cancelToken=null) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    let url = apiBaseURL.PRODUCT_ABSTRACTS;
    if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
        url += requestParam(filter);
    }
    apiConfig.get(url)
        .then((response) => {
            dispatch({type: productAbstractActionType.FETCH_ABSTRACT_PRODUCTS, payload: response.data.data});
            dispatch(setTotalRecord(response.data.meta.total));
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response?.data.message, type: toastType.ERROR}));
        });
};
export const fetchProductAbstract = (productId, singleProduct, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    apiConfig.get(apiBaseURL.PRODUCT_ABSTRACTS + '/' + productId, singleProduct)

        .then((response) => {
            dispatch({type: productAbstractActionType.FETCH_ABSTRACT_PRODUCT, payload: response.data?.data});
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data?.message, type: toastType.ERROR}));
        });
};
export const addProductAbstract = (abstractProducts ,navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    await apiConfig.post(apiBaseURL.PRODUCT_ABSTRACTS, abstractProducts)
    .then((response) => {
        dispatch({type: productAbstractActionType.ADD_ABSTRACT_PRODUCT,
            payload: response?.data?.data});
        dispatch(addToast({text: getFormattedMessage('product.abstract.success.create.message')}));
        navigate('/app/product/abstracts')
        // dispatch(addInToTotalRecord(1))
        dispatch(setSavingButton(false))
    })
    .catch(({response}) => {
        dispatch(setSavingButton(false))
        dispatch(addToast(
            {text: response?.data?.message, type: toastType.ERROR}));
    });


};
export const editProductAbstraction = (productId, product, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    apiConfig.post(apiBaseURL.PRODUCT_ABSTRACTS + '/' + productId, product)
        .then((response) => {
            navigate('/app/product/abstracts')
            dispatch(addToast({text: getFormattedMessage('product.abstract.success.update.message')}));
            dispatch({type: productAbstractActionType.EDIT_ABSTRACT_PRODUCT, payload: response.data.data});
            dispatch(setSavingButton(false))
        })
        .catch(({response}) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const fetchAllProductAbstracts = () => async (dispatch) => {
    apiConfig.get(`product_abstracts?page[size]=0`)
        .then((response) => {
            dispatch({type: productAbstractActionType.FETCH_ALL_ABSTRACT_PRODUCTS, payload: response.data.data});
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};
export const deleteProductAbstract = (productId) => async (dispatch) => {
    apiConfig.delete(apiBaseURL.PRODUCT_ABSTRACTS + '/' + productId)
        .then((response) => {
            dispatch(removeFromTotalRecord(1));
            dispatch({type: productActionType.DELETE_ABSTRACT_PRODUCT, payload: productId});
            dispatch(addToast({text: getFormattedMessage('product.success.delete.message')}));
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};
