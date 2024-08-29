import React, { createRef, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';
import { Button, Image } from 'react-bootstrap-v5';
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
import usePermission from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
import { Form } from 'react-bootstrap-v5';
import ModelFooter from '../../shared/components/modelFooter';
import ReactSelect from '../../shared/select/reactSelect';
import { fetchAllWarehouses } from '../../store/action/warehouseAction';
import { addWarehouseToPackage } from '../../store/action/packageAction';
const Package = (props) => {
    const { products, fetchPackages, addWarehouseToPackage, packages, totalRecord, isLoading, warehouses, fetchAllWarehouses, frontSetting, fetchFrontSetting, allConfigData } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [isOpen, setIsOpen] = useState(false);
    const [lightBoxImage, setLightBoxImage] = useState([]);
    const [show, setShow] = useState(false);

    const [warehouseValue, setWarehouseValue] = useState({ warehouse_id: '', package_id: '', id: '' });
    const [errors, setErrors] = useState({ warehouse_id: '', package_id: '', id: '' });
    const [singleSelect, setSingleSelect] = useState('');

    const [importProduct, setimportProduct] = useState(false);
    const handleClose = () => {
        setimportProduct(!importProduct);
    };
    // close modal(add/edit) - state update

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
        fetchPackages();
        fetchAllWarehouses();
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
    //close modal
    const handleWarehouseShow = (item) => {
        // const item = item;
        // console.log({item});
        setWarehouseValue(inputs => ({ ...inputs, package_id: item.id }));
        setWarehouseValue(inputs => ({ ...inputs, id: item.package_vs_warehouse_id }));
        setWarehouseValue(inputs => ({ ...inputs, warehouse_id: { value: item.warehouse_id, label: item.warehouse_name } }));

        if (item.warehouse_id !== null) {
            setSingleSelect({ warehouse_id: { value: item.warehouse_id, label: item.warehouse_name } });

        }


        setShow(true);
    };
    console.log({ singleSelect });

    const handleWarehouseClose = () => {
        setShow(false);
    };

    const prepareData = (data) => {
     
        const formData = new FormData();
    
        formData.append('package_id',data.package_id);
        formData.append('warehouse_id', data.warehouse_id.value ? data.warehouse_id.value : '');
        formData.append('id', data.id ? data.id : '');

        return formData;
    };
    const handleValidation = () => {
        let errorss = {};
        let isValid = false;

        if (!warehouseValue?.warehouse_id.value) {
            errorss['warehouse_id'] = getFormattedMessage('purchase.select.warehouse.validate.label')
        }
        setErrors(errorss);
        return isValid;
    };

    const onSubmit = (e) => {
        e.preventDefault();
        handleValidation();
        const formValue = prepareData(warehouseValue);
        console.log({ formValue });
        addWarehouseToPackage(formValue);


        handleWarehouseClose();
    };
    const onWarehouseChange = (obj) => {
        setWarehouseValue(inputs => ({ ...inputs, warehouse_id: obj }));
        setErrors('');
    };

    console.log({ warehouseValue });

    const view_permission = usePermission(Permissions.PACKAGE_VIEW);
    const edit_permission = usePermission(Permissions.PACKAGE_EDIT);
    const delete_permission = usePermission(Permissions.PACKAGE_DELETE);
    const create_permission = usePermission(Permissions.PACKAGE_CREATE);

    const itemsValue = packages.length >= 0 && packages.map((packageS) => {
        return (
            {
                id: packageS?.id,
                code: packageS?.attributes.code,
                date: getFormattedDate(packageS?.attributes.created_at, allConfigData && allConfigData),
                time: moment(packageS?.attributes.created_at).format('LT'),
                view_permission: view_permission,
                delete_permission: delete_permission,
                package_vs_warehouse_id: packageS?.attributes.pacakgeVsWarehouseData[0]?.id ? packageS?.attributes.pacakgeVsWarehouseData[0]?.id : '',
                warehouse_id: packageS?.attributes.pacakgeVsWarehouseData[0]?.warehouse ? packageS?.attributes.pacakgeVsWarehouseData[0]?.warehouse.id : '',
                warehouse_name: packageS?.attributes.pacakgeVsWarehouseData[0]?.warehouse ? packageS?.attributes.pacakgeVsWarehouseData[0]?.warehouse.name : '',
            }
        )
    });
    console.log({ itemsValue });

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
            sortable: false,
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
                    isViewIcon={row.view_permission}
                    goToDetailScreen={goToProductDetailPage}
                    item={row}
                    goToEditProduct={goToEditProduct}
                    isEditMode={false}
                    onClickDeleteModel={onClickDeleteModel}
                    isDeleteMode={row.delete_permission}
                    isVariantAddMode={true}
                    isWarehouseAddIcon={true}
                    warehouseAdd={handleWarehouseShow}

                />
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('products.title')} />
            {/* <div>Hii {packages.length}</div> */}
            <Modal show={show} onHide={handleWarehouseClose} keyboard={true} >
                <Form onSubmit={onSubmit}>
                    <Modal.Header closeButton>
                        <Modal.Title>Add Warehouse</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {/* Your form fields go here */}
                        <div className='col-md-12 mb-3'>

                            <ReactSelect title={getFormattedMessage("globally.warehouse.add.label")}
                                data={warehouses}
                                onChange={onWarehouseChange}
                                placeholder={placeholderText('purchase.select.warehouse.placeholder.label')}
                                defaultValue={singleSelect.warehouse_id}
                                // value={singleSelect.warehouse_id}
                                name='warehouse_id'



                            />
                        </div>
                    </Modal.Body>
                    <Modal.Footer>
                        <button type="button " className='btn btn-danger' onClick={handleWarehouseClose}>
                            Close
                        </button>
                        <button type="submit" className='btn btn-success' disabled={
                            warehouseValue.warehouse_id?.value ? false : true
                        } >
                            Save Changes
                        </button>
                    </Modal.Footer>
                </Form>
            </Modal>
            <ReactDataTable columns={columns} items={itemsValue} onChange={onChange} isLoading={isLoading}
                ButtonValue={create_permission ? getFormattedMessage('package.create.title') : null} totalRows={totalRecord}
                to='#/app/packages/create' isShowFilterField={false} isUnitFilter={false}
                title={getFormattedMessage('product.input.product-unit.label')}
                buttonImport={false} goToImport={handleClose} importBtnTitle={getFormattedMessage(' product.import.title')}
                isExport={false} onExcelClick={onExcelClick} />
            <DeletePackage onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete} />
            {/* {isOpen && lightBoxImage.length !== 0 && <ProductImageLightBox setIsOpen={setIsOpen} isOpen={isOpen}
                lightBoxImage={lightBoxImage} />}
            {importProduct && <ImportProductModel handleClose={handleClose} show={importProduct} />} */}
        </MasterLayout>

    )
};

const mapStateToProps = (state) => {
    console.log(state);
    const { packages, totalRecord, warehouses, isLoading, frontSetting, allConfigData } = state;
    return { packages, totalRecord, warehouses, isLoading, frontSetting, allConfigData }
};

export default connect(mapStateToProps, { fetchPackages, fetchAllWarehouses, addWarehouseToPackage, fetchFrontSetting })(Package);

