import React, { useState, createRef, useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { Form, Modal } from 'react-bootstrap-v5';
import { getFormattedMessage, placeholderText } from '../../shared/sharedMethod';
import {  addUnit } from '../../store/action/unitsAction'; // You may need to create an addUnit action if it doesn't exist
import ModelFooter from '../../shared/components/modelFooter';
import { addVariant, editVariant } from '../../store/action/variantAction';
import ReactSelect from '../../shared/select/reactSelect';
import { fetchAllBaseUnits } from '../../store/action/baseUnitsAction';
import { useNavigate } from 'react-router';
import { fetchAllVariants } from '../../store/action/variantAction';

const VariantForm = (props) => {
  const {
    handleClose,
   
    fetchAllBaseUnits,
    show,
    title,
    addVariantsData,
    editVariant,
    singleUnit,
    hide,
   
  } = props;
  const innerRef = useRef();

  const [unitValue, setUnitValue] = useState({
    name: '',
    variant: '',
  });

  const [errors, setErrors] = useState({
    name: '',
  });

  const navigate = useNavigate();

  const [formValues, setFormValues] = useState([{ label: '', value: '' }]);

  const handleChange = (i, e) => {
    const newFormValues = [...formValues];
    newFormValues[i][e.target.name] = e.target.value;
    setFormValues(newFormValues);
  };

  const addFormFields = () => {
    setFormValues([...formValues, { label: '', value: '' }]);
  };

  const removeFormFields = (i) => {
    const newFormValues = [...formValues];
    newFormValues.splice(i, 1);
    setFormValues(newFormValues);
  };

  useEffect(() => {
    fetchAllBaseUnits();
  }, []);
  console.log(singleUnit , 'singleUnit.variant')

  useEffect(() => {
    if (singleUnit && singleUnit.variant) {
      setUnitValue({
        name: singleUnit.name,
        variant: singleUnit.variant,
      });
  
      // Check if the variant string has a valid format before splitting
      if (typeof singleUnit.variant === 'string') {
        const variantPairs = singleUnit.variant.split(', ');
        const dynamicFormValues = variantPairs.map((pair) => {
          const [label, value] = pair.split(': ');
          return { label, value };
        });
  
        setFormValues(dynamicFormValues);
      }
    }
  }, [singleUnit]);
  
  
  

  const disabled =
    singleUnit && singleUnit.name === unitValue.name.trim();

  const handleValidation = () => {
    const errorss = {};
    let isValid = true;

    if (!unitValue['name'].trim()) {
      errorss['name'] = getFormattedMessage('globally.input.name.validate.label');
      isValid = false;
    }

    formValues.forEach((field, index) => {
      if (!field.label || !field.value) {
        isValid = false;
        errorss[`dynamicField_${index}`] = 'Label and value are required';
      }
    });

    setErrors(errorss);
    return isValid;
  };

  const onChangeInput = (e) => {
    e.preventDefault();
    setUnitValue((inputs) => ({ ...inputs, [e.target.name]: e.target.value }));
    setErrors('');
  };

  const prepareFormData = () => {
    const formData = new FormData();
    
    formData.append('name', unitValue.name);

    formValues.forEach((field) => {
      if (field.label.trim() && field.value.trim()) {
        formData.append(`variant[${field.label.trim()}]`, field.value.trim());
      }
    });

    return formData;
  };

  const onSubmit = (event) => {
    event.preventDefault();
    const valid = handleValidation();
    
    if (singleUnit && valid) {
    //   if (!disabled) {
        editVariant(singleUnit.id, prepareFormData(), handleClose);
        clearField(false);
    //   }
    } else if (valid) {
      setUnitValue({ name: '', variant: '' });
      setErrors('');
      addVariantsData(prepareFormData()); 
      clearField(false);
    }
  };

  const clearField = () => {
    setUnitValue({ name: '', variant: '' });
    setErrors('');
    handleClose ? handleClose(false) : hide(false);
  };

  return (
    <Modal
      show={show}
      onHide={clearField}
      keyboard={true}
      onShow={() =>
        setTimeout(() => {
          innerRef.current.focus();
        }, 1)
      }
    >
      <Form
        onKeyPress={(e) => {
          if (e.key === 'Enter') {
            onSubmit(e);
          }
        }}
      >
        <Modal.Header closeButton>
          <Modal.Title>{title}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="row">
            <div className="col-md-12 mb-3">
              <label className="form-label">
                {getFormattedMessage('globally.input.name.label')}:{' '}
              </label>
              <span className="required" />
              <input
                type="text"
                name="name"
                value={unitValue.name}
                placeholder={placeholderText(
                  'globally.input.name.placeholder.label'
                )}
                className="form-control"
                ref={innerRef}
                autoComplete="off"
                onChange={(e) => onChangeInput(e)}
              />
              <span className="text-danger d-block fw-400 fs-small mt-2">
                {errors['name'] ? errors['name'] : null}
              </span>
            </div>

            <div>
              {formValues.map((element, index) => (
                <div className="form-inline" key={index}>
                  <label className="form-label">Label</label>
                  <input
                    type="text"
                    className="form-control"
                    name="label"
                    value={element.label || ''}
                    onChange={(e) => handleChange(index, e)}
                  />
                  <label className="form-label">Value</label>
                  <input
                    type="text"
                    className="form-control"
                    name="value"
                    value={element.value || ''}
                    onChange={(e) => handleChange(index, e)}
                  />
                  {index ? (
                    <button
                      type="button"
                      className="btn btn-danger remove"
                      onClick={() => removeFormFields(index)}
                    >
                      Remove
                    </button>
                  ) : null}
                  <span className="text-danger d-block fw-400 fs-small mt-2">
                    {errors[`dynamicField_${index}`]
                      ? errors[`dynamicField_${index}`]
                      : null}
                  </span>
                </div>
              ))}
              <br />
              <button
                className="btn btn-success add"
                type="button"
                onClick={addFormFields}
              >
                Add
              </button>
            </div>
          </div>
        </Modal.Body>
      </Form>
      <ModelFooter
        onEditRecord={singleUnit}
        onSubmit={onSubmit}
        // editDisabled={disabled}
        clearField={clearField}
        addDisabled={!unitValue.name.trim()}
      />
    </Modal>
  );
};

const mapStateToProps = (state) => {
  const { base } = state;
  return { base };
};

export default connect(mapStateToProps, {
  fetchAllBaseUnits,
  fetchAllVariants,
  editVariant,
})(VariantForm);
