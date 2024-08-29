import React, {useEffect, useState} from 'react';
import {Form} from 'react-bootstrap-v5';
import Select from 'react-select';
import {productActionType} from "../../constants";
import {useDispatch, useSelector} from "react-redux";
import { getFormattedMessage } from '../sharedMethod';
import { fetchProducts } from '../../store/action/InventoryAction'; // Adjust the path as necessary

const ReactSelectInventory = (props) => {
    const {title, placeholder, data, defaultValue, onChange, errors, value, isRequired, multiLanguageOption, isWarehouseDisable, addSearchItems} = props;
    const dispatch = useDispatch();
    const isOptionDisabled = useSelector((state) => state.isOptionDisabled);
    const [loading, setLoading] = useState(true);

    const option = data ? data?.map((da) => {
        const size = da.attributes.variant?.size || '';
        const color = da.attributes.variant?.color || '';
        return {
            value: da.value ? da.value : da.id,
            label: da.label ? da.label : da.attributes.symbol ? da.attributes.symbol :  da.attributes.pan_style +'-' + size + '-' + color,
            variant: da.attributes.variant,
            variant_id: da.attributes.variant_id,
            style: da.attributes.pan_style
        }
    }) : multiLanguageOption?.map((option) => {
        console.log('option',option)
        return {
            value: option.id,
            label: option.name,
        }
    })
    const [options, setOptions] = useState([]);
    const formatOptions = (data) => {
        return data?.map((da) => {
            const size = da.attributes.variant?.size || '';
            const color = da.attributes.variant?.color || '';
            return {
                value: da.value ? da.value : da.id,
                label: da.label ? da.label : da.attributes.symbol ? da.attributes.symbol : da.attributes.pan_style + '-' + size + '-' + color
            }
        }) || multiLanguageOption?.map((option) => {
            return {
                value: option.id,
                label: option.name
            }
        });
    };

    useEffect(() => {
        if (data) {
            setLoading(false);
        }
        addSearchItems ? dispatch({type: 'DISABLE_OPTION', payload: true}) : dispatch({type: 'DISABLE_OPTION', payload: false})
    }, [data]);

    const handleInputChangeWithApiCall = (inputValue) => {
        if (inputValue.length > 2) {
            dispatch(fetchProducts({ search: inputValue }, false))
                .then((response) => {
                    setOptions(formatOptions(response.data));
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                });
        }
    };

    return (
        <Form.Group className='form-group w-100' controlId='formBasic'>
            {title ? <Form.Label>{title} :</Form.Label> : ''}
            {loading ? ( // Step 3: Conditionally render loader or select component
                <div>Loading...</div>
            ) : (
                ''
            )}
            <Select
                placeholder={placeholder}
                value={value}
                defaultValue={defaultValue}
                onChange={onChange}
                onInputChange={handleInputChangeWithApiCall}
                options={option}
                noOptionsMessage={() => getFormattedMessage('no-option.label')}
                isDisabled={isWarehouseDisable ? isOptionDisabled : false}
            />

            { errors ? <span className='text-danger d-block fw-400 fs-small mt-2'>{errors ? errors : null}</span> : null}
        </Form.Group>
    )
};
export default ReactSelectInventory;
