import {setLoading} from './loadingAction';
import apiConfig from '../../config/apiConfig';
import {apiBaseURL, toastType} from '../../constants';
import {addToast} from './toastAction';

export const salePdfAction = (saleId, isLoading = true) => async (dispatch) => {
    if (isLoading) {
        dispatch(setLoading(true))
    }
    apiConfig.get(apiBaseURL.SALE_PDF + '/' + saleId)
        .then((response) => {
            window.open(response.data.data.sale_pdf_url, '_blank');
            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
        });
};



export const saleInvoiceAction = (saleId, isLoading = true) => async (dispatch) => {

    if (isLoading) {
        dispatch(setLoading(true))
    }
    apiConfig.get(apiBaseURL.SALE_INVOICE_DOWNLOAD + '/' + saleId)
        .then(async (response) => {

            // window.open(response.data.data.sale_invoice_url, '_blank');

            try{
                const invoiceRes = await axios.get(
                    response.data.data.sale_invoice_url,
                    {
                        headers: {
                            'Access-Control-Allow-Origin': '*',
                            // 'content-type': 'application/pdf',
                        },
                        responseType: "blob"
                    }
                )

                console.log('invoiceRes:::', invoiceRes)


                const blob = new Blob([invoiceRes?.data], { type: "application/pdf" });

                const pdfUrl = URL.createObjectURL(blob);

                // download via anchor tag
                const link = document.createElement("a");
                link.href = pdfUrl;
                let fileName = `invoice_${response.data.data?.invoice_no}.pdf`;
                if(response.data.data?.country){
                    fileName = `${response.data.data?.country}_invoice_${response.data.data?.invoice_no}.pdf`;
                }
                link.download = fileName;
                document.body.appendChild(link);
                link.click();

                // clean up this link
                document.body.removeChild(link);
                URL.revokeObjectURL(pdfUrl);
            }
            catch (e) {
                console.log('err:::', e)
            }


            if (isLoading) {
                dispatch(setLoading(false))
            }
        })
        .catch(({response}) => {
            console.log('err response:::', response)
            dispatch(addToast(
                {text: response?.data?.message, type: toastType.ERROR}));
        });
};

