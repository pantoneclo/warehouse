import React, {useEffect} from 'react';
import {connect} from 'react-redux';
import {useParams} from 'react-router-dom';
import MasterLayout from '../MasterLayout';
import HeaderTitle from '../header/HeaderTitle';
import {editSale, fetchSale} from '../../store/action/salesAction';
import {fetchAllCustomer} from '../../store/action/customerAction';
import {fetchAllWarehouses} from '../../store/action/warehouseAction';
import {getFormattedMessage, getFormattedOptions} from '../../shared/sharedMethod';
import status from '../../shared/option-lists/status.json';
import paymentStatus from '../../shared/option-lists/paymentStatus.json';
import paymentType from '../../shared/option-lists/paymentType.json';
import Spinner from "../../shared/components/loaders/Spinner";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { saleStatusOptions } from '../../constants';
import PackageForm from './PackageForm';
import { fetchPackage,editPackage } from './../../store/action/packageAction';

const EditPackage = (props) => {
    const {fetchPackage, packages,warehouses, fetchAllWarehouses, isLoading} = props;
   
    const {id} = useParams();

    useEffect(() => {
      
        fetchAllWarehouses();    
        fetchPackage(id);
    }, []);
    
    console.log({packages} )
    packages.forEach(packages => {
        console.log(packages && packages.attributes.code, 'packages from edit package')
        
    });
    

    

    const warehouseId = packages && packages.attributes && packages.attributes.warehouse_id
    const warehouse = warehouses.filter((warehouse) => warehouse.id === warehouseId);
    console.log(warehouse, 'warehouse from edit package')
    const itemsValue = packages && packages.attributes && {
     
        warehouse_id: {
            value: packages.attributes.warehouse_id,
            label: packages.attributes.warehouse_name,
        },    
        grand_total: packages.attributes.grand_total,
        amount: packages.attributes.amount,
        code: 'bhfddfdbd',
        package_data: packages.attributes.package_data.map((item) => ({       
            variant_id: item.variant_id,
            product_id: item.product_id,           
            quantity: item.quantity,        
        
        })),
        id: packages.id,
        notes: packages.attributes.note,
       
       
    };
    console.log(itemsValue, 'itemsValue from edit package')

    return (
        <MasterLayout>
            <TopProgressBar/>
            <HeaderTitle title={getFormattedMessage('package.edit.title')} to='/app/packages'/>
            {isLoading ? <Spinner /> :
                <PackageForm singleSale={itemsValue} id={id}  warehouses={warehouses}/>}
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    console.log(state.packages , 'state from edit package')
    const {packages, warehouses, isLoading} = state;
    return {packages, warehouses, isLoading}
};

export default connect(mapStateToProps, {fetchPackage,editPackage, fetchAllCustomer, fetchAllWarehouses})(EditPackage);
