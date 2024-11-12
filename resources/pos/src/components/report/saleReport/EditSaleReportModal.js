import React, { useEffect, useState } from 'react';
import { Button, Modal, Row } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import { getFormattedMessage, placeholderText } from "../../../shared/sharedMethod";
import moment from 'moment';
import ReactDatePicker from "../../../shared/datepicker/ReactDatePicker";
import ReactSelect from "../../../shared/select/reactSelect";
import ModelFooter from "../../../shared/components/modelFooter";
import { useDispatch } from "react-redux";
import { editSale } from '../../../store/action/salesAction';
import { editSaleReport } from '../../../store/action/salesAction';

const EditSaleReportModal = (props) => {
    const { onEditSaleReportClick, isEditSaleReportOpen, saleReportItem, setIsEditSaleReportOpen } = props;
    const dispatch = useDispatch();
    const [saleReportValue, setSaleReportValue] = useState({
        
        payment_date: new Date(),
        grand_total: "",
        paid_amount: "",
        order_no: "",
        sale_id: "",
        tax_amount: "",
        tax_rate: "",
        shipping: "",
        discount: "",
        received_amount: "",
        date: "",
        currency: "",
        marketplace_commission: "",
        order_process_fee: "",
        other_income: "",
        courier_fee: "",
        other_cost: "",
        selling_value_eur: "",
    });
    const navigate = useNavigate();
    useEffect(() => {
        if (saleReportItem) {
            setSaleReportValue({
                reference_code: saleReportItem.reference_code || "",
                payment_date: saleReportItem.date ? moment(saleReportItem.date).toDate() : new Date(),
                customer_name: saleReportItem.customer_name || "",
                customer_id: saleReportItem.customer_id || "",
                warehouse_id: saleReportItem.warehouse_id || "",
                warehouse_name: saleReportItem.warehouse_name || "",
                status: saleReportItem.status || "",
                payment_status: saleReportItem.payment_status || "",
                grand_total: saleReportItem.grand_total || "",
                paid_amount: saleReportItem.paid_amount || "",
                country: saleReportItem.country || "",
                market_place: saleReportItem.market_place || "",
                order_no: saleReportItem.order_no || "",
                sale_id: saleReportItem.id || "",
                tax_amount: saleReportItem.tax_amount || "",
                tax_rate: saleReportItem?.tax_rate || "",
                shipping: saleReportItem?.shipping || "",
                discount: saleReportItem?.discount ||"",
                payment_type: Number(saleReportItem?.payment_type) || 0,
                received_amount: saleReportItem?.received_amount || "",
                note: saleReportItem?.note || "",
                is_return: saleReportItem?.is_return || "",
                barcode_symbol: saleReportItem?.barcode_symbol || "",
                date: moment(saleReportItem.date).format('YYYY-MM-DD'),
                time: moment(saleReportItem.created_at).format('LT'),
                currency: saleReportItem ?.currency || "",
                marketplace_commission: saleReportItem ?.marketplace_commission || "",
                order_process_fee: saleReportItem ?.order_process_fee || "",
                courier_fee: saleReportItem ?.courier_fee || "",
                other_income: saleReportItem ?.other_income || "",
                other_cost: saleReportItem ?.other_cost || "",
                selling_value_eur: saleReportItem ?.selling_value_eur || "",
            });
        }
    }, [saleReportItem]);

    const handleCallback = (date) => {
        setSaleReportValue(previousState => ({ ...previousState, payment_date: date }));
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSaleReportValue(previousState => ({ ...previousState, [name]: value }));
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        dispatch(editSaleReport(saleReportValue.sale_id, saleReportValue, navigate));
        setIsEditSaleReportOpen(false);
    };

    const clearField = () => {
        setIsEditSaleReportOpen(false);
    };

    return (
        <Modal
            show={isEditSaleReportOpen}
            onHide={onEditSaleReportClick} size='lg' keyboard={true}
        >
            <Modal.Header closeButton>
                <Modal.Title>
                    {getFormattedMessage("sale.report.edit.headline")}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Row>
                <div className="col-6 mb-3">
                        <label className='form-label'>
                            DATE :
                        </label>
                        <input type='text' name='date'
                            placeholder={placeholderText("sale.report.edit.enter.vat")}
                            className='form-control'
                            onChange={handleChange}
                            readOnly={true}
                            value={`${saleReportValue.date} / ${saleReportValue.time}`} />
                    </div>
                    <div className="col-6 mb-3">
                        <label className='form-label'>
                            VAT RATE %:
                        </label>
                        <input type='text' name='tax_rate'
                            placeholder={placeholderText("sale.report.edit.enter.vat")}
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.tax_rate} />
                    </div>

                    <div className="col-6 mb-3">
                        <label className='form-label'>
                           MERKET PLACE COMMISSION :
                        </label>
                        <input type='text' name='marketplace_commission'
                            placeholder={placeholderText("sale.report.edit.enter.marketplace.commision")}
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.marketplace_commission} />
                    </div>

                    <div className="col-6 mb-3">
                        <label className='form-label'>
                        ORDER PROCESSING FEE :
                        </label>
                        <input type='text' name='order_process_fee'
                            placeholder={placeholderText("sale.report.edit.enter.order.process-fee")}
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.order_process_fee} />
                    </div>
                    <div className="col-6 mb-3">
                        <label className='form-label'>
                        COURIER FEE :
                        </label>
                        <input type='text' name='courier_fee'
                            placeholder={placeholderText("sale.report.edit.enter.courier")}
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.courier_fee} />
                    </div>

                    <div className="col-6 mb-3">
                        <label className='form-label'>
                        OTHER INCOME :
                        </label>
                        <input type='text' name='other_income'
                            placeholder={placeholderText("sale.report.edit.enter.other.income")}
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.other_income} />
                    </div>
                    <div className="col-6 mb-3">
                        <label className='form-label'>
                        OTHER COST :
                        </label>
                        <input type='text' name='other_cost'
                            placeholder={placeholderText("sale.report.edit.enter.other.cost")}
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.other_cost} />
                    </div>

                    <div className="col-6 mb-3">
                        <label className='form-label'>
                          SELLING VALUE (EUR) :
                        </label>
                        <input type='text' name='selling_value_eur'
                            placeholder='Enter selling value converted rate'
                            className='form-control'
                            onChange={handleChange}
                            value={saleReportValue.selling_value_eur} />
                    </div>

                   
                    <ModelFooter clearField={clearField} onSubmit={handleSubmit} />
                </Row>
            </Modal.Body>
        </Modal>
    );
};

export default EditSaleReportModal;
