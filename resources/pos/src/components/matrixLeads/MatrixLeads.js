import React from 'react';
import {connect} from 'react-redux';
import MasterLayout from '../MasterLayout';
import ReactDataTable from '../../shared/table/ReactDataTable';
import {fetchMatrixLeads, updateMatrixLeadStatus} from '../../store/action/matrixLeadAction';
import TabTitle from '../../shared/tab-title/TabTitle';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import {Dropdown} from 'react-bootstrap';

const MatrixLeads = (props) => {
    const {matrixLeads, fetchMatrixLeads, updateMatrixLeadStatus, totalRecord, isLoading} = props;

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
            wrap: true
        },
        {
            name: getFormattedMessage('matrix-leads.table.file.column.title'),
            selector: row => row.file_name,
            sortField: 'file_name',
            sortable: false,
            minWidth: '100px',
            cell: row => row.file_name ? <a href={`/uploads/matrix_leads/${row.file_name}`} target="_blank" rel="noreferrer">{row.file_name}</a> : 'No File'
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
        </MasterLayout>
    )
};

const mapStateToProps = (state) => {
    const {matrixLeads, totalRecord, isLoading} = state;
    return {matrixLeads, totalRecord, isLoading}
};

export default connect(mapStateToProps, {fetchMatrixLeads, updateMatrixLeadStatus})(MatrixLeads);
