import React, {useState} from 'react';
import {connect} from 'react-redux';
import MasterLayout from '../MasterLayout';
import ReactDataTable from '../../shared/table/ReactDataTable';
import {fetchMatrixLeads, updateMatrixLeadStatus} from '../../store/action/matrixLeadAction';
import TabTitle from '../../shared/tab-title/TabTitle';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import {Dropdown, Modal, Button} from 'react-bootstrap';
import {FontAwesomeIcon} from '@fortawesome/react-fontawesome';
import {faEye, faDownload} from '@fortawesome/free-solid-svg-icons';

const MatrixLeads = (props) => {
    const {matrixLeads, fetchMatrixLeads, updateMatrixLeadStatus, totalRecord, isLoading} = props;
    const [showModal, setShowModal] = useState(false);
    const [selectedLead, setSelectedLead] = useState(null);

    const handleClose = () => setShowModal(false);
    const handleShow = (lead) => {
        setSelectedLead(lead);
        setShowModal(true);
    };

    const onChange = (filter) => {
        fetchMatrixLeads(filter, true);
    };

    const onStatusChange = (id, status) => {
        updateMatrixLeadStatus(id, status);
    };

    const itemsValue = (Array.isArray(matrixLeads) ? matrixLeads : []).map(lead => ({
        name: lead.name,
        company_name: lead.company_name,
        email: lead.email,
        profile_name: lead.profile_name,
        note: lead.note,
        file_name: lead.file_name,
        status: lead.status,
        id: lead.id,
    }));

    const columns = [
        {
            name: getFormattedMessage('matrix-leads.table.name.column.title'),
            selector: row => row.name,
            sortField: 'name',
            sortable: true,
            minWidth: '120px'
        },
        {
            name: getFormattedMessage('matrix-leads.table.company.column.title'),
            selector: row => row.company_name,
            sortField: 'company_name',
            sortable: true,
            minWidth: '130px'
        },
        {
            name: getFormattedMessage('matrix-leads.table.email.column.title'),
            selector: row => row.email,
            sortField: 'email',
            sortable: true,
            minWidth: '180px'
        },
        {
            name: getFormattedMessage('matrix-leads.table.profile.column.title'),
            selector: row => row.profile_name,
            sortField: 'profile_name',
            sortable: true,
            minWidth: '150px',
            wrap: true
        },
        {
            name: getFormattedMessage('matrix-leads.table.note.column.title'),
            selector: row => row.note,
            sortField: 'note',
            sortable: false,
            minWidth: '200px',
            wrap: true,
            cell: row => (
                <div className="cursor-pointer" onClick={() => handleShow(row)}>
                    {row.note.length > 50 ? row.note.substring(0, 50) + '...' : row.note}
                    {row.note.length > 50 && <span className="text-primary ms-1">View</span>}
                </div>
            )
        },
        {
            name: getFormattedMessage('react-data-table.action.column.label'),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            minWidth: '120px',
            cell: row => (
                <div className="d-flex align-items-center">
                    <Button variant="link" className="p-0 me-3" title="View Details" onClick={() => handleShow(row)}>
                        <FontAwesomeIcon icon={faEye} className="text-primary fs-3" />
                    </Button>
                    {row.file_name && (
                        <a href={`/uploads/matrix_leads/${row.file_name}`} target="_blank" rel="noreferrer" title="Download File">
                            <FontAwesomeIcon icon={faDownload} className="text-success fs-3" />
                        </a>
                    )}
                </div>
            )
        },
        {
            name: getFormattedMessage('matrix-leads.table.status.column.title'),
            selector: row => row.status,
            sortField: 'status',
            sortable: true,
            minWidth: '120px',
            cell: row => {
                const statusClass = row.status === 'approved' ? 'success' : (row.status === 'rejected' ? 'danger' : 'warning');
                return (
                    <Dropdown>
                        <Dropdown.Toggle variant={statusClass} id="dropdown-basic" size="sm" className="text-white">
                            {row.status.toUpperCase()}
                        </Dropdown.Toggle>

                        <Dropdown.Menu>
                            <Dropdown.Item onClick={() => onStatusChange(row.id, 'pending')}>Pending</Dropdown.Item>
                            <Dropdown.Item onClick={() => onStatusChange(row.id, 'approved')}>Approved</Dropdown.Item>
                            <Dropdown.Item onClick={() => onStatusChange(row.id, 'rejected')}>Rejected</Dropdown.Item>
                        </Dropdown.Menu>
                    </Dropdown>
                )
            }
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText('matrix-leads.title')}/>
            <div className='card'>
                <div className='card-body'>
                    <ReactDataTable columns={columns} items={itemsValue} onChange={onChange}
                                    totalRows={totalRecord} isLoading={isLoading}/>
                </div>
            </div>

            <Modal show={showModal} onHide={handleClose} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>Lead Details</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {selectedLead && (
                        <div className="p-3">
                            <p><strong>Name:</strong> {selectedLead.name}</p>
                            <p><strong>Company:</strong> {selectedLead.company_name}</p>
                            <p><strong>Email:</strong> {selectedLead.email}</p>
                            <p><strong>Profile:</strong> {selectedLead.profile_name}</p>
                            {selectedLead.file_name && (
                                <p><strong>Attached File:</strong> <a href={`/uploads/matrix_leads/${selectedLead.file_name}`} target="_blank" rel="noreferrer" className="text-decoration-none">{selectedLead.file_name}</a></p>
                            )}
                            <hr />
                            <p><strong>Note:</strong></p>
                            <div className="bg-light p-3 rounded" style={{ whiteSpace: 'pre-wrap' }}>
                                {selectedLead.note}
                            </div>
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={handleClose}>
                        Close
                    </Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {matrixLeads, totalRecord, isLoading} = state;
    return {matrixLeads, totalRecord, isLoading}
};

export default connect(mapStateToProps, {fetchMatrixLeads, updateMatrixLeadStatus})(MatrixLeads);
