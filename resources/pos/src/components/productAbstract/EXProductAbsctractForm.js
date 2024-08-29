import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import Form from 'react-bootstrap/Form';
import Select from 'react-select';
import CreatableSelect from 'react-select/creatable';
import { InputGroup, Button, NavItem } from 'react-bootstrap-v5';
// import MultipleImage from './MultipleImage';
import { fetchUnits } from '../../store/action/unitsAction';
import { fetchAllProductCategories } from '../../store/action/productCategoryAction';
import { fetchAllBrands } from '../../store/action/brandsAction';

import { productUnitDropdown } from '../../store/action/productUnitAction';
import { decimalValidate, getFormattedMessage, getFormattedOptions, placeholderText } from '../../shared/sharedMethod';
import taxes from '../../shared/option-lists/taxType.json';
import barcodes from '../../shared/option-lists/barcode.json';
import ModelFooter from '../../shared/components/modelFooter';
import ReactSelect from '../../shared/select/reactSelect';
import { saleStatusOptions, taxMethodOptions } from '../../constants';
import { fetchAllWarehouses } from "../../store/action/warehouseAction";
import { fetchAllSuppliers } from "../../store/action/supplierAction";
import moment from "moment";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faUserPlus } from '@fortawesome/free-solid-svg-icons';
import BrandsFrom from '../brands/BrandsFrom';
import { addBrand } from '../../store/action/brandsAction';
import UnitsForm from '../units/UnitsForm';
import { addUnit } from '../../store/action/unitsAction';
import ProductAbstract from './ProductAbstract';
// import Select from 'react-select';
import { fetchProductAbstract, editProductAbstraction } from '../../store/action/productAbstractAction';
import _ from 'lodash';
import { fr } from 'date-fns/locale';

import MultipleImage from '../product/MultipleImage';



