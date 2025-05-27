import React, { useEffect } from 'react';
import { InputGroup } from 'react-bootstrap-v5';
import Select from 'react-select';
import { countryOptions } from '../../../constants';
import { getFormattedMessage } from '../../../shared/sharedMethod';

const CountryDropDown = ({ setSelectedOption, selectedOption }) => {
    const countryNamesFormatted = countryOptions.map((option) => ({
        value: option.code,
        label: option.name,
        vat: option.vat,
        currency: option.currency,
    }));

    const onChangeCountry = (selected) => {
        setSelectedOption(selected);
    };

    return (
        <div className='select-box col-6 ps-sm-2 position-relative'>
            <InputGroup>
                <InputGroup.Text id='basic-addon1' className='bg-transparent position-absolute border-0 z-index-1 input-group-text py-4 px-3'>
                    <i className="bi bi-globe fs-3 text-gray-900" />
                </InputGroup.Text>
                <Select
                    placeholder='Choose Country'
                    value={selectedOption}
                    onChange={onChangeCountry}
                    options={countryNamesFormatted}
                    noOptionsMessage={() => getFormattedMessage('no-option.label')}
                />
            </InputGroup>
        </div>
    );
};

export default CountryDropDown;
