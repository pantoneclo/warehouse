import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import Modal from 'react-bootstrap/Modal';
import { fetchFrontSetting } from '../../store/action/frontSettingAction';
import { getAdjustmentDetails } from '../../store/action/adjustMentDetailsAction';
import { fetchAdjustments } from '../../store/action/adjustMentAction';
import Table from 'react-bootstrap/Table';
import moment from 'moment';
import { getFormattedMessage } from '../../shared/sharedMethod';

const AdjustMentDetail = (props) => {
    const { onDetails, lgShow, setLgShow, fetchFrontSetting, fetchAdjustments, adjustmentsDetails, getAdjustmentDetails} = props;

    useEffect(() => {
        fetchFrontSetting()
    }, [])

    useEffect(() => {
        if(onDetails !== null){
        getAdjustmentDetails(onDetails)
        }
    }, [onDetails])

    const onsetLgShow = () => {
        setLgShow(false)
    }

    return (
        <div>
            <Modal
                size="lg"
                aria-labelledby="example-custom-modal-styling-title"
                show={lgShow}
                onHide={() => onsetLgShow()}
            >
                <Modal.Header closeButton>
                    <Modal.Title id="example-modal-sizes-title-lg">
                        {getFormattedMessage("adjustments.detail.title")}
                    </Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className='mw-100 overflow-auto'>
                        <div className='mx-2'>
                            <Table bordered hover>
                                <thead>
                                    <tr>
                                        <th>{getFormattedMessage("react-data-table.date.column.label")}</th>
                                       
                                        <th>{getFormattedMessage("globally.detail.reference")}</th>
                                        <th>{getFormattedMessage("globally.detail.warehouse")}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{adjustmentsDetails && adjustmentsDetails.attributes && moment(adjustmentsDetails.attributes.date).format('YYYY-MM-DD')}</td>
                                       
                                        <td>{adjustmentsDetails && adjustmentsDetails.attributes && adjustmentsDetails.attributes.reference_code}</td>
                                        <td>{adjustmentsDetails && adjustmentsDetails.attributes && adjustmentsDetails.attributes.warehouse_name}</td>
                                    </tr>
                                </tbody>
                            </Table>
                        </div>
                        <div className='mx-2'>
                        <Table bordered hover>
                                <thead>
                                    <tr>
                                        <th>{getFormattedMessage("globally.detail.product")}</th>
                                        <th>{getFormattedMessage("Style")}</th>
                                        <th>{getFormattedMessage("product.product-details.code-product.label")}</th>
                                        <th>{getFormattedMessage("dashboard.stockAlert.quantity.label")}</th>
                                        <th>{getFormattedMessage("globally.type.label")}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {adjustmentsDetails && adjustmentsDetails.attributes && adjustmentsDetails.attributes.adjustment_items.map((item, index) => {
                                        return(
                                        <tr key={index}>
                                        <td>{item.product.name} <br/><span className='text-success'> {item.product.variant.name}</span></td>
                                        <td >{item.product && item.product.product_abstract.pan_style}</td>
                                        <td>{item.product.code}</td>
                                        <td>{item.quantity}</td>
                                        <td>{item.method_type === 1 ? 'Addition' : "Subtraction"}</td>
                                        </tr>
                                        )
                                    })}
                                </tbody>
                            </Table>
                        </div>
                    </div>
                </Modal.Body>
            </Modal>
        </div>
    )
};


const mapStateToProps = (state) => {
    const { adjustments, adjustmentsDetails, isLoading, frontSetting } = state;
    return { adjustments, adjustmentsDetails, isLoading, frontSetting };
};

export default connect(mapStateToProps, {fetchFrontSetting, getAdjustmentDetails, fetchAdjustments })(AdjustMentDetail);

