import React, {useEffect, useState} from 'react';
import Form from 'react-bootstrap/Form';
import {getFormattedMessage, placeholderText} from '../../shared/sharedMethod';
import ReactSelectInventory from "../../shared/select/reactSelectInventory";
import ReactSelect from '../../shared/select/reactSelect';
import ModelFooter from '../../shared/components/modelFooter';
import {fetchAllWarehouses} from '../../store/action/warehouseAction';
import {connect, useDispatch} from 'react-redux';


const CreateComboForm = (props) => {
    const {combos, products, warehouses, addComboData, fetchAllWarehouses} = props;
    const dispatch = useDispatch();
    
    const [comboName, setComboName] = useState('');

    const handleComboNameChange = (event) => {
        setComboName(event.target.value);
    };

    console.log('All Products', products);
    console.log('Edit Combo', combos[0]);
    const [fields, setFields] = useState([{
        combo_name:'',
        product_id: null,
        product: [{product: null}],
    }]);
    // If editing, populate the form fields with combo data
    useEffect(() => {
        if (combos[0]) {
            setComboName(combos[0].attributes?.name || '');
            
            // Set products to edit (assuming combo structure allows this)
            setFields(combos[0].attributes?.products.map(product => ({
                
                product: product.products.map(nested => ({ 
                    product_id: nested.id,
                    product: nested.attributes, 
                }))
            })) || []);
        }
    }, [combos[0]]);
    

    const [warehouseValue, setwarehouseValue] = useState({
        warehouse_id: '',
       
    });

    const [filteredProducts, setFilteredProducts] = useState([]);

    const filterProductsByWarehouse = (warehouseId)=>{
       const filtredProducts = products.filter((product)=>
        product.attributes.warehouse.some((wh) => wh.id === warehouseId)
        )
        setFilteredProducts(filtredProducts) 
    }

    useEffect(() => {
        fetchAllWarehouses();
    }, [fetchAllWarehouses]);


    useEffect(() => {
        console.log('Warehouse ID changed:', warehouseValue.warehouse_id);
    }, [warehouseValue.warehouse_id])
   console.log("Warehouse", warehouses)
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
            product: [{product: null}],
           
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
        formData.append('warehouse_id', warehouseValue.warehouse_id.value);
        data.forEach((item, index) => {
            item.product.forEach((nestedProduct, nestedIndex) => {
                if (nestedProduct.product) {
                    formData.append(`items[${index}][product][${nestedIndex}][product_id]`, nestedProduct.product.value);
                }
            });
        });
        return formData;
    };

    const [errors, setErrors] = useState({
        warehouse_id: '',
        products:''
       
    });
    const onWarehouseChange = (input)=>{
        setwarehouseValue(inputs => ({ ...inputs, warehouse_id: input }));
        setErrors('');

       
        console.log('Updated warehouseValue:', { ...warehouseValue, warehouse_id: input });
        console.log('Products:', products);
    }

    const handleValidation = () => {
        let error = {};
        let isValid = true;
        if (!fields[0].product) {
            error['products'] = getFormattedMessage('globally.date.validate.label');
            isValid = false; // Set isValid to false if there's an error
        } 
        if (!warehouseValue.warehouse_id) {
            error['warehouse_id'] = getFormattedMessage('product.input.warehouse.validate.label');
            isValid = false; // Set isValid to false if there's an error
        }
        setErrors(error);
        return isValid;
    };


    const onSubmit = (event) => {
        event.preventDefault();
        const valid = handleValidation();
        const formData = prepareFormData(fields);
        if(valid){
            addComboData(formData);
        }
       
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

                    <div className='row'>
                        <div className='col-md-8'>
                            <input
                                type='text'
                                className='form-control'
                                id='combo_name'
                                name='combo_name'
                                value={comboName}
                                onChange={handleComboNameChange}
                            />
                        </div>

                        <div className="col-md-4" style={{ zIndex: 500 }}>
                        <ReactSelect name='warehouse_id' data={warehouses} onChange={onWarehouseChange}
                            title={getFormattedMessage('warehouse.title')} errors={errors['warehouse_id']}
                            defaultValue={warehouseValue.warehouse_id} value={warehouseValue.warehouse_id}
                            isWarehouseDisable={true}
                            placeholder={placeholderText('purchase.select.warehouse.placeholder.label')} />   

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
        products: state.products,
        warehouses: state.warehouses,
        combos: state.combos
    };
};

export default connect(mapStateToProps, {fetchAllWarehouses})(CreateComboForm);
