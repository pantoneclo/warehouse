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
import { vari } from '../../constants/index';
import { fetchComboProduct } from '../../store/action/comboProductAction';


const ComboProductDetail = props => {
    const { combos, fetchComboProduct, isLoading, frontSetting, allConfigData } = props;
    const { id } = useParams();
    const result = combos ? combos[0] && combos?.reduce((obj, cur) => ({ ...obj, [cur.type]: cur }), {})
        : false
    console.log(result, 'this is come from Combo Product Details');
    const combo = result && result.combos;
    console.log(combo, 'this is Combo');
    const productsArray = combo && combo.attributes?.products;

    useEffect(() => {

        fetchComboProduct(id);
    }, []);

    return (
        <MasterLayout>
            <TopProgressBar />
            <HeaderTitle title={getFormattedMessage('combo.combo-details.title')} to='/app/combo-products' />
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
                                            <th className='py-4' scope='row'>{getFormattedMessage('combo.combo-details.title.name')}</th>
                                            <td className='py-4'>{combo && combo.attributes && combo.attributes.name}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </>}
                </div>
            </div>



            {productsArray && productsArray.length > 0 ? (
                productsArray.map((comboItem, index) => (
                    <div className='card card-body mt-2' key={index}>
                        <h2>Code: {comboItem.combo_code}</h2>
                        <Table responsive="md">
                            <thead>
                                <tr>
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
                                {comboItem.products.map((productItem, prodIndex) => {
                                    const product = productItem.attributes;
                                    const productVariant = product.variant;
                                    const productImage = product?.images?.imageUrls && product?.images?.imageUrls.map((img) => img)

                                    return (
                                        <tr key={prodIndex}>
                                            <td className='py-4'>{product.name}</td>
                                            <td className='py-4'>
                                                {productVariant && Object.entries(productVariant.variant).map(([label, value]) => (
                                                    <div key={label}>{`${label}: ${value}`}</div>
                                                ))}
                                            </td>
                                            <td>{product.product_cost}</td>
                                            <td className='py-4'>{product.product_price}</td>
                                            <td className='py-4'>{product.stock.quantity}</td>
                                            <td className='py-4'>{product.code}</td>
                                            <td className='py-2'>
                                                <Image width='200px' src={product.barcode_image_url} />
                                            </td>
                                            <td className='py-4'>
                                                <div>
                                                    {productImage && productImage.length !== 0 ? (
                                                        <Carousel>
                                                            {productImage.map((img, i) => (
                                                                <div key={i}>
                                                                    <Image src={img} height='200px' />
                                                                </div>
                                                            ))}
                                                        </Carousel>
                                                    ) : (
                                                        <div>
                                                            <Image src={user} width='200px' />
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </Table>
                    </div>
                ))
            ) : (
                <p>No products available</p>
            )}
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    console.log(state)
    const { productsArray, combos, isLoading, frontSetting, allConfigData } = state;
    return { productsArray, combos, isLoading, frontSetting, allConfigData }
};

export default connect(mapStateToProps, { fetchProduct, fetchComboProduct })(ComboProductDetail);