const ProductAbsctractForm = (props) => {
    const {
        addProductAbstractData,
        warehouses,
        suppliers,
        id,
        editProductAbstraction,

        singleProduct,
        brands,
        fetchAllBrands,
        fetchAllProductCategories,
        productCategories,
        fetchUnits,
        productUnits,
        productUnitDropdown,
        frontSetting,
        fetchAllWarehouses,
        fetchAllSuppliers,
        addBrand,
        addUnit,
        baseUnits,
        productUnit
    } = props;


    const navigate = useNavigate();
    const [productValues, setProductValues] = useState([]);
    const [productValue, setProductValue] = useState({
        date: new Date(),
        name: '',
        pan_style: '',
        product_category_id: '',
        attributes: '',
        brand_id: '',
        product_unit: '',
        sale_unit: '',
        purchase_unit: '',
        order_tax: 0,
        tax_type: '',
        notes: '',
        images: [],
        isEdit: false,
        products: [],
    });

    const [removedImage, setRemovedImage] = useState([])
    const [removedImage1, setRemovedImage1] = useState([])
    const [isClearDropdown, setIsClearDropdown] = useState(true);
    const [isDropdown, setIsDropdown] = useState(true);
    const [multipleFiles, setMultipleFiles] = useState([]);
    const [multipleFiles1, setMultipleFiles1] = useState([]);
    const [errors, setErrors] = useState({
        name: '',
        pan_style: '',
        product_category_id: '',
        attributes: [],

        brand_id: '',
        product_unit: '',
        sale_unit: '',
        purchase_unit: '',
        stock_alert: '',
        order_tax: '',
        tax_type: '',
        notes: '',
        images: [],
    });

    const [variantsElement, setVariantsElement] = useState([]);
    const [sizeOptions, setSizeOptions] = useState([]);
    const [colorOptions, setColorOptions] = useState([]);
    const [sizeOptionsValue, setSizeOptionsValue] = useState([]);
    const [colorOptionsValue, setColorOptionsValue] = useState([]);
    const [variants, setVariants] = useState(
        {
            size: [],
            color: []
        }
    );

    const [openForms, setOpenForms] = useState([]);
    const attr = ['size', 'color']
    const attributeOptions = attr.map((attribute) => ({ value: attribute, label: attribute }))
    // const attributeOptions = [
    //     { value: 'size', label: 'Size' },
    //     { value: 'color', label: 'Color' },
    //   ];
    // Simulate the attribute string data

    useEffect(() => {
        fetchAllBrands();
        fetchAllProductCategories();
        fetchUnits();
        fetchAllWarehouses();
        fetchAllSuppliers();
    }, []);

    useEffect(() => {
        if (singleProduct && productUnit) {
            productUnitDropdown(productUnit[0]?.id)
        }
    }, [])

    const newTax = singleProduct && taxes.filter((tax) => singleProduct[0].tax_type === tax.value);

    // const newBarcode = singleProduct && barcodes.filter((barcode) => singleProduct[0].barcode_symbol.toString() === barcode.value);
    const disabled = multipleFiles.length !== 0 ? false : singleProduct && productValue.product_unit[0] && productValue.product_unit[0].value === singleProduct[0].product_unit && productValue.barcode_symbol[0] && productValue.barcode_symbol[0].value === singleProduct[0].barcode_symbol.toString() && singleProduct[0].name === productValue.name && singleProduct[0].notes === productValue.notes && singleProduct[0].product_price === productValue.product_price && singleProduct[0]?.stock_alert?.toString() === productValue.stock_alert && singleProduct[0].product_cost === productValue.product_cost && singleProduct[0].code === productValue.code && JSON.stringify(singleProduct[0].order_tax) === productValue.order_tax && singleProduct[0].quantity_limit === productValue.sale_quantity_limit && singleProduct[0].brand_id.value === productValue.brand_id.value && newTax.length === productValue.tax_type.length && singleProduct[0].product_category_id.value === productValue.product_category_id.value && JSON.stringify(singleProduct[0].images.imageUrls) === JSON.stringify(removedImage)
    const [selectedBrand] = useState(singleProduct && singleProduct[0] ? ([{
        label: singleProduct[0].brand_id.label, value: singleProduct[0].brand_id.value
    }]) : null);
    // console.log('singleProduct[0].attributes.attributes', singleProduct[0]);
    const [defaultOption] = useState(() => {


        if (singleProduct && singleProduct[0] && !Array.isArray(singleProduct[0].attributes) && typeof singleProduct[0].attributes === 'object') {
            // console.log('singleProduct[0].attributes.attributes X', singleProduct[0].attributes);

            const parsedAttributes = (singleProduct[0].attributes);
            return Object.entries(parsedAttributes).map(([key, value]) => ({
                value: key.toLowerCase(),
                label: key,
            }));
        }
        return null;
    });
    console.log(defaultOption, 'defaultOption')



    const [selectedProductCategory] = useState(singleProduct && singleProduct[0] ? ([{
        label: singleProduct[0].product_category_id.label, value: singleProduct[0].product_category_id.value
    }]) : null);

    const [selectedTax] = useState(newTax && newTax[0] ? ([{ label: newTax[0].label, value: newTax[0].value }]) : null);

    const saleUnitOption = productUnits && productUnits.length && productUnits.map((productUnit) => {
        return { value: productUnit?.id, label: productUnit.attributes.name }
    });

    useEffect(() => {
        if (singleProduct) {
            setProductValue({
                name: singleProduct ? singleProduct[0].name : '',
                pan_style: singleProduct ? singleProduct[0].pan_style : '',
                attributes: singleProduct ? singleProduct[0].attributes : '',
                product_category_id: singleProduct ? singleProduct[0].product_category_id : '',
                brand_id: singleProduct ? singleProduct[0].brand_id : '',
                product_unit: singleProduct ? { value: productUnit[0]?.id, label: productUnit[0].attributes.name } : '',
                sale_unit: singleProduct ? singleProduct[0].sale_unit : '',
                purchase_unit: singleProduct ? singleProduct[0].purchase_unit && singleProduct[0].purchase_unit : '',
                order_tax: singleProduct ? singleProduct[0].order_tax ? JSON.stringify(singleProduct[0].order_tax) : 0 : 0,
                tax_type: newTax,
                notes: singleProduct ? singleProduct[0].notes : '',
                images: singleProduct ? singleProduct[0].images : '',
                isEdit: singleProduct ? singleProduct[0].is_Edit : false,
                products: singleProduct ? singleProduct[0].products : ''
            })
        }
    }, []);

    console.log(singleProduct, 'singleProduct')

    const onChangeFiles = (file) => {
        setMultipleFiles(file);
    };

    const transferImage = (item) => {
        setRemovedImage(item);
        setMultipleFiles([])
    };
    const transferImage1 = (item) => {
        setRemovedImage1(item);
        setMultipleFiles1([])
    };


    const handleProductUnitChange = (obj) => {
        productUnitDropdown(obj.value);
        setIsClearDropdown(false);
        setIsDropdown(false);
        setProductValue({ ...productValue, product_unit: obj });
        setErrors('');
    };

    const handleSaleUnitChange = (obj) => {
        setIsClearDropdown(true);
        setProductValue({ ...productValue, sale_unit: obj });
        setErrors('');
    };

    const handlePurchaseUnitChange = (obj) => {
        setIsDropdown(true);
        setProductValue({ ...productValue, purchase_unit: obj });
        setErrors('');
    };

    const onBrandChange = (obj) => {
        setProductValue(productValue => ({ ...productValue, brand_id: obj }));
        setErrors('');
    };

    const onBarcodeChange = (obj) => {
        setProductValue(productValue => ({ ...productValue, barcode_symbol: obj }));
        setErrors('');
    };

    const onProductCategoryChange = (obj) => {
        setProductValue(productValue => ({ ...productValue, product_category_id: obj }));
        setErrors('');
    };

    // console.log(frontSetting?.value?.possible_variant_list.size, 'frontSetting')

    useEffect(() => {
        const sizeOption = frontSetting?.value?.possible_variant_list.size;
        const sizeOptions = sizeOption?.map((size) => ({ value: size, label: size }));
        console.log({ sizeOptions });
        setSizeOptions(sizeOptions);

        setSizeOptionsValue(singleProduct && singleProduct[0] && singleProduct[0]?.attributes['size'] &&
            singleProduct[0]?.attributes['size'].map((item, index) => {
                // Create and return a new object based on each item in the original array
                return {
                    // define the properties of your new object here
                    label: item,
                    value: item,
                    // ...
                };
            }));

    }, [frontSetting]);
    useEffect(() => {
        const colorOption = frontSetting?.value?.possible_variant_list.color;
        const colorOptions = colorOption?.map((color) => ({ value: color, label: color }));
        setColorOptions(colorOptions);

        setColorOptionsValue(singleProduct && singleProduct[0] && singleProduct[0]?.attributes['color'] &&
            singleProduct[0]?.attributes['color'].map((item, index) => {
                // Create and return a new object based on each item in the original array
                return {
                    // define the properties of your new object here
                    label: item,
                    value: item,
                    // ...
                };
            }));

    }, [frontSetting]);

    useEffect(() => {
        setProductValue((productValue) => ({
            ...productValue,
            attributes: variants,
        }));
    }, [variants]);


    useEffect(() => {
        if (singleProduct && singleProduct[0]) {
            handleAttributeChange(defaultOption);
        }


    }, [sizeOptionsValue, colorOptionsValue]);

    const handleAttributeChange = (newAttributes) => {
        // console.log(newAttributes, 'newAttributes')

        const variantsElements = newAttributes.map((item, index) => (

            <div className='col-12 mb-3' key={index}>
                <label
                    className='form-label' htmlFor={`select-${index}`}>{_.startCase(item.value)}</label>
                <CreatableSelect
                    id={`select-${index}`}
                    // title={"hii"+_.startCase(item.value)}
                    placeholder={`Select ${_.startCase(item.value)}`}
                    isClearable
                    isDisabled={false}
                    isLoading={false}
                    isMulti
                    options={item?.value === 'size' ? sizeOptions : colorOptions}
                    onChange={(newValue) => handleSelectChange(index, newValue)}
                    value={item?.value === 'size' ? sizeOptionsValue : colorOptionsValue}


                />
            </div>
        ));
        // console.log({ variantsElements });
        setVariantsElement(variantsElements);
        setErrors('');
    };
    const handleSelectChange = (index, newValue) => {
        console.log({ newValue ,index } );
        if (index === 0) {
            setVariants((variants) => ({
                ...variants,
                size: newValue,
            }));

            setSizeOptionsValue(newValue)
        } else {
            setVariants((variants) => ({
                ...variants,
                color: newValue,
            }));
            setColorOptionsValue(newValue)
        }
        setProductValue((productValue) => ({
            ...productValue,
            attributes: variants,
        }));

    };
    console.log(variants, 'variants')

    const taxTypeFilterOptions = getFormattedOptions(taxMethodOptions)
    const [taxType, setTaxType] = useState(singleProduct ? singleProduct[0].tax_type === '1' ? {
        value: 1, label: getFormattedMessage("tax-type.filter.exclusive.label")
    } : {
        value: 2, label: getFormattedMessage("tax-type.filter.inclusive.label")
    } || singleProduct[0].tax_type === '2' ? {
        value: 2, label: getFormattedMessage("tax-type.filter.inclusive.label")
    } : {
        value: 1, label: getFormattedMessage("tax-type.filter.exclusive.label")
    } : '');

    const defaultTaxType = singleProduct ? singleProduct[0].tax_type === '1' ? {
        value: 1, label: getFormattedMessage("tax-type.filter.exclusive.label")
    } : {
        value: 2, label: getFormattedMessage("tax-type.filter.inclusive.label")
    } || singleProduct[0].tax_type === '2' ? {
        value: 2, label: getFormattedMessage("tax-type.filter.inclusive.label")
    } : {
        value: 1, label: getFormattedMessage("tax-type.filter.exclusive.label")
    } : {
        value: 1, label: getFormattedMessage("tax-type.filter.exclusive.label")
    }

    const onTaxTypeChange = (obj) => {
        setProductValue(productValue => ({ ...productValue, tax_type: obj }));
        setErrors('');
    };

    const onWarehouseChange = (obj) => {
        setProductValue(inputs => ({ ...inputs, warehouse_id: obj }))
        setErrors('')
    };

    const onSupplierChange = (obj) => {
        setProductValue(inputs => ({ ...inputs, supplier_id: obj }))
        setErrors('');
    };

    const onStatusChange = (obj) => {
        setProductValue(inputs => ({ ...inputs, status_id: obj }))
    };


    const statusFilterOptions = getFormattedOptions(saleStatusOptions)
    const statusDefaultValue = statusFilterOptions.map((option) => {
        return {
            value: option.id,
            label: option.name
        }
    })
    const handleValidation = () => {
        let errorss = {};
        let isValid = false;
        if (!productValue['name']) {
            errorss['name'] = getFormattedMessage('globally.input.name.validate.label');
        }
        else if (!productValue['pan_style']) {
            errorss['pan_style'] = getFormattedMessage('globally.input.panstyle.valid.validate.label');
        }
        else if (!productValue['product_category_id']) {
            errorss['product_category_id'] = getFormattedMessage('product.input.product-category.validate.label');
        } else if (!productValue['brand_id']) {
            errorss['brand_id'] = getFormattedMessage('product.input.brand.validate.label');
        }
        else if (!productValue['product_unit']) {
            errorss['product_unit'] = getFormattedMessage('product.input.product-unit.validate.label');
        } else if (!productValue['sale_unit']) {
            errorss['sale_unit'] = getFormattedMessage('product.input.sale-unit.validate.label');
        } else if (isClearDropdown === false) {
            errorss['sale_unit'] = getFormattedMessage('product.input.sale-unit.validate.label');
        } else if (!productValue['purchase_unit']) {
            errorss['purchase_unit'] = getFormattedMessage('product.input.purchase-unit.validate.label');
        } else if (isDropdown === false) {
            errorss['purchase_unit'] = getFormattedMessage('product.input.purchase-unit.validate.label');
        } else if (productValue['order_tax'] > 100) {
            errorss['order_tax'] = getFormattedMessage('product.input.order-tax.valid.validate.label');
        } else if (productValue['notes'] && productValue['notes'].length > 100) {
            errorss['notes'] = getFormattedMessage('globally.input.notes.validate.label');
        }
        else {
            isValid = true;
        }
        setErrors(errorss);
        return isValid;
    };
    console.log(productValue, 'productValue come from random')
    const onChangeInput = (e) => {

        e.preventDefault();
        const { value } = e.target;
        // check if value includes a decimal point
        if (value.match(/\./g)) {
            const [, decimal] = value.split('.');
            // restrict value to only 2 decimal places
            if (decimal?.length > 2) {
                // do nothing
                return;
            }
        }
        setProductValue(inputs => ({ ...inputs, [e.target.name]: value }))
        setErrors('');
    };



    // let manage_purchase_stock = [];
    // manage_purchase_stock.push(productValue?.add_stock, productValue?.supplier_id?.value, productValue?.warehouse_id?.value, productValue?.status_id?.value)

    const [creatBrandModel, setCreatBrandModel] = useState(false);
    const customerModel = (val) => {
        setCreatBrandModel(val)
    }

    const addBrandData = (formValue) => {
        addBrand(formValue, Filters.OBJ)
    };

    const [abstractModel, setAbstractModel] = useState(false);
    const showAbstractModel = (val) => {
        setAbstractModel(val)
    }
    const [unitModel, setUnitModel] = useState(false);
    const showUnitModel = (val) => {
        setUnitModel(val)
    }

    const addUnitsData = (productValue) => {
        addUnit(productValue);
    };

    const prepareFormData = (data) => {
        console.log('come from prepare form data', data.products);

        // const productsJSON = JSON.stringify(data.products);

        const formData = new FormData();
        formData.append('name', data.name);
        formData.append('pan_style', data.pan_style);
        formData.append('product_category_id', data.product_category_id.value);
        formData.append('brand_id', data.brand_id.value);

        data.products.forEach((product, product_index) => {
            formData.append(`products[${product_index}][variant_name]`, product.variant_name);
            formData.append(`products[${product_index}][variant][color]`, product.variant.color);
            formData.append(`products[${product_index}][variant][size]`, product.variant.size);
            formData.append(`products[${product_index}][product_cost]`, product.product_cost);
            formData.append(`products[${product_index}][product_price]`, product.product_price);
            formData.append(`products[${product_index}][stock_alert]`, product.stock_alert);
            formData.append(`products[${product_index}][quantity_limit]`, product.quantity_limit);

            product.product_image.forEach((image, image_index) => {
                formData.append(`products[${product_index}][product_image][${image_index}]`, image);
            })

        });

        // //this daata backend aspect as a array but go as string
        const transformedData = {};
        for (const key in data.attributes) {
            if (Object.hasOwnProperty.call(data.attributes, key)) {
                // Extract the "value" from each object within the array
                transformedData[key] = data.attributes[key].map(item => item.value);
            }
        }

        // console.log(transformedData, 'transformedData')
        formData.append('attributes', JSON.stringify(transformedData));


        formData.append('product_unit', data.product_unit && data.product_unit[0] ? data.product_unit[0].value : data.product_unit.value);
        formData.append('sale_unit', data.sale_unit && data.sale_unit[0] ? data.sale_unit[0].value : data.sale_unit.value);
        formData.append('purchase_unit', data.purchase_unit && data.purchase_unit[0] ? data.purchase_unit[0].value : data.purchase_unit.value);
        formData.append('order_tax', data.order_tax ? data.order_tax : "");
        formData.append('quantity_limit', data.sale_quantity_limit ? data.sale_quantity_limit : "");
        formData.append('barcode_symbol', 1);
        // formData.append('products' )
        if (data.tax_type[0]) {
            formData.append('tax_type', data.tax_type[0].value ? data.tax_type[0].value : 1);
        } else {
            formData.append('tax_type', data.tax_type.value ? data.tax_type.value : 1);
        }
        formData.append('notes', data.notes);

        if (multipleFiles) {
            multipleFiles.forEach((image, index) => {
                formData.append(`images[${index}]`, image);
            })
        }

        return formData;
    };
    // console.log(variantsElement , 'variantsElement')

    const onSubmit = (event) => {
        event.preventDefault();
        const valid = handleValidation();
        productValue.images = multipleFiles;



        if (singleProduct && valid && isClearDropdown === true && isDropdown === true) {
            if (!disabled) {
                editProductAbstraction(id, prepareFormData(productValue), navigate);
            }
        } else {
            if (valid) {
                productValue.images = multipleFiles;

                setProductValue(productValue);
                addProductAbstractData(prepareFormData(productValue), navigate);
            }
        }

    };


    // Function to add a new ProductAddForm
    const addProductForm = () => {
        setOpenForms([...openForms, Date.now()]); // Using Date.now() as a unique key
    };

    // Function to remove a ProductAddForm
    const removeProductForm = (key) => {
        setOpenForms(openForms.filter((variant_name) => variant_name !== key));
    };







    // Create a ref to track previous variants
    const prevVariantsRef = useRef();

    useEffect(() => {
        // Check if variants have changed
        if (prevVariantsRef.current !== variants) {
            // Create an array of all possible variant_names based on current variants
            const allvariant_names = variants.size.flatMap((sizeOption) =>
                variants.color.map((colorOption) => `${colorOption.value}-${sizeOption.value}`)
            );

            console.log({ allvariant_names })

            // Initialize productValues with all variant_names, preserving values for existing options
            const initialProductValues = allvariant_names.map((variant_name) => {
                const existingProductValue = productValues.find((pv) => pv.variant_name === variant_name);
                console.log(existingProductValue, 'existingProductValue')
                if (existingProductValue) {
                    return existingProductValue;
                } else {
                    const [color, size] = variant_name.split('-');


                    return {
                        variant_name,
                        variant: {
                            color: color,
                            size: size,
                        },
                        product_cost: '',
                        product_price: '',
                        stock_alert: '',
                        quantity_limit: '',
                        product_image: []
                    };
                }
            });

            setProductValues(initialProductValues);

            // Update the ref to the current variants
            prevVariantsRef.current = variants;


        }
        setProductValue((productValue) => ({
            ...productValue,
            products: productValues,
        }));


    }, [variants, productValues]);

    console.log(productValues, 'productValues');

    // Function to handle changes in a specific product field
    const handleProductFieldValueChange = (variant_name, fieldName, value, file) => {
        setProductValues((prevProductValues) =>
            prevProductValues.map((productValue) =>
                productValue.variant_name === variant_name
                    ? { ...productValue, [fieldName]: value }
                    : productValue
            )
        );
    };


    //i will work from here tomorrow

    console.log(productValue.products, 'this is come from product value 588 linr')
    console.log(productValue, 'this is come from product value 588 linr value')
    return (
        <div className='card'>

            <div className='card-body'>
                {/* <Form> */}
                <div className='row'>
                    <div className='col-xl-12'>
                        <div className='card'>
                            <div className='row'>

                                <div className='col-md-6 mb-3'>
                                    <label
                                        className='form-label'>{getFormattedMessage('globally.input.name.label')}: </label>
                                    <span className='required' />
                                    <input type='text' name='name' value={productValue.name}
                                        placeholder={placeholderText('globally.input.name.placeholder.label')}
                                        className='form-control' autoFocus={true}
                                        onChange={(e) => onChangeInput(e)} />
                                    <span
                                        className='text-danger d-block fw-400 fs-small mt-2'>{errors['name'] ? errors['name'] : null}</span>
                                </div>

                                <div className='col-md-6 mb-3'>
                                    <label
                                        className='form-label'>{getFormattedMessage('globally.input.panStyle.label')}: </label>
                                    <span className='required' />
                                    <input type='text' name='pan_style' value={productValue.pan_style}
                                        placeholder={placeholderText('globally.input.panStyle.placeholder')}
                                        className='form-control'
                                        onChange={(e) => onChangeInput(e)} />
                                    <span
                                        className='text-danger d-block fw-400 fs-small mt-2'>{errors['pan_style'] ? errors['pan_style'] : null}</span>
                                </div>




                                <div className='col-md-6 mb-3'>
                                    <ReactSelect title={getFormattedMessage('product.input.product-category.label')}
                                        placeholder={placeholderText('product.input.product-category.placeholder.label')}
                                        defaultValue={selectedProductCategory}
                                        value={productValue.product_category_id}
                                        data={productCategories} onChange={onProductCategoryChange}
                                        errors={errors['product_category_id']} />
                                </div>
                                <div className='col-md-6 mb-3'>
                                    <ReactSelect title={getFormattedMessage('product.input.brand.label')}
                                        placeholder={placeholderText('product.input.brand.placeholder.label')}
                                        defaultValue={selectedBrand} errors={errors['brand_id']}
                                        data={brands} onChange={onBrandChange}
                                        value={productValue.brand_id} />
                                </div>


                                <div className='col-md-6 mb-3'>
                                    <InputGroup className='flex-nowrap dropdown-side-btn'>
                                        <ReactSelect
                                            className='position-relative'
                                            title={getFormattedMessage("product.input.product-unit.label")}
                                            placeholder={placeholderText('product.input.product-unit.placeholder.label')}
                                            defaultValue={productValue.product_unit}
                                            value={productValue.product_unit}
                                            data={baseUnits}
                                            errors={errors['product_unit']}
                                            onChange={handleProductUnitChange} />
                                        <Button onClick={() => showUnitModel(true)} className='position-absolute model-dtn'><FontAwesomeIcon
                                            icon={faPlus} /></Button></InputGroup>
                                </div>

                                <div className='col-md-6 mb-3'>
                                    <ReactSelect
                                        className='position-relative'
                                        title={getFormattedMessage("product.input.sale-unit.label")}
                                        placeholder={placeholderText('product.input.sale-unit.placeholder.label')}
                                        value={isClearDropdown === false ? '' : productValue.sale_unit}
                                        data={saleUnitOption}
                                        errors={errors['sale_unit']}
                                        onChange={handleSaleUnitChange} />
                                </div>
                                <div className='col-md-6 mb-3'>
                                    <ReactSelect
                                        className='position-relative'
                                        title={getFormattedMessage("product.input.purchase-unit.label")}
                                        placeholder={placeholderText('product.input.purchase-unit.placeholder.label')}
                                        value={isDropdown === false ? '' : productValue.purchase_unit}
                                        data={saleUnitOption}
                                        errors={errors['purchase_unit']}
                                        onChange={handlePurchaseUnitChange} />
                                </div>

                                <div className='col-md-6 mb-3'>
                                    <label
                                        className='form-label'>{getFormattedMessage('product.input.order-tax.label')}: </label>
                                    <InputGroup>
                                        <input type='text' name='order_tax' className='form-control'
                                            placeholder={placeholderText('product.input.order-tax.placeholder.label')}
                                            onKeyPress={(event) => decimalValidate(event)}
                                            onChange={(e) => onChangeInput(e)}
                                            min={0} pattern='[0-9]*' value={productValue.order_tax} />
                                        <InputGroup.Text>%</InputGroup.Text>
                                    </InputGroup>
                                    <span
                                        className='text-danger d-block fw-400 fs-small mt-2'>{errors['order_tax'] ? errors['order_tax'] : null}</span>
                                </div>
                                <div className='col-md-6 mb-3'>
                                    <ReactSelect title={getFormattedMessage('product.input.tax-type.label')}
                                        multiLanguageOption={taxTypeFilterOptions}
                                        onChange={onTaxTypeChange} errors={errors['tax_type']}
                                        defaultValue={defaultTaxType}
                                        placeholder={placeholderText("product.input.tax-type.placeholder.label")}
                                    />
                                </div>


                                <div className='col-md-6 mb-3'>
                                    <label
                                        className='form-label'>{"Attributes"}: </label>
                                    <span className='required' />
                                    <Select
                                        closeMenuOnSelect={false}
                                        defaultValue={defaultOption}
                                        isMulti
                                        //   onChange={(values) => console.log(values)}
                                        options={attributeOptions}
                                        onChange={handleAttributeChange}
                                    // defaultData = {handleAttributeChange(defaultOption)}

                                    />


                                    <span
                                        className='text-danger d-block fw-400 fs-small mt-2'>{errors['name'] ? errors['name'] : null}</span>
                                </div>
                                <div className='col-xl-6'>
                                    <div className='card'>
                                        <label className='form-label'>
                                            {getFormattedMessage('product.input.multiple-image.label')}: </label>
                                        <MultipleImage product={singleProduct} fetchFiles={onChangeFiles}
                                            transferImage={transferImage} />
                                    </div>
                                </div>
                                <div>{variantsElement}</div>



                                {/* <div>{sizeColorForms}</div> */}
                                {variants.size.map((sizeOption) => (
                                    variants.color.map((colorOption) => {
                                        const variant_name = `${colorOption.value}-${sizeOption.value}`;

                                        // Find the product value for the current combination
                                        const productValue = productValues.find((product) => product.variant_name === variant_name);

                                        return (
                                            <div key={variant_name}>
                                                <div className='row'>
                                                    <div className='col-md-2'>
                                                        <h2>Size: {sizeOption.label}, Color: {colorOption.label}</h2>
                                                    </div>
                                                    <div className='col-md-2 mb-3'>
                                                        <label
                                                            className='form-label'>{getFormattedMessage('product.input.product-cost.label')}: </label>
                                                        <span className='required' />
                                                        <InputGroup>
                                                            <input type='text' name='product_cost'
                                                                min={0} className='form-control'
                                                                placeholder={placeholderText('product.input.product-cost.placeholder.label')} onKeyPress={(event) => decimalValidate(event)}
                                                                value={productValue?.product_cost}
                                                                onChange={(e) => handleProductFieldValueChange(variant_name, 'product_cost', e.target.value)} />
                                                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                                                        </InputGroup>
                                                        <span
                                                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['product_cost'] ? errors['product_cost'] : null}</span>
                                                    </div>
                                                    <div className='col-md-2 mb-3'>
                                                        <label
                                                            className='form-label'>{getFormattedMessage('product.input.product-price.label')}: </label>
                                                        <span className='required' />
                                                        <InputGroup>
                                                            <input type='text' name='product_price' min={0}
                                                                className='form-control'
                                                                placeholder={placeholderText('product.input.product-price.placeholder.label')}
                                                                onKeyPress={(event) => decimalValidate(event)}
                                                                onChange={(e) => handleProductFieldValueChange(variant_name, 'product_price', e.target.value)}
                                                                value={productValue?.product_price}

                                                            />
                                                            <InputGroup.Text>{frontSetting.value && frontSetting.value.currency_symbol}</InputGroup.Text>
                                                        </InputGroup>
                                                        <span
                                                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['product_price'] ? errors['product_price'] : null}</span>
                                                    </div>
                                                    <div className='col-md-2 mb-3'>
                                                        <label
                                                            className='form-label'>{getFormattedMessage('product.input.stock-alert.label')}: </label>
                                                        <input type='number' name='stock_alert'
                                                            className='form-control'
                                                            placeholder={placeholderText('product.input.stock-alert.placeholder.label')}
                                                            onKeyPress={(event) => decimalValidate(event)}
                                                            onChange={(e) => handleProductFieldValueChange(variant_name, 'stock_alert', e.target.value)}
                                                            value={productValue?.stock_alert} min={0}
                                                        />
                                                        <span
                                                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['stock_alert'] ? errors['stock_alert'] : null}</span>
                                                    </div>

                                                    <div className='col-md-2 mb-3'>
                                                        <label
                                                            className='form-label'>{getFormattedMessage('product-quantity.add.title')}:</label>
                                                        <span className='required' />
                                                        <input type='number' name='quantity_limit'
                                                            className='form-control'
                                                            placeholder={placeholderText('product-quantity.add.title')}
                                                            onKeyPress={(event) => decimalValidate(event)}
                                                            onChange={(e) => handleProductFieldValueChange(variant_name, 'quantity_limit', e.target.value)}
                                                            value={productValue?.quantity_limit} min={1} />
                                                        <span
                                                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['add_stock'] ? errors['add_stock'] : null}</span>
                                                    </div>
                                                    <div className='col-xl-2'>
                                                        <div className='card'>
                                                            <label className='form-label'>
                                                                {getFormattedMessage('product.input.multiple-image.label')}: </label>
                                                            <MultipleImage product={singleProduct} fetchFiles={(value) => { handleProductFieldValueChange(variant_name, 'product_image', value) }}
                                                                transferImage={transferImage1} />
                                                        </div>
                                                    </div>



                                                </div>
                                            </div>
                                        );
                                    })
                                ))}







                                {/* <div className='col-md-12'>
                                        <label
                                            className='form-label'>{getFormattedMessage('globally.input.notes.label')}: </label>
                                        <textarea className='form-control' name='notes' rows={3}
                                            placeholder={placeholderText('globally.input.notes.placeholder.label')}
                                            onChange={(e) => onChangeInput(e)}
                                            value={productValue.notes ? productValue.notes === "null" ? '' : productValue.notes : ''} />
                                        <span
                                            className='text-danger d-block fw-400 fs-small mt-2'>{errors['notes'] ? errors['notes'] : null}</span>
                                    </div> */}
                                {/* <div >     <Button onClick={()=>addProductForm() } style={{width:'200px'}}>Add product</Button></div>
                                        {openForms.map((variant_name) => (
                                        <div className='col-md-12' key={variant_name}>
                                               <ProductAddForm  variantsElement={variantsElement}  variants={variants}/>
                                               <Button className='btn-danger' onClick={() => removeProductForm(variant_name)}>Remove Product</Button>
                                           </div>
                                         ))} */}


                                <ModelFooter onEditRecord={singleProduct} onSubmit={onSubmit}
                                    editDisabled={disabled}
                                    link='/app/products' addDisabled={!productValue.name} />
                            </div>
                        </div>
                    </div>

                </div>
                {/* </Form> */}
            </div>

        </div>
    )
}
    ;

const mapStateToProps = (state) => {
    console.log(state, 'this is from map state to props abstract fpr,');
    const { brands, productCategories, units, totalRecord, suppliers, warehouses, productUnits, frontSetting } = state;
    return { brands, productCategories, units, totalRecord, suppliers, warehouses, productUnits, frontSetting };
};

export default connect(mapStateToProps, {
    fetchAllBrands,
    fetchAllProductCategories,
    fetchUnits,
    productUnitDropdown,
    fetchAllWarehouses,
    fetchAllSuppliers,
    addBrand,
    editProductAbstraction,
    fetchProductAbstract,
    addUnit
})(ProductAbsctractForm);
