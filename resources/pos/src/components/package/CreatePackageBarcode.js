import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import { Button, Form, Image } from 'react-bootstrap-v5';
import MasterLayout from '../MasterLayout';
import { fetchProducts } from '../../store/action/productAction';
import { fetchPackages } from '../../store/action/packageAction';
import ReactDataTable from '../../shared/table/ReactDataTable';
// import DeleteProduct from './DeleteProduct';
import TabTitle from '../../shared/tab-title/TabTitle';
// import ProductImageLightBox from './ProductImageLightBox';
import user from '../../assets/images/brand_logo.png';
import { getFormattedDate, getFormattedMessage, placeholderText, currencySymbolHendling } from '../../shared/sharedMethod';
import ActionButton from '../../shared/action-buttons/ActionButton';
import { fetchFrontSetting } from '../../store/action/frontSettingAction';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import Modal from 'react-bootstrap/Modal';
// import ImportProductModel from './ImportProductModel';
import { productExcelAction } from '../../store/action/productExcelAction';
import DeletePackage from './DeletePackage';
// import DeletePackage from './DeletePackage';

import { fetchAllPackages } from '../../store/action/packageAction';
import ModelFooter from '../../shared/components/modelFooter';
import Printable from './Printable';
import { useReactToPrint } from 'react-to-print';


const CreatePackageBarcode = (props) => {
    const { products, fetchPackages,fetchAllPackages, packages, totalRecord, isLoading, frontSetting, fetchFrontSetting, allConfigData } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [isOpen, setIsOpen] = useState(false);
    const [lightBoxImage, setLightBoxImage] = useState([]);


    const [importProduct, setimportProduct] = useState(false);
    const handleClose = () => {
        setimportProduct(!importProduct);
    };

    const [isWarehouseValue, setIsWarehouseValue] = useState(false);
    useEffect(() => {
        if (isWarehouseValue === true) {
            productExcelAction(setIsWarehouseValue, true, productUnitId);
        }
    }, [isWarehouseValue])

    const onExcelClick = () => {
        setIsWarehouseValue(true);
    };

    useEffect(() => {
        fetchFrontSetting();
        // fetchPackages();
        fetchAllPackages();
    }, []);

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchPackages(filter, true);
    };

    const goToEditProduct = (item) => {
        const id = item.id;
        window.location.href = '#/app/packages/edit/' + id;
    };

    const goToProductDetailPage = (packageId) => {
        window.location.href = '#/app/packages/details/' + packageId;
    };

    const itemsValue = packages.length >= 0 && packages.map((packageS) => {
        return (
            {
                id: packageS?.id,
                code: packageS?.attributes.code,
                date: getFormattedDate(packageS?.attributes.created_at, allConfigData && allConfigData),
                time: moment(packageS?.attributes.created_at).format('LT'),
            }
        )
    });
    const [fromValue, setFromValue] = useState('');
    const [toValue, setToValue] = useState('');
    const [filterData ,setFilterData] = useState([]);
    const [printPcs,setPrintPcs] = useState(2)
    const handleFromChange = (event) => {
      setFromValue(event.target.value);
    };

    const handleToChange = (event) => {
      setToValue(event.target.value);
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        // You can now use 'fromValue' and 'toValue' in your application logic
        console.log('From:', fromValue);
        console.log('To:', toValue);
        const filteredData = packages.filter((item) => item.id <= toValue && item.id >= fromValue);
        setFilterData(filteredData);
        console.log(filterData);
      };
    const columns = [

        {
            name: getFormattedMessage("id"),
            selector: row => <span>{row.id}</span>,
            className: 'package-id',
            sortField: 'id',
            sortable: true,
        },
        {
            name: getFormattedMessage('package.code'),
            selector: row => <span className='badge bg-light-danger'>
                <span>{row.code}</span>
            </span>,
            sortField: 'code',
            sortable: true,
        },
        {
            name: getFormattedMessage('globally.react-table.column.created-date.label'),
            selector: row => row.date,
            sortField: 'created_at',
            sortable: true,
            cell: row => {
                return (
                    <span className='badge bg-light-info'>
                        <div className='mb-1'>{row.time}</div>
                        {row.date}
                    </span>
                )
            }
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            width: '120px',
            cell: row =>
             <ActionButton
            isViewIcon={true}
            goToDetailScreen={goToProductDetailPage}
            item={row}
            goToEditProduct={goToEditProduct}
            isEditMode={false}
            onClickDeleteModel={onClickDeleteModel}

            isVariantAddMode={true}

                 />
        }
    ];
    const componentRef = useRef();
    const addOneMore = () =>{
        setPrintPcs(printPcs+1)
    }

    const handlePrint = useReactToPrint({
        content: () => componentRef.current,
    });
    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('products.title')} />
            {/* <div>Hii {packages.length}</div> */}
            <div className='card'>
                <div className='card-body'>
                    <Form>
                        <div className='row'>
                            <div className='col-xl-12'>
                                <div className='card'>
                                    <div className='row'>
                                        <div className='col-md-6 mb-3'>
                                            <label
                                                className='form-label'>{getFormattedMessage('from.id')}: </label>
                                            <span className='required'/>
                                            <input type='text' name='name'  value={fromValue} onChange={handleFromChange}
                                                   placeholder={placeholderText('globally.input.id.placeholder.label')}
                                                   className='form-control' autoFocus={true} 
                                                //    onChange={(e) => onChangeInput(e)}
                                                   />
                                            {/* <span
                                                className='text-danger d-block fw-400 fs-small mt-2'>{errors['name'] ? errors['name'] : null}</span> */}
                                        </div>
                                        <div className='col-md-6 mb-3'>
                                            <label
                                                className='form-label'>{getFormattedMessage('to.id')}: </label>
                                            <span className='required'/>
                                            <input type='text' name='name' value={toValue} onChange={handleToChange}
                                                   placeholder={placeholderText('globally.input.id.placeholder.label')}
                                                   className='form-control' 
                                                //    onChange={(e) => onChangeInput(e)}
                                                   />
                                            {/* <span
                                                className='text-danger d-block fw-400 fs-small mt-2'>{errors['name'] ? errors['name'] : null}</span> */}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <ModelFooter  onSubmit={handleSubmit} />
                        </div>
                    </Form>
                    <div className='card card-body'>
                <div className='row text-center'>
                    {isLoading ?
                        <Spinner /> : <>
                        <div className='fs-3' ref={componentRef}> 
                        {
                          filterData&& filterData?.length==0? '':  filterData.map((item ) => {
                                return (
                                    <Printable key={Math.random()}  product={item} variants={item?.attributes?.package_data }  frontSetting={frontSetting} pcs={2}  />
                               )
                            })

                        }
                        </div>

                            <div className='d-inline my-5'>
                                <button type='button' className='btn btn-success me-5 mb-2' onClick={addOneMore}>Add One More</button>
                                <button type='button' className='btn btn-primary me-5 mb-2' onClick={handlePrint}>Print</button>
                            </div>
                        </>
                    }
                </div>
            </div>
                </div>
            </div>

        </MasterLayout>

    )
};

const mapStateToProps = (state) => {
    console.log(state);
    const { packages ,totalRecord, isLoading, frontSetting, allConfigData } = state;
    return { packages ,totalRecord, isLoading, frontSetting, allConfigData }
};

export default connect(mapStateToProps, { fetchPackages, fetchFrontSetting , fetchAllPackages })(CreatePackageBarcode);

