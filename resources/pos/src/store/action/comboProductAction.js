import apiConfig from '../../config/apiConfigWthFormData';
import {apiBaseURL, Filters, comboProductType, toastType, Tokens} from '../../constants';
import {addToast} from './toastAction'
import {setTotalRecord, addInToTotalRecord, removeFromTotalRecord} from './totalRecordAction';
import requestParam from '../../shared/requestParam';
import {setLoading} from './loadingAction';
import {getFormattedMessage} from '../../shared/sharedMethod';
import {setSavingButton} from "./saveButtonAction";
import { callImportProductApi } from './importProductApiAction';
import axios from 'axios';

// Create a cancel token source

export const fetchCombos = (filter = {}, isLoading = true, cancelToken=null) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    let url = apiBaseURL.COMBO_PRODUCT;
    if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
        url += requestParam(filter);
    } 
    apiConfig.get(url)
        .then((response) => {
               // Inspect the response here
               console.log('Response Data:', response.data);

          if (response.data && response.data.data) {
                dispatch({
                    type: comboProductType.FETCH_COMBO_PRODUCTS,
                    payload: response.data.data
                });
                dispatch(setTotalRecord(response.data.meta.total));
            } else {
                // Handle unexpected response structure
                dispatch(addToast({ text: 'Unexpected response structure', type: toastType.ERROR }));
            }
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response?.data.message, type: toastType.ERROR}));
        });
};



export const addCombo = (combo, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    await apiConfig.post(apiBaseURL.ADD_COMBO, combo)
        .then((response) => {
            dispatch({type: comboProductType.ADD_COMBO, payload: response.data.data});
            console.log('dd',response.data.data);
            dispatch(addToast({text: getFormattedMessage('combo.success.create.message')}));

            navigate('app/combo-products')
            // dispatch(addInToTotalRecord(1))
            dispatch(setSavingButton(false))
        })
        .catch(({response}) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};


export const fetchComboProduct = (productId, singleProduct, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
   await apiConfig.get(apiBaseURL.COMBO_PRODUCT_DETAIL + '/' + productId)

        .then((response) => {
            dispatch({type: comboProductType.FETCH_COMBO_PRODUCT, payload: response.data?.data});
            if (isLoading) {
                dispatch(setLoading(false))
            }

            console.log(response.data?.data, 'Come From Combo Action');
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data?.message, type: toastType.ERROR}));
        });
};






