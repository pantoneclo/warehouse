import React from 'react';
import CustomerDropDown from "../pos-dropdown/CustomerDropdown";
import WarehouseDropDown from "../pos-dropdown/WarehouseDropDown";
import CountryDropDown from "../pos-dropdown/CountryDropDown";

import {Button, Row} from "react-bootstrap-v5";


const PosHeader = (props) => {
    const {setSelectedCustomerOption,selectedCustomerOption, setSelectedOption, selectedOption, customerModel, updateCustomer,selectedCountry,
        setSelectedCountry} = props;

    return (
        <div className='top-nav my-3'>
            <Row className="align-items-center justify-content-between grp-select h-100">
                <CustomerDropDown setSelectedCustomerOption={setSelectedCustomerOption}
                                  selectedCustomerOption={selectedCustomerOption} customerModel={customerModel}
                                  updateCustomer={updateCustomer}/>

                <WarehouseDropDown setSelectedOption={setSelectedOption}
                                   selectedOption={selectedOption}/>

                <CountryDropDown
                    setSelectedOption={setSelectedCountry}
                    selectedOption={selectedCountry}
                />
            </Row>
        </div>
    )
};
export default PosHeader
