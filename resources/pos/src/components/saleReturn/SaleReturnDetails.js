import React, {useEffect, useState} from 'react';
import MasterLayout from '../MasterLayout';
import Form from 'react-bootstrap/Form';
import {connect, useDispatch} from 'react-redux';
import {Col, Row, Table, Button} from 'react-bootstrap-v5';
import {useParams, useNavigate} from 'react-router-dom';
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faEnvelope, faLocationDot, faMobileAlt, faUser, faCheck, faCheckCircle} from '@fortawesome/free-solid-svg-icons';
import HeaderTitle from '../header/HeaderTitle';
import TabTitle from '../../shared/tab-title/TabTitle';
import {currencySymbolHendling, getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import {fetchSaleReturnDetails} from '../../store/action/salesReturnDetailAction';
import {approveSaleReturn, approvePartialSaleReturn} from '../../store/action/salesReturnAction';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import {getCurrencySymbol} from '../../constants';

const SaleReturnDetails = (props) => {
    const {fetchSaleReturnDetails, saleReturnDetails, frontSetting, allConfigData} = props;
    const {id} = useParams();
    const dispatch = useDispatch();
    const navigate = useNavigate();

    // State for managing selected items for partial approval
    const [selectedItems, setSelectedItems] = useState([]);
    const [selectAll, setSelectAll] = useState(false);

    // Get currency symbol from sale return's currency or fallback to global setting
    const currencySymbolToUse = getCurrencySymbol(saleReturnDetails?.currency) || (frontSetting.value && frontSetting.value.currency_symbol);

    useEffect(() => {
        fetchSaleReturnDetails(id);
    }, []);

    // Handle individual item selection
    const handleItemSelection = (itemId, isChecked) => {
        if (isChecked) {
            setSelectedItems([...selectedItems, itemId]);
        } else {
            setSelectedItems(selectedItems.filter(id => id !== itemId));
            setSelectAll(false);
        }
    };

    // Handle select all items
    const handleSelectAll = (isChecked) => {
        setSelectAll(isChecked);
        if (isChecked) {
            const allItemIds = saleReturnDetails.sale_return_items?.map(item => item.id) || [];
            setSelectedItems(allItemIds);
        } else {
            setSelectedItems([]);
        }
    };

    // Handle full approval
    const handleFullApproval = () => {
        if (window.confirm('Are you sure you want to approve this entire sale return? This will update stock quantities in PostgreSQL.')) {
            dispatch(approveSaleReturn(id, navigate));
        }
    };

    // Handle partial approval
    const handlePartialApproval = () => {
        if (selectedItems.length === 0) {
            alert('Please select at least one item to approve.');
            return;
        }

        if (window.confirm(`Are you sure you want to approve ${selectedItems.length} selected item(s)? This will update stock quantities for these items in PostgreSQL.`)) {
            dispatch(approvePartialSaleReturn(id, selectedItems, navigate));
        }
    };

    // Check if return can be approved
    const canApprove = saleReturnDetails?.return_status === 'Pending' || !saleReturnDetails?.return_status;
    const isApproved = saleReturnDetails?.return_status === 'Approved';
    const isPartiallyApproved = saleReturnDetails?.return_status === 'Partially Approved';

    return (
        <MasterLayout>
            <TopProgressBar/>
            <HeaderTitle title={getFormattedMessage('sale-return.details.title')} to='/app/sale-return'/>
            <TabTitle title={placeholderText('sale-return.details.title')}/>
            <div className='card'>
                <div className='card-body'>
                    <Form>
                        <div className='row'>
                            <div className='col-12'>
                                <h4 className='font-weight-bold text-center mb-5'>
                                    {getFormattedMessage('sale-return.details.title')} : {saleReturnDetails && saleReturnDetails.reference_code}
                                </h4>
                            </div>
                        </div>
                        <Row className='custom-line-height'>
                            <Col md={4}>
                                <h5 className='text-gray-600 bg-light p-4 mb-0 text-uppercase'>{getFormattedMessage('sale.detail.customer.info')}</h5>
                                <div className='p-4'>
                                    <div className='d-flex align-items-center pb-1'>
                                        <FontAwesomeIcon icon={faUser}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.customer && saleReturnDetails.customer.name}
                                    </div>
                                    <div className='d-flex align-items-center pb-1'>
                                        <FontAwesomeIcon icon={faEnvelope}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.customer && saleReturnDetails.customer.email}
                                    </div>
                                    <div className='d-flex align-items-center pb-1'>
                                        <FontAwesomeIcon icon={faMobileAlt}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.customer && saleReturnDetails.customer.phone}
                                    </div>
                                    <div className='d-flex align-items-center'>
                                        <FontAwesomeIcon icon={faLocationDot}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.customer && saleReturnDetails.customer.address}
                                    </div>
                                </div>
                            </Col>
                            <Col md={4}>
                                <h5 className='text-gray-600 bg-light p-4 mb-0 text-uppercase'>{getFormattedMessage('globally.detail.company.info')}</h5>
                                <div className='p-4'>
                                    <div className='d-flex align-items-center pb-1'>
                                        <FontAwesomeIcon icon={faUser}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.company_info && saleReturnDetails.company_info.company_name}
                                    </div>
                                    <div className='d-flex align-items-center pb-1'>
                                        <FontAwesomeIcon icon={faEnvelope}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.company_info && saleReturnDetails.company_info.email}
                                    </div>
                                    <div className='d-flex align-items-center pb-1'>
                                        <FontAwesomeIcon icon={faMobileAlt}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.company_info && saleReturnDetails.company_info.phone}
                                    </div>
                                    <div className='d-flex align-items-center'>
                                        <FontAwesomeIcon icon={faLocationDot}
                                                         className='text-primary me-2 fs-5'/>{saleReturnDetails.company_info && saleReturnDetails.company_info.address}
                                    </div>
                                </div>
                            </Col>
                            <Col md={4}>
                                <h5 className='text-gray-600 bg-light p-4 mb-0 text-uppercase'>{getFormattedMessage('sale.detail.invoice.info')}</h5>
                                <div className='p-4'>
                                    <div className='pb-1'>
                                        <span
                                            className='me-2'>{getFormattedMessage('globally.detail.reference')} :</span>
                                        <span>{saleReturnDetails && saleReturnDetails.reference_code}</span>
                                    </div>
                                    <div className='pb-1'>
                                        <span className='me-2'>{getFormattedMessage('globally.detail.status')} :</span>
                                        {saleReturnDetails && saleReturnDetails.status === 1 &&
                                        <span className='badge bg-light-success'>
                                        <span>Received</span>
                                    </span> || saleReturnDetails.status === 2 &&
                                        <span className='badge bg-light-primary'>
                                        <span>Pending</span>
                                    </span> || saleReturnDetails.status === 3 &&
                                        <span className='badge bg-light-warning'>
                                        <span>Ordered</span>
                                    </span>
                                        }
                                    </div>
                                    <div className='pb-1'>
                                        <span className='me-2'>Approval Status :</span>
                                        {isApproved &&
                                        <span className='badge bg-success'>
                                            <FontAwesomeIcon icon={faCheckCircle} className='me-1'/>
                                            <span>Approved</span>
                                        </span> || isPartiallyApproved &&
                                        <span className='badge bg-warning'>
                                            <FontAwesomeIcon icon={faCheck} className='me-1'/>
                                            <span>Partially Approved</span>
                                        </span> || canApprove &&
                                        <span className='badge bg-secondary'>
                                            <span>Pending Approval</span>
                                        </span>
                                        }
                                    </div>
                                    <div className='pb-1'>
                                        <span
                                            className='me-2'>{getFormattedMessage('globally.detail.warehouse')} :</span>
                                        <span>{saleReturnDetails.warehouse && saleReturnDetails.warehouse.name}</span>
                                    </div>
                                    <div>
                                    <span
                                        className='me-2'>{getFormattedMessage('globally.detail.payment.status')} :</span>
                                        <span className='badge bg-light-success'>
                                        <span>Unpaid</span>
                                    </span>
                                    </div>
                                </div>
                            </Col>
                        </Row>
                        <div className='mt-5'>
                            <div className='d-flex justify-content-between align-items-center mb-4'>
                                <h5 className='font-weight-bold mb-0'>{getFormattedMessage('globally.detail.order.summary')}</h5>
                                {canApprove && (
                                    <div className='d-flex gap-2'>
                                        <Button
                                            variant="success"
                                            size="sm"
                                            onClick={handleFullApproval}
                                        >
                                            <FontAwesomeIcon icon={faCheckCircle} className='me-1'/>
                                            Approve All Items
                                        </Button>
                                        <Button
                                            variant="warning"
                                            size="sm"
                                            onClick={handlePartialApproval}
                                            disabled={selectedItems.length === 0}
                                        >
                                            <FontAwesomeIcon icon={faCheck} className='me-1'/>
                                            Approve Selected ({selectedItems.length})
                                        </Button>
                                    </div>
                                )}
                            </div>
                            <Table responsive>
                                <thead>
                                <tr>
                                    {canApprove && (
                                        <th className='ps-3'>
                                            <Form.Check
                                                type="checkbox"
                                                checked={selectAll}
                                                onChange={(e) => handleSelectAll(e.target.checked)}
                                                label="Select All"
                                            />
                                        </th>
                                    )}
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.product')}</th>
                                    <th className='ps-3'>{getFormattedMessage('Style')}</th>
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.net-unit-price')}</th>
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.quantity')}</th>
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.unit-price')}</th>
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.discount')}</th>
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.tax')}</th>
                                    <th className='ps-3'>{getFormattedMessage('globally.detail.subtotal')}</th>
                                    <th className='ps-3'>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                {saleReturnDetails.sale_return_items && saleReturnDetails.sale_return_items.map((details, index) => {
                                    const isItemSelected = selectedItems.includes(details.id);
                                    const isItemApproved = details.is_approved;

                                    return (
                                        <tr key={index} className={`align-middle ${isItemApproved ? 'table-success' : ''}`}>
                                            {canApprove && (
                                                <td className="ps-3">
                                                    <Form.Check
                                                        type="checkbox"
                                                        checked={isItemSelected}
                                                        onChange={(e) => handleItemSelection(details.id, e.target.checked)}
                                                        disabled={isItemApproved}
                                                    />
                                                </td>
                                            )}
                                            <td className="ps-3">{details.product && details.product.code}
                                                ({details.product && details.product.name}) <br/> ({details.product && details.product.variant.name})</td>
                                            <td>{details.product && details.product.product_abstract.pan_style}</td>
                                            <td>{currencySymbolHendling(allConfigData, currencySymbolToUse, details.net_unit_price)}</td>
                                            <td>{details.quantity}</td>
                                            <td>{currencySymbolHendling(allConfigData, currencySymbolToUse, details.product_price)}</td>
                                            <td>{currencySymbolHendling(allConfigData, currencySymbolToUse, details.discount_amount)}</td>
                                            <td>{currencySymbolHendling(allConfigData, currencySymbolToUse, details.tax_amount)}</td>
                                            <td>{currencySymbolHendling(allConfigData, currencySymbolToUse, details.sub_total)}</td>
                                            <td className="ps-3">
                                                {isItemApproved ? (
                                                    <span className='badge bg-success'>
                                                        <FontAwesomeIcon icon={faCheckCircle} className='me-1'/>
                                                        Approved
                                                    </span>
                                                ) : (
                                                    <span className='badge bg-secondary'>
                                                        Pending
                                                    </span>
                                                )}
                                            </td>
                                        </tr>)
                                })}
                                </tbody>
                            </Table>
                        </div>
                        <div className='col-xxl-5 col-lg-6 col-md-6 col-12 float-end'>
                            <div className='card'>
                                <div className='card-body pt-7 pb-2'>
                                    <div className='table-responsive'>
                                        <table className='table border'>
                                            <tbody>
                                            <tr>
                                                <td className='py-3'>{getFormattedMessage('globally.detail.order.tax')}</td>
                                                <td className='py-3'>
                                                    {currencySymbolHendling(allConfigData, currencySymbolToUse, saleReturnDetails && saleReturnDetails.tax_amount > 0 ? saleReturnDetails.tax_amount : '0.00')} ({saleReturnDetails && parseFloat(saleReturnDetails.tax_rate).toFixed(2)}%)
                                                </td>
                                            </tr>
                                            <tr>
                                                <td className='py-3'>{getFormattedMessage('globally.detail.discount')}</td>
                                                <td className='py-3'>{currencySymbolHendling(allConfigData, currencySymbolToUse, saleReturnDetails && saleReturnDetails.discount)}</td>
                                            </tr>
                                            <tr>
                                                <td className='py-3'>{getFormattedMessage('globally.detail.shipping')}</td>
                                                <td className='py-3'>{currencySymbolHendling(allConfigData, currencySymbolToUse, saleReturnDetails && saleReturnDetails.shipping)}</td>
                                            </tr>
                                            <tr>
                                                <td className='py-3 text-primary'>{getFormattedMessage('globally.detail.grand.total')}</td>
                                                <td className='py-3 text-primary'>
                                                    {currencySymbolHendling(allConfigData, currencySymbolToUse, saleReturnDetails && saleReturnDetails.grand_total)}
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Form>
                </div>
            </div>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
        const {saleReturnDetails, frontSetting, allConfigData} = state;
        return {saleReturnDetails, frontSetting, allConfigData};
};

export default connect(mapStateToProps, {fetchSaleReturnDetails })(SaleReturnDetails);
