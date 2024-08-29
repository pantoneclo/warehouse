import React, {useState} from 'react';
import Form from 'react-bootstrap/Form';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ReactSelectInventory from "../../shared/select/reactSelectInventory";

import ModelFooter from '../../shared/components/modelFooter';
import {connect, useDispatch} from 'react-redux';


const CreateComboForm = (props) => {
    const {products, addComboData} = props;
    const dispatch = useDispatch();
    const [comboName, setComboName] = useState('');

    const handleComboNameChange = (event) => {
        setComboName(event.target.value);
    };


    const [fields, setFields] = useState([{
        combo_name:'',
        product_id: null,
        product: [{product: null, item_per_box: 0}],
        carton_meas: 0,
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
          
            product_id: null,
            product: [{product: null, item_per_box: 0}],
         
            carton_meas: 0,
           
        }]);
        console.log(fields);
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
        formData.append('combo_name', comboName);
        data.forEach((item, index) => {
            item.product.forEach((nestedProduct, nestedIndex) => {
                if (nestedProduct.product) {
                    formData.append(`items[${index}][product][${nestedIndex}][product_id]`, nestedProduct.product.value);
                }
            });
        });
        return formData;
    };
    const onSubmit = (event) => {
        event.preventDefault();
        const formData = prepareFormData(fields);
        addComboData(formData);
    };

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

                    <div className=''>
                        <div>
                            <input
                                type='text'
                                className='form-control'
                                id='combo_name'
                                name='combo_name'
                                value={comboName}
                                onChange={handleComboNameChange}
                            />
                        </div>
                    </div>

                        <div className='row'>
                            <div className="col-md-8">
                                <div className="row">
                                    
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
                                <p style={({fontSize: '12px', fontWeight: 'bold'})}>
                                    {getFormattedMessage('inventory.form.carton_meas.label')}
                                </p>
                            </div>
                            <div className='col-md-1'></div>
                        </div>
                        {fields.map((field, index) => (
                            <div className='row align-items-center mb-4' key={index}>
                                <div className='col-md-8'>
                                    {field.product.map((nestedProduct, nestedIndex) => (

                                       
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
                                           
                                    ))}
                                </div>


                                <div className="col-md-1">
                                    <button type="button" className="btn btn-primary btn-sm"
                                            onClick={() => handleAddNestedProduct(index)}>+
                                    </button>

                                </div>
                               
                               
                        
                                <div className='col-md-2'>
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
                                     link='/app/combo'/>
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

export default connect(mapStateToProps)(CreateComboForm);
