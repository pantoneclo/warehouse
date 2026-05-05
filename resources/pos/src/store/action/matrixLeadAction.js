import {matrixLeadActionType, apiBaseURL, constants} from '../../constants';
import apiConfig from '../../config/apiConfig';
import {setLoading} from './loadingAction';
import {addToast} from './toastAction';
import {toastType} from '../../constants';
import requestParam from '../../shared/requestParam';

export const fetchMatrixLeads = (filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true));
    }
    let url = apiBaseURL.MATRIX_LEADS;
    url += requestParam(filter);

    apiConfig.get(url)
        .then((response) => {
            dispatch({type: matrixLeadActionType.FETCH_MATRIX_LEADS, payload: response.data.data.data});
            dispatch({type: constants.SET_TOTAL_RECORD, payload: response.data.data.total});
            if (isLoading) {
                dispatch(setLoading(false));
            }
        })
        .catch(({response}) => {
            dispatch(addToast({text: response.data.message, type: toastType.ERROR}));
            if (isLoading) {
                dispatch(setLoading(false));
            }
        });
};

export const updateMatrixLeadStatus = (id, status) => async (dispatch) => {
    dispatch(setLoading(true));
    apiConfig.post(apiBaseURL.MATRIX_LEADS + '/' + id + '/status', {status})
        .then((response) => {
            dispatch({type: matrixLeadActionType.UPDATE_MATRIX_LEAD_STATUS, payload: response.data.data});
            dispatch(addToast({text: response.data.message, type: toastType.SUCCESS}));
            dispatch(setLoading(false));
        })
        .catch(({response}) => {
            dispatch(addToast({text: response.data.message, type: toastType.ERROR}));
            dispatch(setLoading(false));
        });
};
