import { apiBaseURL, settingActionType, toastType } from "../../constants";
import requestParam from "../../shared/requestParam";
import apiConfig from "../../config/apiConfig";
import { addToast } from "./toastAction";
import { setLoading } from "./loadingAction";
import { getFormattedMessage } from "../../shared/sharedMethod";
import { fetchConfig } from "./configAction";
import { fetchFrontSetting } from "./frontSettingAction";
import { setDateFormat } from "./dateFormatAction";
import { setDefaultCountry } from "../defaultCountryAction";

export const fetchSetting =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        let url = apiBaseURL.SETTINGS;
        if (!_.isEmpty(filter) && (filter.page || filter.pageSize)) {
            url += requestParam(filter);
        }
        apiConfig
            .get(url)
            .then((response) => {
                dispatch({
                    type: settingActionType.FETCH_SETTING,
                    payload: response.data.data,
                });
                if (isLoading) {
                    dispatch(setLoading(false));
                }
                response &&
                    dispatch(
                        setDateFormat(response.data.data.attributes.date_format)
                    );
                response &&
                    dispatch(
                        setDefaultCountry({
                            countries: response.data.data.attributes.countries,
                            country: response.data.data.attributes.country,
                        })
                    );
            })
            .catch(({ response }) => {
                dispatch(
                    addToast({
                        text: response?.data.message,
                        type: toastType.ERROR,
                    })
                );
            });
    };

export const editSetting =
    (setting, isLoading = true, setDefaultDate) =>
    async (dispatch) => {
        //console.log("load api");
        if (isLoading) {
            dispatch(setLoading(true));
        }
        apiConfig
            .post(apiBaseURL.SETTINGS, setting)
            .then(async (response) => {
                console.log(response);
                // dispatch({type: settingActionType.EDIT_SETTINGS, payload: response.data.data});


                //dispatch(fetchConfig());
                // console.log('1     ',response);
                // if (isLoading) {
                //     dispatch(setLoading(false));
                // }
                // response &&
                //     dispatch(
                //         setDateFormat(response.data.data.attributes.date_format)
                //     );
                //     console.log('2     ',response);
                // response &&
                //     dispatch(
                //         setDefaultCountry({
                //             countries: response.data.data.attributes.countries,
                //             country: response.data.data.attributes.country,
                //         })
                //     );
                    // console.log('3     ',response);
                //response && await dispatch(fetchSetting());
                //console.log('4     ',response);

                dispatch({
                    type: settingActionType.EDIT_SETTINGS,
                    payload: response.data.data,
                });

                if (isLoading) {
                    dispatch(setLoading(false));
                }

                dispatch(
                    addToast({
                        text: getFormattedMessage(
                            "settings.success.edit.message"
                        ),
                    })
                );

                dispatch(fetchFrontSetting());
            })
            .catch(({ response }) => {
                console.log('fail: ',response);
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };

export const fetchCacheClear = () => async (dispatch) => {
    apiConfig
        .get(apiBaseURL.CACHE_CLEAR)
        .then((response) => {
            dispatch({
                type: settingActionType.FETCH_CACHE_CLEAR,
                payload: response.data.message,
            });
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "settings.clear-cache.success.message"
                    ),
                })
            );
            window.location.reload();
        })
        .catch(({ response }) => {
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const fetchState = (id) => async (dispatch) => {
    apiConfig
        .get("states/" + id)
        .then((response) => {
            dispatch({ type: "FETCH_STATE_DATA", payload: response.data.data });
        })
        .catch(({ response }) => {
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const variantListUpdate =
    (data, isLoading = true) =>
    async (dispatch) => {
        console.log(data, "this is come from redux data");
        if (isLoading) {
            dispatch(setLoading(true));
        }
        apiConfig
            .post(apiBaseURL.POSSIBLE_VARIANT_UPDATE, data)
            .then((response) => {
                console.log(response, "res");
                dispatch(
                    addToast({
                        text: getFormattedMessage(
                            "settings.success.edit.message"
                        ),
                    })
                );
                dispatch({
                    type: settingActionType.POSSIBLE_VARIANT_UPDATE,
                    payload: response.data.data,
                });
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch(({ response }) => {
                console.log(response);
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };
