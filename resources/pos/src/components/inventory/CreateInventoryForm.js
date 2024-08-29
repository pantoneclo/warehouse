import React, {useState} from 'react';
import Form from 'react-bootstrap/Form';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ReactSelectInventory from "../../shared/select/reactSelectInventory";

import ModelFooter from '../../shared/components/modelFooter';
import {connect, useDispatch} from 'react-redux';


const CreateInventoryForm = (props) => {
    const {products, addInventoryData} = props;
    const dispatch = useDispatch();

    const [fields, setFields] = useState([{
        no_of_items_per_box: 0,
        sticker_meas_unit: 'PCS',
        product_id: null,
        product: [{product: null, item_per_box: 0}],
        no_boxes: 0,
        carton_meas: 0,
        gross_wt: 0,
        net_wt: 0,
        carton_no: 0
    }]);

    const disabled = true;
    const handleInputChange = (index, event) => {
        const values = [...fields];
        values[index][event.target.name] = event.target.value;
        setFields(values);
    };

    const handleSelectChange = (index, nestedIndex, selectedOption) => {

        const values = [...fields];
        values[index].product[nestedIndex].product = selectedOption;
        setFields(values);
    };


    const handleItemPerBoxChange = (index, nestedIndex, event) => {
        const {value} = event.target;  // Get the value from the event
        const values = [...fields];  // Copy the current state
        values[index].product[nestedIndex].item_per_box = value;  // Update the specific nested item
        setFields(values);  // Set the new state
    };

    const handleAddFields = () => {
        setFields([...fields, {
            no_of_items_per_box: 0,
            product_id: null,
            sticker_meas_unit: 'PCS',
            product: [{product: null, item_per_box: 0}],
            no_boxes: 0,
            carton_meas: 0,
            gross_wt: 0,
            net_wt: 0
        }]);
    };

    const handleAddNestedProduct = (index) => {
        const values = [...fields];
        values[index].product.push({product: null, item_per_box: 0});
        setFields(values);
    };

    const handleRemoveNestedProduct = (index, nestedIndex) => {
        const values = [...fields];
        values[index].product.splice(nestedIndex, 1);
        setFields(values);
    };

    const handleRemoveFields = (index) => {
        const values = [...fields];
        values.splice(index, 1);
        setFields(values);
    };
    const prepareFormData = (data) => {
        const formData = new FormData();
        data.forEach((item, index) => {
            formData.append(`items[${index}][no_of_items_per_box]`, item.no_of_items_per_box);
            formData.append(`items[${index}][no_boxes]`, item.no_boxes);
            formData.append(`items[${index}][sticker_meas_unit]`, item.sticker_meas_unit);
            formData.append(`items[${index}][carton_meas]`, item.carton_meas);
            formData.append(`items[${index}][gross_wt]`, item.gross_wt);
            formData.append(`items[${index}][net_wt]`, item.net_wt);
            formData.append(`items[${index}][carton_no]`, item.carton_no);

            item.product.forEach((nestedProduct, nestedIndex) => {
                if (nestedProduct.product) {
                    formData.append(`items[${index}][product][${nestedIndex}][product_id]`, nestedProduct.product.value);
                    formData.append(`items[${index}][product][${nestedIndex}][item_per_box]`, nestedProduct.item_per_box);
                    formData.append(`items[${index}][product][${nestedIndex}][variant_color]`, nestedProduct.product.variant['color'] ?? null);
                    formData.append(`items[${index}][product][${nestedIndex}][variant_size]`, nestedProduct.product.variant['size'] ?? null);
                    formData.append(`items[${index}][product][${nestedIndex}][style]`, nestedProduct.product.style ?? null);
                    formData.append(`items[${index}][product][${nestedIndex}][variant_id]`, nestedProduct.product.variant_id ?? null);
                }
            });
        });
        return formData;
    };
    const onSubmit = (event) => {
        event.preventDefault();
        const formData = prepareFormData(fields);
        addInventoryData(formData);
    };

    const sticker_items_mes = ['PCS', 'Pack'];
    return (
        <div>
            <div className='card'>
                <div className='card-body'>
                    <div className="text-right" style={{textAlign: 'right'}}>
                        <button type="button" className="btn btn-primary btn-sm text-right"
                                onClick={handleAddFields}>Add new
                        </button>
                    </div>
                    <Form>
                        <div className='row'>
                            <div className="col-md-4">
                                <div className="row">
                                    <div className="col-md-4">
                                        <p style={({
                                            fontSize: '12px',
                                            fontWeight: 'bold'
                                        })}>{getFormattedMessage('inventory.form.no_of_items_per_box.label')}</p>
                                    </div>
                                    <div className="col-md-8">
                                        <p style={({
                                            fontSize: '12px',
                                            fontWeight: 'bold'
                                        })}>{getFormattedMessage('inventory.form.product.label')}</p>
                                    </div>
                                </div>
                            </div>
                            <div className='col-md-1'></div>
                            <div className='col-md-2'>
                                <p style={({fontSize: '12px', fontWeight: 'bold'})}> PCS/Pack</p>
                            </div>
                            <div className='col-md-1'>
                                <p style={({fontSize: '12px', fontWeight: 'bold'})}>
                                    {getFormattedMessage('inventory.form.no_of_boxs.label')}
                                </p>
                            </div>
                            <div className='col-md-1'>
                                <p style={({fontSize: '12px', fontWeight: 'bold'})}>
                                    {getFormattedMessage('inventory.form.net_wt.label')}
                                </p>
                            </div>
                            <div className='col-md-1'>
                                <p style={({fontSize: '12px', fontWeight: 'bold'})}>
                                    {getFormattedMessage('inventory.form.gross_wt.label')}
                                </p>
                            </div>
                            <div className='col-md-2'>
                                <p style={({fontSize: '12px', fontWeight: 'bold'})}>
                                    {getFormattedMessage('inventory.form.carton_meas.label')}
                                </p>
                            </div>
                        </div>
                        {fields.map((field, index) => (
                            <div className='row align-items-center mb-2' key={index}>
                                <div className='col-md-4'>
                                    {field.product.map((nestedProduct, nestedIndex) => (

                                        <div className="row mb-2">
                                            <div className="col-md-4">
                                                <div key={nestedIndex+index}>
                                                    <input
                                                        type='number'
                                                        className='form-control'
                                                        id='name'
                                                        name='no_of_items_per_box'
                                                        value={nestedProduct.item_per_box}
                                                        onChange={(event) => handleItemPerBoxChange(index, nestedIndex, event)}
                                                    />
                                                </div>
                                            </div>
                                            <div className="col-md-8 mb-1">
                                                <div>
                                                    <div className='d-flex' key={nestedIndex}>
                                                        <ReactSelectInventory
                                                            data={products}
                                                            onChange={(selectedOption) => handleSelectChange(index, nestedIndex, selectedOption)}
                                                            placeholder={placeholderText('inventory.select.placeholder.product')}
                                                            value={nestedProduct.product}
                                                        />
                                                        <button type="button" className="btn btn-danger btn-sm"
                                                                style={{
                                                                    height: '31px',
                                                                    marginLeft: '5px',
                                                                    marginTop: '9px'
                                                                }}
                                                                onClick={() => handleRemoveNestedProduct(index, nestedIndex)}>-
                                                        </button>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>


                                <div className="col-md-1">
                                    <button type="button" className="btn btn-primary btn-sm"
                                            onClick={() => handleAddNestedProduct(index)}>+
                                    </button>

                                </div>
                                {/* sticker meas unit*/}
                                <div className='col-md-2'>
                                    <div>
                                        <select
                                            onChange={(event) => handleInputChange(index, event)}
                                            name="sticker_meas_unit" id="" className="form-control">
                                            <option value="PCS">PCS</option>
                                            <option value="Pack">Pack</option>
                                        </select>
                                    </div>
                                </div>
                                {/* no boxes */}
                                <div className='col-md-1'>
                                    <div>
                                        <input
                                            type='number'
                                            className='form-control'
                                            id='no_boxes'
                                            name='no_boxes'
                                            value={field.no_boxes}
                                            onChange={(event) => handleInputChange(index, event)}
                                        />
                                    </div>
                                </div>
                                {/* net wt*/}
                                <div className='col-md-1'>
                                    <div>
                                        <input
                                            type='text'
                                            className='form-control'
                                            id='net_wt'
                                            name='net_wt'
                                            value={field.net_wt}
                                            onChange={(event) => handleInputChange(index, event)}
                                        />
                                    </div>
                                </div>
                                {/* Gross wt*/}
                                <div className='col-md-1'>
                                    <div>
                                        <input
                                            type='text'
                                            className='form-control'
                                            id='gross_wt'
                                            name='gross_wt'
                                            value={field.gross_wt}
                                            onChange={(event) => handleInputChange(index, event)}
                                        />
                                    </div>
                                </div>
                                {/* Carton meas*/}
                                <div className='col-md-1'>
                                    <div>
                                        <input
                                            type='text'
                                            className='form-control'
                                            id='carton_meas'
                                            name='carton_meas'
                                            value={field.carton_meas}
                                            onChange={(event) => handleInputChange(index, event)}
                                        />
                                    </div>
                                </div>
                                <div className="col-md-1">
                                    <button type="button" className="btn btn-danger btn-sm"
                                            onClick={() => handleRemoveFields(index)}>Del
                                    </button>
                                </div>
                            </div>
                        ))}

                        <ModelFooter onSubmit={onSubmit}
                                     editDisabled={disabled}
                                     link='/app/inventory'/>
                    </Form>
                </div>
            </div>
        </div>
    );
};

const mapStateToProps = (state) => {
    return {
        products: state.products
    };
};

export default connect(mapStateToProps)(CreateInventoryForm);
