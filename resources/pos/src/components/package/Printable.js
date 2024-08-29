import React, { forwardRef, useRef } from 'react';
import { Image, Table } from 'react-bootstrap-v5';

const Printable = forwardRef((props, ref) => {
    console.log(props,'props');
    const { product, variants, frontSetting, pcs, page_break=2 } = props;
    // console.log("lla"+{product});
    // const totalQuantity = useRef(0);
    return (
        <div className='' ref={ref}>
            {Array.from({ length: pcs }, (_, i) => (
                <div key={i} className={'text-center mt-4 mb-0 .d-print-block '} style={(i + 1) % page_break == 0 ? 
                { pageBreakAfter: 'always' } : {}}>
                    <div style={{ margin: 'auto', width:'1000px', minHeight: '650px', objectFit:'fit', overflow: 'hidden', border: '2px solid #000', padding: '10px' }}>
                        <div className='col-md-12 text-center'>

                            <div className='d-inline-block text-center'><h3>{frontSetting?.value?.company_name}</h3></div>

                            <div className='d-inline-block text-center'>
                                <Image
                                    src={product && product.attributes && product.attributes.barcode_url}
                                    alt={product && product.attributes && product.attributes.name}
                                    style={{ width: '400px' }}
                                    className='' />
                                <div
                                    className='mt-3 mb-1' style={{fontSize:"20px"}}>{product && product.attributes && product.attributes.code}</div>
                            </div>


                        </div>
                        {variants && variants.length !== 0 ?
                            <div className='card card-body p-0 mt-2'>
                                <div className="p-0">
                                    <Table className='p-0 m-0' responsive="md" >
                                        <thead>
                                            <tr>
                                                <th   style={{ maxWidth: '200px', fontSize: '28px' }}> Product name</th>
                                                {variants && variants[0] &&
                                                    Object.keys(variants[0].variant).map((variantLabel, index) => (
                                                        <th
                                                            key={index}
                                                            style={{ maxWidth: '200px', fontSize: '28px' }}
                                                        >
                                                            {variantLabel}
                                                        </th>
                                                    ))}

                                                <th  style={{ maxWidth: '200px', fontSize: '28px' }}>Style</th>
                                                <th  style={{ maxWidth: '200px', fontSize: '28px' }}>Quantity</th>
                                                {/* <th>Barcode</th> */}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {variants && variants.map((item, index) => {
                                                console.log(item);
                                                return (
                                                    <tr key={index}>

                                                        <td className='py-3' style={{ maxWidth: '200px', fontSize: '28px',color:'#212529' }}>
                                                           
                                                            <span  style={{fontSize:'28px',lineHeight:'56px'}}>{item.product_name}</span>  
                                                            
                                                            </td>
                                                        {Object.values(item.variant).map((variantValue, variantIndex) => (
                                                            <td className='py-3' key={variantIndex} style={{ maxWidth: '200px', fontSize: '28px' }}>
                                                              <span  style={{fontSize:'28px',color:'#212529',lineHeight:'56px'}}>{variantValue}</span>  
                                                            </td>
                                                        ))}

                                                        {/* <td className='py-2' style={{ maxWidth: '200px', fontSize: '10px' }}>{item.variant_name}</td> */}


                                                        <td className='py-3 ' style={{ maxWidth: '200px' }}>
                                                            {/* <Image
                                                                            src={product && product.attributes && product.attributes.barcode_url}
                                                                            alt={product && product.attributes && product.attributes.name}
                                                                            style={{ maxHeight: '10px' }}
                                                                            className='product_brcode' /> */}
                                                            <span  className='p-2 ' style={{ fontSize: '28px' ,color:'#212529',lineHeight:'56px'}}>{item && item.pan_style}</span>
                                                        </td>
                                                        <td className='py-3 m-auto '><span className='p-1' style={{fontSize:'28px',color:'#212529',lineHeight:'56px'}}>{item.quantity}</span></td>
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

                        <div className='col-md-12'>

                            {/* <div className='d-inline-block text-center'>{frontSetting?.value?.company_name}</div> */}

                            <div className='p-0 d-flex text-right' style={{ justifyContent: 'space-between',fontSize:'12px' }}>

                                <div
                                    className='mt-0 ms-5' style={{fontSize:'25px'}} >{product?.attributes.notes}
                                </div>

                                <div
                                    className='mt-0 me-9 text-small ' style={{fontSize:"28px"}}>Total:{variants && variants.reduce((accumulator, currentValue) => accumulator + currentValue.quantity, 0)}
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
});

export default Printable;
