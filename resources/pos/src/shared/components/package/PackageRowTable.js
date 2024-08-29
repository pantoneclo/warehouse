import React, {useEffect} from 'react';
import {Table} from 'react-bootstrap-v5';
import PackageTableBody from './PackageTableBody';
import {getFormattedMessage} from '../../sharedMethod';

const PackageRowTable = (props) => {
    const {
        updateProducts, setUpdateProducts, updatedQty, updateCost, updateDiscount, updateTax,
        frontSetting, updateSubTotal, updateSaleUnit, isSaleReturn
    } = props;

    useEffect(() => {
        setUpdateProducts(updateProducts);
    }, [updateProducts]);

    return (
        <Table responsive>
            <thead>
            <tr>
                <th>{getFormattedMessage('product.title')}</th>
                <th>{getFormattedMessage('sale.order-item.table.net-unit-price.column.label')}</th>
                <th>{getFormattedMessage('Style')}</th>

                <th>{getFormattedMessage('purchase.order-item.table.stock.column.label')}</th>
                <th className='text-lg-start text-center'>{getFormattedMessage('purchase.order-item.table.qty.column.label')}</th>
                {/* <th>{getFormattedMessage('xxpurchase.order-item.table.discount.column.label')}</th>
                <th>{getFormattedMessage('xpurchase.order-item.table.tax.column.label')}</th> */}
                <th>{getFormattedMessage('purchase.order-item.table.sub-total.column.label')}</th>
                {isSaleReturn ? null : < th > {getFormattedMessage('react-data-table.action.column.label')}</th>}
            </tr>
            </thead>
            <tbody>
            {updateProducts && updateProducts.map((singleProduct, index) => {
                return <PackageTableBody singleProduct={singleProduct} key={index} index={index} updateProducts={updateProducts}
                                         setUpdateProducts={setUpdateProducts} frontSetting={frontSetting}
                                         updateQty={updatedQty} updateCost={updateCost}
                                        
                                         updateSubTotal={updateSubTotal} updateSaleUnit={updateSaleUnit}/>
                })}
            {!updateProducts.length &&
                <tr>
                    <td colSpan={8} className='fs-5 px-3 py-6 custom-text-center'>
                        {getFormattedMessage('sale.product.table.no-data.label')}
                    </td>
                </tr>
            }
            </tbody>
        </Table>
    )
};

export default PackageRowTable;
