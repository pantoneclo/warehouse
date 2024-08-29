import React, {useEffect} from 'react';
import {connect} from 'react-redux';
import {useNavigate} from 'react-router-dom';
import PackageForm from './PackageForm';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import {addSale} from '../../store/action/salesAction';
import {fetchAllCustomer} from '../../store/action/customerAction';
import {fetchAllWarehouses} from '../../store/action/warehouseAction';
import {getFormattedMessage} from '../../shared/sharedMethod';
import { addPackage } from './../../store/action/packageAction';

const CreatePackage = (props) => {
    const {addSale,addPackage, customers, fetchAllCustomer, warehouses, fetchAllWarehouses} = props;
    const navigate = useNavigate();

    useEffect(() => {
        fetchAllCustomer();
        fetchAllWarehouses();
    }, []);

    const addPackageData = (formValue) => {
        addPackage(formValue, navigate);
    };

    return (
        <MasterLayout>
            <HeaderTitle title={getFormattedMessage('package.create.title')} to='/app/packages'/>
            <PackageForm addPackageData={addPackageData} customers={customers} warehouses={warehouses}/>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {customers, warehouses, totalRecord} = state;
    return {customers, warehouses, totalRecord}
};

export default connect(mapStateToProps, {addSale,addPackage, fetchAllCustomer, fetchAllWarehouses})(CreatePackage);
