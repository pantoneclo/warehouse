import React, {useEffect} from 'react';
import {ToastContainer} from 'react-toastify';
import PropTypes from 'prop-types';

const Toast = (props) => {
    const {onCancel, language} = props;

    useEffect(() => {
        setTimeout(() => onCancel(), 3000);
    }, []);

    return (
        <ToastContainer
            autoClose={3000}
            hideProgressBar={true}
            newestOnTop={true}
            closeOnClick
            rtl={language === "ar" ? true : false}
            draggable
            pauseOnHover
            pauseOnFocusLoss
        />
    );
};

Toast.propTypes = {
    onCancel: PropTypes.func,
};

export default Toast;
