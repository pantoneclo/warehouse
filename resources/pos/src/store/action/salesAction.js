import apiConfig from '../../config/apiConfig';
import {apiBaseURL, saleActionType, toastType} from '../../constants';
import {addToast} from './toastAction';
import {addInToTotalRecord, removeFromTotalRecord, setTotalRecord} from './totalRecordAction';
import {setLoading} from './loadingAction';
import requestParam from '../../shared/requestParam';
import {getFormattedMessage} from '../../shared/sharedMethod';
import {callSaleApi} from "./saleApiAction";
import {setSavingButton} from "./saveButtonAction";


export const fetchSales = (filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    const admin = true;
    let url = apiBaseURL.SALES;
    if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at || filter.customer_id || filter.country)) {
        url += requestParam(filter, admin);
    }
    await apiConfig.get(url)
        .then((response) => {
            dispatch({type: saleActionType.FETCH_SALES, payload: response.data.data});
            dispatch(setTotalRecord(response.data.meta.total));
            dispatch(callSaleApi(false))
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const fetchSale = (saleId, singleSale, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    await apiConfig.get(apiBaseURL.SALES + '/' + saleId + '/edit', singleSale)
        .then((response) => {
            dispatch({type: saleActionType.FETCH_SALE, payload: response.data.data})
            if (isLoading) {
                dispatch(setLoading(false));
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const addSale = (sale, navigate) => async (dispatch) => {
   dispatch(setSavingButton(true))
    await apiConfig.post(apiBaseURL.SALES, sale)
        .then((response) => {
            dispatch({type: saleActionType.ADD_SALE, payload: response.data.data});
            dispatch(addToast({text: getFormattedMessage('sale.success.create.message')}));
            dispatch(addInToTotalRecord(1));
            navigate('/app/sales');
            dispatch(setSavingButton(false))
        })
        .catch(({response}) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const editSale = (saleId, sale, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    await apiConfig.patch(apiBaseURL.SALES + '/' + saleId, sale)
        .then((response) => {
            dispatch(addToast({text: getFormattedMessage('sale.success.edit.message')}));
            navigate('/app/sales');
            dispatch({type: saleActionType.EDIT_SALE, payload: response.data.data});
            dispatch(setSavingButton(false))
        })
        .catch(({ response }) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const editSaleReport = (saleId, sale, navigate, isLoading=true) => async (dispatch) => {
    dispatch(setSavingButton(true))
    if (isLoading) {
        dispatch(setLoading(true))
    }
    await apiConfig.patch(apiBaseURL.SALES_REPORT + '/' + saleId, sale)
        .then((response) => {
            dispatch(addToast({text: getFormattedMessage('sale.report.success.edit.message')}));
            console.log('API Response:', response); 
            navigate('/app/report/report-sale');
            dispatch({type: saleActionType.EDIT_SALE_REPORT_FROM_REPORT, payload: response.data.data});
            dispatch(setSavingButton(false))
            if (isLoading) {
                dispatch(setLoading(false));
            }
        })
        .catch(({ response }) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};

export const deleteSale = (userId) => async (dispatch) => {
    await apiConfig.delete(apiBaseURL.SALES + '/' + userId)
        .then(() => {
            dispatch(removeFromTotalRecord(1));
            dispatch({type: saleActionType.DELETE_SALE, payload: userId});
            dispatch(addToast({text: getFormattedMessage('sale.success.delete.message')}));
        })
        .catch(({response}) => {
            response && dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};
