import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import { Image, Table } from 'react-bootstrap-v5';
import { useParams } from 'react-router-dom';
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
import { fetchPackage } from './../../store/action/packageAction';
import { useReactToPrint } from 'react-to-print';
import Printable from './Printable';


const PackageDetail = props => {
    const { packages, fetchPackage, fetchProduct, isLoading, frontSetting, allConfigData } = props;
    const { id } = useParams();
    const result = packages && packages.reduce((obj, cur) => ({ ...obj, [cur.type]: cur }), {})
    const product = result.packages
    const componentRef = useRef();
    const [printPcs,setPrintPcs] = useState(2)

    const variants = product?.attributes?.package_data;
    const pacakgeVsWarehouseData = product?.attributes?.pacakgeVsWarehouseData;
    console.log({ product });
    console.log({ frontSetting });
    useEffect(() => {
        // document.title=id;
        fetchPackage(id);
        // componentRef.current = document.getElementById('printablediv').innerHTML;
        // console.log({componentRef});
    }, []);

    const addOneMore = () =>{
        setPrintPcs(printPcs+1)
    }

    const handlePrint = useReactToPrint({
        content: () => componentRef.current,
    });

    // const sliderImage = product && product.attributes && product.attributes.images.imageUrls && product.attributes.images.imageUrls.map((img) => img)
    const warehouse = product && product.attributes && product.attributes.pacakgeVsWarehouseData && product.attributes.pacakgeVsWarehouseData.warehouse && product.attributes.warehouse.map((item) => item)
    console.log(product,'pan')
    return (
        <MasterLayout>
            <TopProgressBar />
            <HeaderTitle title={getFormattedMessage('package.package-details.title')} to='/app/packages' />
            {/* <HeaderTitle title={'Package Details '+id} to='/app/packages' /> */}
            <TabTitle title={placeholderText('package.package-details.title')} />
            <div className='card card-body'>
                <div className='row text-center'>
                    {isLoading ?
                        <Spinner /> : <>
                            <Printable  product={product} variants={variants} frontSetting={frontSetting} pcs={printPcs} ref={componentRef} />
                            <div className='d-inline my-5'>
                                <button type='button' className='btn btn-success me-5 mb-2' onClick={addOneMore}>Add One More</button>
                                <button type='button' className='btn btn-primary me-5 mb-2' onClick={handlePrint}>Print</button>
                            </div>
                        </>}
                </div>
            </div>
            {/* {pacakgeVsWarehouseData && pacakgeVsWarehouseData.length !== 0 ?
                <div className='card card-body mt-2'>
                    <div>
                        <Table responsive="md">
                            <thead>
                                <tr>


                                    <th>{getFormattedMessage('package.warehouse')}</th>
                                    <th>{getFormattedMessage('package.position')}</th>

                                </tr>
                            </thead>
                            <tbody>
                                {pacakgeVsWarehouseData && pacakgeVsWarehouseData.map((item, index) => {
                                    return (
                                        <tr key={index}>

                                            <td className='py-4'>{item.warehouse.name}</td>
                                            <td className='py-4'>
                                                {Object.keys(item.position).map((key, positionIndex) => (
                                                    <div className='badge bg-light-info me-2' key={positionIndex}>
                                                        <span>{key}: {item.position[key]}</span>
                                                    </div>
                                                ))}

                                            </td>
                                        </tr>
                                    )
                                })}
                            </tbody>
                        </Table>
                    </div>
                </div> : ''
            } */}
            {variants && variants.length !== 0 ?
                <div className='card card-body mt-2'>
                    <div>
                        <Table responsive="md">
                            <thead>
                                <tr>
                                    <th>Product name</th>
                                    <th>Variant name</th>
                                    <th>Variant </th>
                                    <th>Code</th>
                                    <th>Quantity</th>
                                    {/* <th>Barcode</th> */}
                                </tr>
                            </thead>
                            <tbody>
                                {variants && variants.map((item, index) => {
                                    return (
                                        <tr key={index}>
                                            <td className='py-4' style={{ maxWidth: '200px' }}>{item.product_name}</td>

                                            <td className='py-4' style={{ maxWidth: '200px' }}>{item.variant_name}</td>
                                            <td id="hideWhilePrint" className='py-4'> <div>
                                                {Object.keys(item.variant).map((key, variantIndex) => (
                                                    <div className='badge bg-light-info me-2' key={variantIndex}>
                                                        <span>{key}: {item.variant[key]}</span>
                                                    </div>
                                                ))}
                                            </div></td>
                                            <td className='py-4' style={{ maxWidth: '210px' }}>
                                                <Image
                                                    src={product && product.attributes && product.attributes.barcode_url}
                                                    alt={product && product.attributes && product.attributes.name}
                                                    style={{ maxHeight: '30px' }}
                                                    className='product_brcode' />
                                                <br></br><span className='p-0' style={{ fontSize: '10px' }}>{product && product.attributes.code}</span>
                                            </td>
                                            <td className='py-4'>{item.quantity}</td>
                                            {/* <td className='py-4'> <Image  src={item.barcode_url} style={{ width: '100px', height: '50px' }}/>  </td> */}
                                            {/* <td className='py-4'>
                                    <div>

                                        <div className='badge bg-light-info me-2'><span>{item.variant_price}</span></div>

                                    </div>
                                </td> */}
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
    console.log(state.packages, 'package from detail')
    const { packages, isLoading, frontSetting, allConfigData } = state;
    return { packages, isLoading, frontSetting, allConfigData }
};

export default connect(mapStateToProps, { fetchPackage })(PackageDetail);
