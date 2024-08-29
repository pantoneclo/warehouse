import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { Image, Table } from 'react-bootstrap-v5';
import { json, useParams } from 'react-router-dom';
import Carousel from 'react-elastic-carousel';
import MasterLayout from '../MasterLayout';
import TabTitle from '../../shared/tab-title/TabTitle';
import { fetchProduct } from '../../store/action/productAction';
import HeaderTitle from '../header/HeaderTitle';
import user from '../../assets/images/brand_logo.png';
import { getFormattedMessage, placeholderText, currencySymbolHendling } from '../../shared/sharedMethod';
import Spinner from "../../shared/components/loaders/Spinner";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { vari } from './../../constants/index';
import { fetchProductAbstract } from './../../store/action/productAbstractAction';


const ProductAbstractDetail = props => {
    const { productAbstracts, fetchProductAbstract, isLoading, frontSetting, allConfigData } = props;
    const { id } = useParams();


    // const result = products && products?.reduce((obj, cur) => ({...obj, [cur.type]: cur}), {})
    //     const product = result.products
    //    if (productAbstracts ? productAbstracts.length > 0 : false) {
    //     const result = productAbstracts && productAbstracts?.reduce((obj, cur) => ({...obj, [cur.type]: cur}), {})
    //     return result ;
    //     //  console.log(productAbstracts&&productAbstracts[0] ,result ,'this is come from product abstract');
    //    }

    const result = productAbstracts ? productAbstracts[0] && productAbstracts?.reduce((obj, cur) => ({ ...obj, [cur.type]: cur }), {})
        : false
    console.log(result, 'this is come from product abstract');
    const product = result && result.product_abstracts;
    console.log(product, 'this is come from product');
    const products = product && product.attributes && product.attributes.products && product.attributes.products
    console.log(products, 'Rony this is come from products');
    console.log(productAbstracts, 'this is come from product');
    




    // const variants = product && product.attributes && product.attributes.variants && product.attributes.variants


    useEffect(() => {

        fetchProductAbstract(id);
    }, []);


    const sliderImage = product && product.attributes && product.attributes.images.imageUrls && product.attributes.images.imageUrls.map((img) => img)
    // const warehouse = product && product.attributes && product.attributes.warehouse && product.attributes.warehouse.map((item) => item)

    return (
        <MasterLayout>
            <TopProgressBar />
            <HeaderTitle title={getFormattedMessage('product.product-details.title')} to='/app/products' />
            <TabTitle title={placeholderText('product.product-details.title')} />
            <div className='card card-body'>
                <div className='row'>
                    {isLoading ?
                        <Spinner /> : <>
                            {/* <div className='col-md-12'>
                                <div className='d-inline-block text-center'>
                                    <Image
                                        src={product && product.attributes && product.attributes.barcode_url}
                                        alt={product && product.attributes && product.attributes.name}
                                        className='product_brcode'/>
                                    <div
                                        className='mt-3'>{product && product.attributes && product.attributes.code}</div>
                                </div>
                            </div> */}
                            <div className='col-xxl-7'>
                                <table className='table table-responsive gy-7'>
                                    <tbody>

                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('product.abstract.title.name')}</th>
                                            <td className='py-4'>{product && product.attributes && product.attributes.name}</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('globally.input.panStyle.label')}</th>
                                            <td className='py-4'>{product && product.attributes && product.attributes.pan_style}</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('product.product-details.category.label')}</th>
                                            <td className='py-4'> {product && product.attributes && product.attributes.product_category_name}</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('product.input.brand.label')}</th>
                                            <td className='py-4'>{product && product.attributes && product.attributes.brand_name}</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('base.cost.label')}</th>
                                            <td className='py-4'>{currencySymbolHendling(allConfigData, frontSetting.value && frontSetting.value.currency_symbol, product && product.attributes && product.attributes.base_cost)}</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('base.price.label')}</th>
                                            <td className='py-4'>{currencySymbolHendling(allConfigData, frontSetting.value && frontSetting.value.currency_symbol, product && product.attributes && product.attributes.base_price)}</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('product.product-details.unit.label')}</th>
                                            {product && product.attributes && product.attributes.product_unit_name &&
                                                <td className='py-4'><span className='badge bg-light-success'><span>{product.attributes.product_unit_name?.name}</span></span></td>}
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('product.product-details.tax.label')}</th>
                                            <td className='py-4'>{product && product.attributes && product.attributes.order_tax ? product.attributes.order_tax : 0} %</td>
                                        </tr>
                                        <tr>
                                            <th className='py-4' scope='row'>{getFormattedMessage('variant.input.variant-attr.label')}</th>
                                            <td className='py-4'>
                                                {product && product.attributes && product.attributes.attributes
                                                    ? Object.entries(product.attributes.attributes).map(([label, value]) => (
                                                        <div key={label}>
                                                            {`${label}: ${Array.isArray(value) ? value.join(", ") : value}`}
                                                        </div>
                                                    ))
                                                    : ''}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div className='col-xxl-5 d-flex justify-content-center m-auto'>
                                {sliderImage && sliderImage.length !== 0 ? <Carousel>
                                    {sliderImage.length !== 0 && sliderImage.map((img, i) => {
                                        return (<div key={i}>
                                            <Image src={img} width='413px' />
                                        </div>)
                                    })}
                                </Carousel> : <div>
                                    <Image src={user} width='413px' />
                                </div>}
                            </div>
                        </>}
                </div>
            </div>
            {/* {warehouse && warehouse.length !== 0 ?
             <div className='card card-body mt-2'>
             <div>
                <Table responsive="md">
                    <thead>
                        <tr>
                            <th>{getFormattedMessage('dashboard.stockAlert.warehouse.label')}</th>
                            <th>{getFormattedMessage('dashboard.stockAlert.quantity.label')}</th>
                        </tr>
                    </thead>
                    <tbody>
                       {warehouse && warehouse.map((item, index) => {
                        return(
                            <tr key={index}>
                                <td className='py-4'>{item.name}</td>
                                <td className='py-4'>
                                    <div>
                                        <div className='badge bg-light-info me-2'><span>{item.total_quantity}</span></div>
                                        {product.attributes.product_unit === '1' && <span className='badge bg-light-success me-2'><span>{getFormattedMessage("unit.filter.piece.label")}</span></span>
                                        || product.attributes.product_unit === '2' && <span className='badge bg-light-primary me-2'><span>{getFormattedMessage("unit.filter.meter.label")}</span></span>
                                        || product.attributes.product_unit === '3' && <span className='badge bg-light-warning me-2'><span>{getFormattedMessage("unit.filter.kilogram.label")}</span></span>}
                                    </div>
                                </td>
                            </tr>
                        )
                       })}
                    </tbody>
                </Table>
               </div>
                </div> : ''
             } */}
            {products && products.length !== 0 ?
                <div className='card card-body mt-2'>
                    <div>
                        <Table responsive="md">
                            <thead>
                                <tr>
                                {/* <th>Id</th> */}
                                    <th>Product name</th>
                                    <th>Variant</th>
                                    <th>Cost</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Code</th>
                                    <th>Barcode</th>

                                    <th>Image</th>
                                </tr>
                            </thead>
                            <tbody>
                                {products && products.map((item, index) => {
                                    const product = item?.data?.attributes

                                    const productImage = product?.images?.imageUrls && product?.images?.imageUrls.map((img) => img)
                                    const productVariant = product?.variant;
                                    console.log(product, 'productImage')
                                    console.log(item?.data, 'item')
                                    return (
                                        <tr key={index}>
                                            {/* <td className='py-4'>{item?.data?.id}</td> */}

                                            <td className='py-4'>{product?.name}</td>
                                            <td className='py-4'> {productVariant && productVariant
                                                ? Object.entries(productVariant).map(([label, value]) => (
                                                    <div key={label}>
                                                        {`${label}: ${Array.isArray(value) ? value.join(", ") : value}`}
                                                    </div>
                                                ))
                                                : ''}</td>
                                            <td>
                                                {product?.product_cost}
                                            </td>
                                            <td className='py-4'>{product?.product_price}</td>
                                            <td className='py-4'>{product?.in_stock}</td>
                                            <td className='py-4'>{product?.code}</td>
                                            <td className='py-2' >
                                                <Image width='200px' src={product?.barcode_url} />

                                            </td>
                                            <td className='py-4'>
                                                <div>

                                                    {productImage && productImage.length !== 0 ? <Carousel>
                                                        {productImage.length !== 0 && productImage.map((img, i) => {
                                                            console.log(img, 'img')
                                                            return (<div key={i}>
                                                                <Image src={img} height='200px' />
                                                            </div>)
                                                        })}
                                                    </Carousel> : <div>
                                                        <Image src={user} width='200px' />
                                                    </div>}

                                                </div>
                                            </td>
                                        </tr>
                                    )
                                })}
                            </tbody>
                        </Table>
                    </div>
                </div> : ''
            }
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    console.log(state)
    const { products, productAbstracts, isLoading, frontSetting, allConfigData } = state;
    return { products, productAbstracts, isLoading, frontSetting, allConfigData }
};

export default connect(mapStateToProps, { fetchProduct, fetchProductAbstract })(ProductAbstractDetail);



