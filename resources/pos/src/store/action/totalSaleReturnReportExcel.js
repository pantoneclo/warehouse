import apiConfig from '../../config/apiConfig';
import {toastType} from '../../constants';
import {addToast} from './toastAction';
import {setLoading} from './loadingAction';

export const totalSaleReturnReportExcel = (dates, setIsWarehouseValue, filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    await apiConfig.get(`total-sales-return-report-excel?start_date=${dates.start_date ? dates.start_date : null }&end_date=${dates.end_date ? dates.end_date : null}`)
        .then((response) => {
            window.open(response.data.data.total_sale_return_excel_url, '_blank');
            setIsWarehouseValue(false);
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};
