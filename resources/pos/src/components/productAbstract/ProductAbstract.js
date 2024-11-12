import React, { useEffect, useState } from 'react';
import { connect, useDispatch } from 'react-redux';
import moment from 'moment';
import { Button, Image } from 'react-bootstrap-v5';
import MasterLayout from '../MasterLayout';
import { fetchProducts } from '../../store/action/productAction';
import { fetchproduct } from '../../store/action/packageAction';
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
import { allProductsExcelAction } from '../../store/action/productExcelAction';
import { fetchProductAbstracts, cancelTokenSourceProductAbstract } from '../../store/action/productAbstractAction';
import DeleteProductAbstract from './DeleteProductAbstract';

import usePermission from '../../shared/utils/usePermission';
import { Permissions } from '../../constants';
import axios from 'axios';

const ProductAbstract = (props) => {
    const dispatch = useDispatch();
    const { productAbstracts, fetchProductAbstracts, totalRecord, isLoading, frontSetting, fetchFrontSetting, allConfigData } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [isOpen, setIsOpen] = useState(false);
    const [lightBoxImage, setLightBoxImage] = useState([]);
    // console.log(productAbstracts?.attributes);


    const [importProduct, setimportProduct] = useState(false);
    const handleClose = () => {
        setimportProduct(!importProduct);
    };

    const [isWarehouseValue, setIsWarehouseValue] = useState(false);

    // Debug: Log when `isWarehouseValue` changes
    useEffect(() => {
        console.log("useEffect triggered, isWarehouseValue:", isWarehouseValue);
        if (isWarehouseValue) {
            console.log("I am Here"); // Should appear when `isWarehouseValue` is true
            dispatch(allProductsExcelAction(setIsWarehouseValue, true));
        }
    }, [isWarehouseValue, dispatch]);

    const onExcelClick = () => {
        console.log("onExcelClick called");
        setIsWarehouseValue(true); // Triggers `useEffect`
    };

    useEffect(() => {
        let cancelToken =  axios.CancelToken.source();

        fetchFrontSetting();
        fetchProductAbstracts({},true,cancelToken);

        // Cleanup function to cancel the dispatch when the component is unmounted
        return () => {
            if (isLoading) {
                cancelToken.cancel('Request canceled manually');
            }
        };

    }, []);

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchProductAbstracts(filter, true);
    };

    const goToEditProduct = (item) => {
        const id = item.id;
        window.location.href = '#/app/product/abstracts/edit/' + id;
    };

    const goToProductDetailPage = (productId) => {
        window.location.href = '#/app/product/abstracts/detail/' + productId;
    };



    const view_permission = usePermission(Permissions.PRODUCT_VIEW);
    const edit_permission = usePermission(Permissions.PRODUCT_EDIT);
    const delete_permission = usePermission(Permissions.PRODUCT_DELETE);
    const create_permission = usePermission(Permissions.PRODUCT_CREATE);



    const itemsValue = productAbstracts.length >= 0 && productAbstracts.map((product) => {

        return (
            {
                id: product?.id,
                code: product?.attributes.pan_style,
                name: product?.attributes.name,
                brand_name: product?.attributes.brand_name,
                category_name: product?.attributes.product_category_name,
                attributes: product?.attributes.attributes,
                date: getFormattedDate(product?.attributes.created_at, allConfigData && allConfigData),
                time: moment(product?.attributes.created_at).format('LT'),
                view_permission: view_permission,
                edit_permission: edit_permission,
                delete_permission: delete_permission,
                quantity:product?.attributes.total_quantity,
                

            }
        )
    });
    console.log(itemsValue,'item values')



    const columns = [

        // {
        //     name: getFormattedMessage("id"),
        //     selector: row => <span>{row.id}</span>,
        //     className: 'package-id',
        //     sortField: 'id',
        //     sortable: true,
        // },
        {
            name: getFormattedMessage('Style'),
            selector: row => <span className='badge bg-light-danger'>
                <span>{row.code}</span>
            </span>,
            sortField: 'code',
            sortable: false,
        },
        {
            name: getFormattedMessage('name'),
            selector: row => <span className='product-name'>
                <span>{row.name}</span>
            </span>,
            sortField: 'code',
            sortable: false,
        },
        {
            name: getFormattedMessage("product-category.title"),
            selector: row => <span>{row.category_name}</span>,
            sortField: 'category_name',
            sortable: false,
        },
        {
            name: getFormattedMessage('product-brand.name'),
            selector: row => <span className='badge bg-light-danger'>
                <span>{row.brand_name}</span>
            </span>,
            sortField: 'brand_name',
            sortable: false,
        },
       
        
        {
            name: getFormattedMessage('quantity.lable'),
            selector: row => <span className='badge bg-light-danger'>
                <span>{row.quantity}</span>
            </span>,
            sortField: 'code',
            sortable: false,
        },
        // {
        //     name: getFormattedMessage("brand.title"),
        //     selector: row => <span>{row.brand_name}</span>,
        //     sortField: 'brand_name',
        //     sortable: true,
        // },
        // {
        //     name: getFormattedMessage("product.abstract.attribute"),
        //     selector: row => <span>{Object.entries(row.attributes).map((item,index)=>(
        //         <span>{index}</span>)
        //     )}</span>,
        //     sortField: 'brand_name',
        //     sortable: false,
        // },

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

            cell: row => {

                return (
                    <ActionButton
                        isViewIcon={row.view_permission}
                        isDeleteMode={row.delete_permission}
                        isEditMode={row.edit_permission}
                        goToDetailScreen={goToProductDetailPage}
                        item={row}
                        goToEditProduct={goToEditProduct}
                        onClickDeleteModel={onClickDeleteModel}
                        isVariantAddMode={true}
                    />
                );


            },
        }
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('products.title')} />
            {/* <div>Hii {product.length}</div> */}
            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                isLoading={isLoading}
                ButtonValue={create_permission ? getFormattedMessage('product.create.title') : null}
                totalRows={totalRecord}
                to='#/app/product/abstracts/create'
                isShowFilterField={false}
                isUnitFilter={false}

                title={getFormattedMessage('product.input.product-unit.label')}
                buttonImport={false}

                goToImport={handleClose}
                importBtnTitle={getFormattedMessage('product.import.title')}
                isExport={false} onExcelClick={onExcelClick} isEXCEL/>


            <DeleteProductAbstract onClickDeleteModel={onClickDeleteModel} deleteModel={deleteModel} onDelete={isDelete} />
            {/* {isOpen && lightBoxImage.length !== 0 && <ProductImageLightBox setIsOpen={setIsOpen} isOpen={isOpen}
                lightBoxImage={lightBoxImage} />} */}
            {/* {importProduct && <ImportProductModel handleClose={handleClose} show={importProduct} />}  */}
        </MasterLayout>


    )
};

const mapStateToProps = (state) => {
    console.log(state);
    const { productAbstracts, totalRecord, isLoading, frontSetting, allConfigData } = state;
    return { productAbstracts, totalRecord, isLoading, frontSetting, allConfigData }
};

export default connect(mapStateToProps, { fetchProductAbstracts, fetchFrontSetting })(ProductAbstract);

