import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Badge, Table, Button, Modal, Form, Alert } from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { 
    faPlay, 
    faPause, 
    faTrash, 
    faPlus, 
    faRefresh,
    faClock,
    faCheckCircle,
    faExclamationTriangle,
    faSpinner
} from '@fortawesome/free-solid-svg-icons';
import apiConfig from '../../config/apiConfig';
import { getFormattedMessage } from '../../shared/sharedMethod';
import { apiBaseURL } from '../../constants';
import MasterLayout from '../MasterLayout';

const JobMonitoring = () => {
    const [jobStatuses, setJobStatuses] = useState([]);
    const [scheduledJobs, setScheduledJobs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingJob, setEditingJob] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        job_class: 'App\\Jobs\\ProcessStockUpdate',
        queue_name: 'stock-updates',
        scheduled_time: '03:00',
        timezone: 'Asia/Dhaka',
        is_active: true,
        job_parameters: []
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    // Auto-refresh every 10 seconds
    useEffect(() => {
        fetchData();
        const interval = setInterval(fetchData, 10000);
        return () => clearInterval(interval);
    }, []);

    const fetchData = async () => {
        try {
            const [statusResponse, scheduledResponse] = await Promise.all([
                apiConfig.get(apiBaseURL.JOB_STATUS),
                apiConfig.get(apiBaseURL.SCHEDULED_JOBS)
            ]);

            if (statusResponse.data.success) {
                setJobStatuses(statusResponse.data.data);
            }

            if (scheduledResponse.data.success) {
                setScheduledJobs(scheduledResponse.data.data);
            }

            setLoading(false);
        } catch (error) {
            console.error('Error fetching job data:', error);
            setError('Failed to fetch job data');
            setLoading(false);
        }
    };

    const getStatusBadge = (status) => {
        const variants = {
            pending: 'warning',
            running: 'primary',
            done: 'success',
            failed: 'danger'
        };

        const icons = {
            pending: faClock,
            running: faSpinner,
            done: faCheckCircle,
            failed: faExclamationTriangle
        };

        return (
            <Badge bg={variants[status]} className="d-flex align-items-center gap-1">
                <FontAwesomeIcon 
                    icon={icons[status]} 
                    spin={status === 'running'} 
                />
                {status.toUpperCase()}
            </Badge>
        );
    };

    const handleSaveScheduledJob = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        try {
            const response = await apiConfig.post(apiBaseURL.SCHEDULED_JOBS, formData);
            
            if (response.data.success) {
                setSuccess(response.data.message);
                setShowModal(false);
                setEditingJob(null);
                setFormData({
                    name: '',
                    job_class: 'App\\Jobs\\ProcessStockUpdate',
                    queue_name: 'stock-updates',
                    scheduled_time: '03:00',
                    timezone: 'Asia/Dhaka',
                    is_active: true,
                    job_parameters: []
                });
                fetchData();
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Failed to save scheduled job');
        }
    };

    const handleToggleActive = async (job) => {
        try {
            const response = await apiConfig.put(`${apiBaseURL.SCHEDULED_JOBS}/${job.id}`, {
                is_active: !job.is_active
            });

            if (response.data.success) {
                setSuccess(response.data.message);
                fetchData();
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Failed to update scheduled job');
        }
    };

    const handleDeleteJob = async (jobId) => {
        if (!window.confirm('Are you sure you want to delete this scheduled job?')) {
            return;
        }

        try {
            const response = await apiConfig.delete(`${apiBaseURL.SCHEDULED_JOBS}/${jobId}`);
            
            if (response.data.success) {
                setSuccess(response.data.message);
                fetchData();
            }
        } catch (error) {
            setError(error.response?.data?.message || 'Failed to delete scheduled job');
        }
    };

    const handleEditJob = (job) => {
        setEditingJob(job);
        setFormData({
            name: job.name,
            job_class: job.job_class,
            queue_name: job.queue_name || 'stock-updates',
            scheduled_time: job.scheduled_time.substring(0, 5), // HH:MM format
            timezone: job.timezone,
            is_active: job.is_active,
            job_parameters: job.job_parameters || []
        });
        setShowModal(true);
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ height: '400px' }}>
                <FontAwesomeIcon icon={faSpinner} spin size="2x" />
                <span className="ms-2">Loading job monitoring data...</span>
            </div>
        );
    }

    return (
        <MasterLayout>
            <div className="job-monitoring">
            <Row className="mb-4">
                <Col>
                    <h2>Job Monitoring & Scheduling</h2>
                    <p className="text-muted">Monitor job execution and manage scheduled tasks</p>
                </Col>
                <Col xs="auto">
                    <Button 
                        variant="outline-primary" 
                        onClick={fetchData}
                        className="me-2"
                    >
                        <FontAwesomeIcon icon={faRefresh} /> Refresh
                    </Button>
                    <Button 
                        variant="primary" 
                        onClick={() => setShowModal(true)}
                    >
                        <FontAwesomeIcon icon={faPlus} /> Add Scheduled Job
                    </Button>
                </Col>
            </Row>

            {error && (
                <Alert variant="danger" dismissible onClose={() => setError('')}>
                    {error}
                </Alert>
            )}

            {success && (
                <Alert variant="success" dismissible onClose={() => setSuccess('')}>
                    {success}
                </Alert>
            )}

            {/* Job Status Overview */}
            <Row className="mb-4">
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h5>Running Jobs</h5>
                            <h2 className="text-primary">
                                {jobStatuses.filter(job => job.status === 'running').length}
                            </h2>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h5>Completed Today</h5>
                            <h2 className="text-success">
                                {jobStatuses.filter(job => 
                                    job.status === 'done' && 
                                    new Date(job.created_at).toDateString() === new Date().toDateString()
                                ).length}
                            </h2>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h5>Failed Today</h5>
                            <h2 className="text-danger">
                                {jobStatuses.filter(job => 
                                    job.status === 'failed' && 
                                    new Date(job.created_at).toDateString() === new Date().toDateString()
                                ).length}
                            </h2>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="text-center">
                        <Card.Body>
                            <h5>Scheduled Jobs</h5>
                            <h2 className="text-info">
                                {scheduledJobs.filter(job => job.is_active).length}
                            </h2>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            {/* Scheduled Jobs */}
            <Card className="mb-4">
                <Card.Header>
                    <h5>Scheduled Jobs</h5>
                </Card.Header>
                <Card.Body>
                    {scheduledJobs.length === 0 ? (
                        <p className="text-muted">No scheduled jobs configured.</p>
                    ) : (
                        <Table responsive>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Schedule Time</th>
                                    <th>Next Run</th>
                                    <th>Last Run</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {scheduledJobs.map(job => (
                                    <tr key={job.id}>
                                        <td>
                                            <strong>{job.name}</strong>
                                            <br />
                                            <small className="text-muted">{job.job_class}</small>
                                        </td>
                                        <td>{job.scheduled_time_human}</td>
                                        <td>{job.next_run_human}</td>
                                        <td>{job.last_run_human}</td>
                                        <td>
                                            <Badge bg={job.is_active ? 'success' : 'secondary'}>
                                                {job.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        <td>
                                            <Button
                                                size="sm"
                                                variant={job.is_active ? 'warning' : 'success'}
                                                onClick={() => handleToggleActive(job)}
                                                className="me-1"
                                            >
                                                <FontAwesomeIcon icon={job.is_active ? faPause : faPlay} />
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline-primary"
                                                onClick={() => handleEditJob(job)}
                                                className="me-1"
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline-danger"
                                                onClick={() => handleDeleteJob(job.id)}
                                            >
                                                <FontAwesomeIcon icon={faTrash} />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </Card.Body>
            </Card>

            {/* Recent Job Executions */}
            <Card>
                <Card.Header>
                    <h5>Recent Job Executions</h5>
                </Card.Header>
                <Card.Body>
                    {jobStatuses.length === 0 ? (
                        <p className="text-muted">No job executions found.</p>
                    ) : (
                        <Table responsive>
                            <thead>
                                <tr>
                                    <th>Job Name</th>
                                    <th>Queue</th>
                                    <th>Status</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                {jobStatuses.map(job => (
                                    <tr key={job.id}>
                                        <td>
                                            <strong>{job.job_name}</strong>
                                            {job.meta?.warehouse_id && (
                                                <>
                                                    <br />
                                                    <small className="text-muted">
                                                        Warehouse: {job.meta.warehouse_id}
                                                    </small>
                                                </>
                                            )}
                                        </td>
                                        <td>{job.queue_name || 'default'}</td>
                                        <td>{getStatusBadge(job.status)}</td>
                                        <td>{job.created_at_human}</td>
                                        <td>
                                            {job.meta?.started_at && job.meta?.completed_at ? (
                                                `${Math.round(
                                                    (new Date(job.meta.completed_at) - new Date(job.meta.started_at)) / 1000
                                                )}s`
                                            ) : job.status === 'running' ? (
                                                <FontAwesomeIcon icon={faSpinner} spin />
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </Table>
                    )}
                </Card.Body>
            </Card>

            {/* Add/Edit Scheduled Job Modal */}
            <Modal show={showModal} onHide={() => setShowModal(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>
                        {editingJob ? 'Edit Scheduled Job' : 'Add Scheduled Job'}
                    </Modal.Title>
                </Modal.Header>
                <Form onSubmit={handleSaveScheduledJob}>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Job Name</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.name}
                                        onChange={(e) => setFormData({...formData, name: e.target.value})}
                                        required
                                        placeholder="e.g., Daily Stock Update"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Job Class</Form.Label>
                                    <Form.Select
                                        value={formData.job_class}
                                        onChange={(e) => setFormData({...formData, job_class: e.target.value})}
                                        required
                                    >
                                        <option value="App\Jobs\ProcessStockUpdate">Stock Update Job</option>
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Scheduled Time</Form.Label>
                                    <Form.Control
                                        type="time"
                                        value={formData.scheduled_time}
                                        onChange={(e) => setFormData({...formData, scheduled_time: e.target.value})}
                                        required
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Timezone</Form.Label>
                                    <Form.Select
                                        value={formData.timezone}
                                        onChange={(e) => setFormData({...formData, timezone: e.target.value})}
                                    >
                                        <option value="Asia/Dhaka">Asia/Dhaka (BDT)</option>
                                        <option value="UTC">UTC</option>
                                        <option value="America/New_York">America/New_York (EST)</option>
                                        <option value="Europe/London">Europe/London (GMT)</option>
                                    </Form.Select>
                                </Form.Group>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Label>Queue Name</Form.Label>
                                    <Form.Control
                                        type="text"
                                        value={formData.queue_name}
                                        onChange={(e) => setFormData({...formData, queue_name: e.target.value})}
                                        placeholder="stock-updates"
                                    />
                                </Form.Group>
                            </Col>
                            <Col md={6}>
                                <Form.Group className="mb-3">
                                    <Form.Check
                                        type="checkbox"
                                        label="Active"
                                        checked={formData.is_active}
                                        onChange={(e) => setFormData({...formData, is_active: e.target.checked})}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShowModal(false)}>
                            Cancel
                        </Button>
                        <Button variant="primary" type="submit">
                            {editingJob ? 'Update' : 'Create'} Scheduled Job
                        </Button>
                    </Modal.Footer>
                </Form>
            </Modal>
            </div>
        </MasterLayout>
    );
};

export default JobMonitoring;
