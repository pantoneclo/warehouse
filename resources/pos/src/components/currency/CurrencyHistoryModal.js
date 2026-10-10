import React, {useEffect, useState} from 'react';
import {Modal, Table, Spinner} from 'react-bootstrap-v5';
import apiConfig from '../../config/apiConfig';
import moment from 'moment';

const CurrencyHistoryModal = ({show, handleClose, currency}) => {
    const [historyList, setHistoryList] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (show && currency && currency.id) {
            setLoading(true);
            apiConfig.get(`currencies/${currency.id}/history`)
                .then((response) => {
                    setHistoryList(response?.data?.data || []);
                    setLoading(false);
                })
                .catch(() => {
                    setHistoryList([]);
                    setLoading(false);
                });
        }
    }, [show, currency]);

    return (
        <Modal show={show} onHide={handleClose} size="lg" centered>
            <Modal.Header closeButton>
                <Modal.Title className="fs-4">
                    {currency?.name} ({currency?.code}) - Conversion Rate History
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="mb-3 text-muted">
                    Base Currency: <span className="badge bg-light-primary fw-bold">EUR (1.0000)</span>
                    <span className="ms-3">Current Active Rate: <strong>{currency?.conversion_rate}</strong></span>
                </div>
                {loading ? (
                    <div className="text-center py-5">
                        <Spinner animation="border" variant="primary" />
                    </div>
                ) : historyList.length > 0 ? (
                    <div className="table-responsive">
                        <Table striped bordered hover className="align-middle">
                            <thead className="table-light">
                                <tr>
                                    <th style={{width: '60px'}}>#</th>
                                    <th>Effective Date</th>
                                    <th>Conversion Rate (EUR)</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                {historyList.map((item, index) => (
                                    <tr key={item.id || index}>
                                        <td>{index + 1}</td>
                                        <td>
                                            <span className="badge bg-light-info text-dark">
                                                {item.date ? moment(item.date).format('YYYY-MM-DD') : '-'}
                                            </span>
                                        </td>
                                        <td>
                                            <span className="fw-bolder text-primary">
                                                {parseFloat(item.conversion_rate).toFixed(4)}
                                            </span>
                                        </td>
                                        <td>
                                            {item.created_at ? moment(item.created_at).format('YYYY-MM-DD hh:mm A') : '-'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    </div>
                ) : (
                    <div className="text-center py-4 text-muted">
                        No historical rate changes recorded yet for this currency.
                    </div>
                )}
            </Modal.Body>
            <Modal.Footer>
                <button type="button" className="btn btn-secondary" onClick={handleClose}>
                    Close
                </button>
            </Modal.Footer>
        </Modal>
    );
};

export default CurrencyHistoryModal;
