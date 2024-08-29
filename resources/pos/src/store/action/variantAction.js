import apiConfig from '../../config/apiConfigWthFormData';
import {apiBaseURL, Filters, variantActionType,productAbstractActionType, toastType, Tokens} from '../../constants';
import {addToast} from './toastAction'
import {setTotalRecord, addInToTotalRecord, removeFromTotalRecord} from './totalRecordAction';
import requestParam from '../../shared/requestParam';
import {setLoading} from './loadingAction';
import {getFormattedMessage} from '../../shared/sharedMethod';
import {setSavingButton} from "./saveButtonAction";
import { callImportProductApi } from './importProductApiAction';
import axios from 'axios';


export const fetchVariants = (filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    let url = apiBaseURL.VARIANTS;
    if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
        url += requestParam(filter);
    }
    apiConfig.get(url)
        .then((response) => {
            dispatch({type: variantActionType.FETCH_VARIANTS, payload: response.data.data});
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



export const addVariant = (variants) => async (dispatch) => {
    await apiConfig.post(apiBaseURL.VARIANTS, variants)
        .then((response) => {
            dispatch({type: variantActionType.ADD_VARIANT, payload: response.data.data});
            dispatch(fetchVariants(Filters.OBJ));

            dispatch(addToast({text: getFormattedMessage('variant.success.create.message')}));
            dispatch(addInToTotalRecord(1))
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};


export const editVariant = (variantId, variant, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    apiConfig.post(apiBaseURL.VARIANTS + '/' + variantId, variant)
        .then((response) => {
            navigate('/app/product/abstracts')
            dispatch(addToast({text: getFormattedMessage('variant.success.update.message')}));
            dispatch({type: variantActionType.EDIT_VARIANT, payload: response.data.data});
            dispatch(setSavingButton(false))
        })
        .catch(({response}) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const fetchAllVariants = () => async (dispatch) => {
    apiConfig.get(`variants?page[size]=0`)
        .then((response) => {
            dispatch({type: variantActionType.FETCH_ALL_VARIANTS, payload: response.data.data});
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const deleteVariant = (variantId) => async (dispatch) => {
    apiConfig.delete(apiBaseURL.VARIANTS + '/' + variantId)
        .then((response) => {
            dispatch(removeFromTotalRecord(1));
            dispatch({type: variantActionType.DELETE_VARIANT, payload: unitId});
            dispatch(addToast({text: getFormattedMessage('unit.success.delete.message')}));
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

