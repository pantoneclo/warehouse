import apiConfig from '../../config/apiConfigWthFormData';
import { apiBaseURL, Filters, packageActionType, toastType, Tokens } from '../../constants';
import { addToast } from './toastAction'
import { setTotalRecord, addInToTotalRecord, removeFromTotalRecord } from './totalRecordAction';
import requestParam from '../../shared/requestParam';
import { setLoading } from './loadingAction';
import { getFormattedMessage } from '../../shared/sharedMethod';
import { setSavingButton } from "./saveButtonAction";
import { callImportProductApi } from './importProductApiAction';
import axios from 'axios';


export const fetchPackages = (filter = {}, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    let url = apiBaseURL.PACKAGES;
    if (!_.isEmpty(filter) && (filter.page || filter.pageSize || filter.search || filter.order_By || filter.created_at)) {
        url += requestParam(filter);
    }
    apiConfig.get(url)
        .then((response) => {
            dispatch({ type: packageActionType.FETCH_PACKAGES, payload: response.data.data });
            dispatch(setTotalRecord(response.data.meta.total));
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({ response }) => {
            dispatch(addToast(
                { text: response.data.message, type: toastType.ERROR }));
        });
};
export const fetchPackage = (packageId, singlePackage, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    apiConfig.get(apiBaseURL.PACKAGES + '/' + packageId + '/edit', singlePackage)
        .then((response) => {
            dispatch({ type: packageActionType.FETCH_PACKAGE, payload: response.data.data });
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({ response }) => {
            dispatch(addToast(
                { text: response.data.message, type: toastType.ERROR }));
        });
};

export const addPackage = (Package, navigate) => async (dispatch) => {
    console.log(Package, 'package from action')
    dispatch(setSavingButton(true))
    await apiConfig.post(apiBaseURL.PACKAGES, Package)
        .then((response) => {
            dispatch({ type: packageActionType.ADD_PACKAGE, payload: response.data.data });
            dispatch(addToast({ text: getFormattedMessage('package.success.create.message') }));
            console.log('navigate to: ' + '/app/packages/details/' + response.data.data.id);

            dispatch(addInToTotalRecord(1))
            dispatch(setSavingButton(false))
            navigate(('/app/packages/'))
            const timeoutId = setTimeout(() => {
                // Navigate to a different route after 1 second
                navigate(('/app/packages/details/' + response.data.data.id))
            }, 1100);

        })
        .catch(({ response }) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                { text: response.data.message, type: toastType.ERROR }));
        });
};

export const editPackage = (packageId, Package, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true))
    apiConfig.post(apiBaseURL.PACKAGES + '/' + packageId, Package)
        .then((response) => {
            navigate('/app/packages')
            dispatch(addToast({ text: getFormattedMessage('package.success.edit.message') }));
            dispatch({ type: packageActionType.EDIT_PACKAGE, payload: response.data.data });
            dispatch(setSavingButton(false))
        })
        .catch(({ response }) => {
            dispatch(setSavingButton(false))
            dispatch(addToast(
                { text: response.data.message, type: toastType.ERROR }));
        });
};

export const deletePackage = (userId) => async (dispatch) => {
    await apiConfig.delete(apiBaseURL.PACKAGES + '/' + userId)
        .then(() => {
            dispatch(removeFromTotalRecord(1));
            dispatch({ type: packageActionType.DELETE_PACKAGE, payload: userId });
            dispatch(addToast({ text: getFormattedMessage('package.success.delete.message') }));
        })
        .catch(({ response }) => {
            response && dispatch(addToast(
                { text: response.data.message, type: toastType.ERROR }));
        });
};


export const fetchAllPackages = () => async (dispatch) => {
    apiConfig.get(`packages?page[size]=0`)
        .then((response) => {
            dispatch({ type: packageActionType.FETCH_ALL_PACKAGES, payload: response.data.data });
        })
        .catch(({ response }) => {
            dispatch(addToast(
                { text: response.data.message, type: toastType.ERROR }));
        });
};

export const addWarehouseToPackage = (data) => async (dispatch) => {

    dispatch(setSavingButton(true))
    try {
        const response = await apiConfig.post(apiBaseURL.ADD_WAREHOUSE_TO_PACKAGE, data);

        dispatch({ type: packageActionType.ADD_WAREHOUSE_TO_PACKAGES, payload: response.data.data });

        
        dispatch(addToast({ text: getFormattedMessage('package.success.add.warehouse.message') }));
        dispatch(setSavingButton(false))
        dispatch (fetchPackages () )
       

    } catch (error) {
        dispatch(setSavingButton(false))
        dispatch(addToast({ text: error.response.data.message, type: toastType.ERROR }));
    }
};




