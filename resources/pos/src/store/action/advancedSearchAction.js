import apiConfig from '../../config/apiConfig';
import {apiBaseURL, toastType, advancedSearchActionType} from '../../constants';
import requestParam from '../../shared/requestParam';
import {addToast} from './toastAction'
import {setTotalRecord, addInToTotalRecord, removeFromTotalRecord} from './totalRecordAction';
import {setLoading} from './loadingAction';
import {getFormattedMessage} from '../../shared/sharedMethod';

export const fetchAdvancedSearch = (params) => async (dispatch) => {
    dispatch(setLoading(true));

    try {
        const response = await apiConfig.get(apiBaseURL.ADVANCED_SEARCH, { params });
        
        console.log("Response from API:", response);

        if (response && response.data && response.data.data) {
            dispatch({ type: advancedSearchActionType.FETCH_ADVANCED_SEARCH, payload: response.data.data });
            dispatch(setTotalRecord(response.data.data.length));
        } else {
            dispatch({ type: advancedSearchActionType.FETCH_ADVANCED_SEARCH, payload: [] });
            dispatch(setTotalRecord(0));
        }

        dispatch(setLoading(false));
    } catch (error) {
        console.log("API request failed with an error:", error);
        dispatch({ type: advancedSearchActionType.FETCH_ADVANCED_SEARCH, payload: [] });
        dispatch(setTotalRecord(0));
        dispatch(setLoading(false));
    }
}


