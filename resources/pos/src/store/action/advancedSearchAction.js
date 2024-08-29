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
        
        console.log("Response from API:", response); // Add this line to log the response

        if (response && response.data.data) {
            dispatch({ type: advancedSearchActionType.FETCH_ADVANCED_SEARCH, payload: response.data.data });
           dispatch(setTotalRecord(response.data.data.length)); // Only if the total record is not available in the API response.
        } else {
            console.log("API response did not meet success condition."); // Add this line for debugging
            dispatch(setTotalRecord(0)); // Handle the case when there is no data.
        }

        dispatch(setLoading(false));
    } catch (error) {
        console.log("API request failed with an error:", error); // Add this line to log the error
        dispatch(setLoading(false));
        dispatch(addToast({
            text: error.response.data.message,
            type: toastType.ERROR
        }));
    }
}


