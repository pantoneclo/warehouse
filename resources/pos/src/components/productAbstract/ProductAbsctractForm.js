import React, { useEffect, useReducer, useRef, useState } from "react";
import { connect, useDispatch } from "react-redux";
import { useNavigate } from "react-router-dom";
import Form from "react-bootstrap/Form";
import Select from "react-select";
import CreatableSelect from "react-select/creatable";
import { InputGroup, Button, NavItem } from "react-bootstrap-v5";
// import MultipleImage from './MultipleImage';
import { fetchUnits } from "../../store/action/unitsAction";
import { fetchAllProductCategories } from "../../store/action/productCategoryAction";
import { fetchAllBrands } from "../../store/action/brandsAction";
import { productUnitDropdown } from "../../store/action/productUnitAction";
import {
    decimalValidate,
    getFormattedMessage,
    getFormattedOptions,
    placeholderText,
    ucwords,
} from "../../shared/sharedMethod";
import taxes from "../../shared/option-lists/taxType.json";
import barcodes from "../../shared/option-lists/barcode.json";
import ModelFooter from "../../shared/components/modelFooter";
import ReactSelect from "../../shared/select/reactSelect";
import {
    productAbstractAttributes,
    saleStatusOptions,
    taxMethodOptions,
} from "../../constants";
import { fetchAllWarehouses } from "../../store/action/warehouseAction";
import { fetchAllSuppliers } from "../../store/action/supplierAction";
import moment from "moment";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faPlus, faUserPlus } from "@fortawesome/free-solid-svg-icons";
import BrandsFrom from "../brands/BrandsFrom";
import { addBrand } from "../../store/action/brandsAction";
import UnitsForm from "../units/UnitsForm";
import { addUnit } from "../../store/action/unitsAction";
import ProductAbstract from "./ProductAbstract";
// import Select from 'react-select';
import { editProductAbstraction } from "./../../store/action/productAbstractAction";
import _, { result } from "lodash";
import { fr } from "date-fns/locale";
import MultipleImage from "../product/MultipleImage";
import { formReducer, INITIAL_STATE } from "./FormReducer";

import { addToast } from "../../store/action/toastAction";

const ProductAbsctractForm = (props) => {
    const {
        addProductAbstractData,
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
        productUnit,
    } = props;

    /*
        define all states here
    */

    const [productAbstract, setProductAbstract] = useState({});
    const [availableOptions, setAvailableOptions] = useState({});
    const [productVariantCombination, setProductVariantCombination] = useState(
        {}
    );
    const [errors, setErrors] = useState({});

    const [multipleFiles, setMultipleFiles] = useState([]);
    const [removedImage, setRemovedImage] = useState([]);
    const [productAttributesState, setProductAttributesState] = useState([]);

    const navigate = useNavigate();
    const dispatch = useDispatch();

    /*
        Init all values here
    */

    //reload time api's should call from here
    useEffect(() => {
        fetchAllProductCategories();
        fetchAllBrands();
    }, []);

    //initiate the values in state
    useEffect(() => {
        setProductAbstract({
            name: singleProduct !== null ? singleProduct[0].name : "",
            pan_style: singleProduct !== null ? singleProduct[0].pan_style : "",
            attributes:
                singleProduct !== null ? singleProduct[0].attributes : "",
            attribute_list:
                singleProduct !== null ? (singleProduct[0].attribute_list === undefined ? null : singleProduct[0].attribute_list) : null,
            category: singleProduct !== null ? singleProduct[0].category : "",
            brand: singleProduct !== null ? singleProduct[0].brand : "",
            product_unit:
                singleProduct !== null
                    ? {
                        value: productUnit[0]?.id,
                        label: productUnit[0].attributes?.name,
                    }
                    : "",
            sale_unit: singleProduct !== null ? singleProduct[0].sale_unit : "",
            purchase_unit:
                singleProduct !== null
                    ? singleProduct[0].purchase_unit &&
                    singleProduct[0].purchase_unit
                    : "",
            order_tax:
                singleProduct !== null
                    ? singleProduct[0].order_tax
                        ? JSON.stringify(singleProduct[0].order_tax)
                        : 0
                    : 0,
            tax_type: singleProduct !== null ? singleProduct[0].tax_type : "",
            notes: singleProduct !== null ? singleProduct[0].notes : "",
            images: singleProduct !== null ? singleProduct[0].images : "",
            isEdit: singleProduct !== null ? singleProduct[0].is_Edit : false,
            products:
                singleProduct !== null
                    ? singleProduct[0].products?.length === 0
                        ? []
                        : singleProduct[0].products
                    : [],
            base_price:
                singleProduct !== null ? singleProduct[0].base_price : 0,
            base_cost: singleProduct !== null ? singleProduct[0].base_cost : 0,
            new_images: null,
        });

        setErrors({
            name: "",
            pan_style: "",
            product_category_id: "",
            attributes: [],
            brand_id: "",
            product_unit: "",
            sale_unit: "",
            purchase_unit: "",
            stock_alert: "",
            order_tax: "",
            tax_type: "",
            attribute_list: [],
            notes: "",
            product_cost: [],
            new_images: [],
            base_price: "",
            base_cost: "",
            products: [],
        });
    }, []);

    //product combination generate
    useEffect(() => {
        const combinations = [];
        const combinationVariables = { count: 0 };

        if (productAbstract?.attribute_list !== undefined) {
            console.log("master1", productAbstract?.attribute_list);
            productAbstract?.attribute_list && generateProductCombinations(
                productAbstract?.attribute_list,
                combinations,
                combinationVariables
            );
            if (combinations.length !== 0) {
                combinations.sort((a, b) => a.id - b.id);
                setProductVariantCombination(combinations);
                console.log("master", combinations);
            }
        }
    }, [
        productAbstract.attribute_list,
        productAbstract.base_cost,
        productAbstract.base_price,
    ]);

    // structure products attributes
    useEffect(() => {
        const proAttr = frontSetting?.value?.possible_variant_list;
        if (proAttr) {
            const proAttrArr = Object.keys(proAttr);
            const stData = proAttrArr.map((item) => {
                return { value: item, label: ucwords(item) };
            });
            setProductAttributesState(stData);
        }
    }, [frontSetting]);

    //load settings to state
    useEffect(() => {
        let obj = productAttributesState?.reduce((result, item) => {
            result[item.value] = frontSetting?.value?.possible_variant_list[
                item.value
            ].map((ix) => ({ value: ix, label: ix }));
            return result;
        }, {});
        setAvailableOptions((prv) => ({
            ...prv,
            ...obj,
        }));
    }, [productAttributesState]);

    console.log(productAttributesState, "productsAttrState");

    /*
        define custom functions and handellers
    */

    const updateError = (value, obj = {}) => {
        const index = obj.hasOwnProperty("index") ? obj.index : null;
        const field = obj.hasOwnProperty("field") ? obj.field : null;
        setErrors((err) => {
            const prev_err = { ...err };
            prev_err[field] =
                index !== null ? { ...prev_err[field], [index]: value } : value;
            return prev_err;
        });
    };

    const textValidationCheck = (value, type, obj) => {
        switch (type) {
            case "number":
                if (/^\d*\.?\d*$/.test(value)) {
                    const [, decimal] = value.split(".");
                    // restrict value to only 2 decimal places
                    if (decimal?.length > 2) {
                        // do nothing
                        updateError(
                            "Only accepts number with two decimal digits",
                            obj
                        );
                        console.log({ errors });
                        return false;
                    }
                    updateError("", obj);
                } else {
                    updateError("Only accepts number", obj);
                    return false;
                }
                break;
            case "digits":
                if (/^\d+$/.test(value)) {
                    updateError("", obj);
                } else {
                    updateError("Only accepts digits 0-9.", obj);
                    return false;
                }
                break;
            case "text":
                if (!/^[a-zA-Z0-9_#'\"\s]+$/.test(value)) {
                    updateError("Only accepts text.", obj);
                    return false;
                } else {
                    updateError("", obj);
                }
                break;
            case "text-code":
                if (!/^[^\s]*$/.test(value)) {
                    updateError("No space supported", obj);
                    return false;
                } else {
                    updateError("", obj);
                }
                break;
            default:
                return false;
                break;
        }
        return true;
    };

    const onChangeInputTextFields = (
        e,
        obj = { field: e.target.name },
        type = "abstract"
    ) => {
        console.log('e:::::::::', {
            e,
            obj: { field: e.target.name, value: e.target.value },
            type: "abstract"
        });

        e.preventDefault();
        const { value } = e.target;
        console.log('on Change text Fields', e, 'asdadasdasdadasdas', textValidationCheck(
            e.target.value,
            e.target.attributes["data-type"].value,
            obj
        ))
        if (
            textValidationCheck(
                e.target.value,
                e.target.attributes["data-type"].value,
                obj
            )
        ) {
            switch (type) {
                case "abstract":
                    setProductAbstract((inputs) => ({
                        ...inputs,
                        [e.target.name]: value,
                    }));

                    console.log('getProductAbstract:::', productAbstract);

                    break;
                case "product-combination":
                    setProductVariantCombination((prev) => ({
                        ...prev,
                        [obj.index]: { ...prev[obj.index], [obj.field]: value },
                    }));
                    console.log('product variant combination', { productVariantCombination });
                    break;

                default:
                    break;
            }
        } else {
            return false;
        }
        console.log({ errors });
    };

    const onChangeSelectFields = (fieldName, obj, arrayKey = null) => {
        console.log({ singleProduct });

        switch (fieldName) {
            case "attribute_list":
                setProductAbstract((prev) => ({
                    ...prev,
                    [fieldName]: { ...prev[fieldName], [arrayKey]: obj },
                }));
                console.log(productAbstract);
                break;
            case "attributes":
                let objAttrLst = obj.map((item) => ({
                    //this line handel prev state from api or prv state if null
                    [item?.value]:
                        singleProduct &&
                            singleProduct[0].attribute_list[item.value]
                            ? singleProduct[0].attribute_list[item.value]
                            : item?.value,
                }));

                console.log({ objAttrLst });

                objAttrLst = Object.entries(objAttrLst).reduce(
                    (result, [key, val]) => {
                        result = { ...result, ...val };
                        return result;
                    },
                    {}
                );

                console.log({ objAttrLst });

                setProductAbstract((prev) => ({
                    ...prev,
                    attribute_list: { ...objAttrLst },
                }));

            default:
                console.log({ productAbstract });
                console.log({ availableOptions });
                setProductAbstract((prev) => ({ ...prev, [fieldName]: obj }));
                setErrors("");
                break;
        }
    };

    const onChangeFiles = (
        file,
        obj = { field: e.target.name },
        type = "abstract"
    ) => {
        console.log(file, "this is from on change files");

        switch (type) {
            case "product-combination": {
                setProductVariantCombination((prev) => ({
                    ...prev,
                    [obj.index]: { ...prev[obj.index], [obj.field]: file },
                }));
                console.log({ productVariantCombination });
                break;
            }
            case "abstract": {
                console.log(file);
                // setMultipleFiles(file);
                setProductAbstract((inputs) => ({
                    ...inputs,
                    [obj.field]: file,
                }));
            }

            default:
                break;
        }
    };

    const transferImage = (item) => {
        setRemovedImage(item);
        setMultipleFiles([]);
    };

    /*
        define custom render functions here
    */

    const generateProductCombinations = (
        obj,
        output,
        combinationVariables,
        currentCombo = {},
        keys = Object.keys(obj),
        index = 0
    ) => {
        if (index === keys.length) {
            const combination = keys.reduce((result, key) => {
                console.log({ key });
                result[key] = currentCombo.hasOwnProperty(key)
                    ? { ...currentCombo[key] }
                    : null;
                return result;
            }, {});

            //console.log({combination});

            const filterFunction = (product) => {
                console.log({ ...product.data.attributes });
                const attributes = Object.entries(
                    product.data.attributes.variant
                ).reduce((result, [key, val]) => {
                    result = { ...result, [key]: val };
                    return result;
                }, {});
                console.log({ attributes });
                const transformedObject = Object.keys(combination).reduce(
                    (result, key) => {
                        result[key] = combination[key].value;
                        return result;
                    },
                    {}
                );
                console.log({ transformedObject });
                return _.isEqual(attributes, transformedObject);
            };
            let filteredProducts =
                singleProduct &&
                singleProduct[0]?.products?.filter(filterFunction)[0];
            // console.log( filteredProducts, 'this is from filtered products' );
            if (filteredProducts === undefined) {
                filteredProducts = null;
            }
            console.log({ filteredProducts });

            let prev_state = null;
            console.log("count: ", combinationVariables.count);
            if (
                combinationVariables.count >= 0 &&
                combinationVariables.count < productVariantCombination.length
            ) {
                prev_state =
                    productVariantCombination[combinationVariables.count];
                console.log({ prev_state });
            }

            output.push({
                variant: { ...combination },
                id: filteredProducts?.data.id,
                product_cost:
                    // prev_state?.product_cost ??
                    filteredProducts?.data.attributes.product_cost ??
                    productAbstract.base_cost,
                product_price:
                    filteredProducts?.data.attributes.product_price ??
                    productAbstract.base_price,
                stock_alert: filteredProducts?.data.attributes.stock_alert,
                code: filteredProducts?.data.attributes.code,
                quantity: null,
                images:
                    filteredProducts === null
                        ? [{ images: [] }]
                        : [
                            {
                                images:
                                    filteredProducts?.data?.attributes
                                        ?.images ?? null,
                            },
                        ],
                new_images: null,
                // is_availale:filteredProducts?.data?.id ===undefined ? singleProduct===null ?true:  false : true,

                is_availale:
                    filteredProducts === null
                        ? singleProduct === null
                            ? true
                            : false
                        : true,
            });
            combinationVariables.count++;
            return;
        }

        const key = keys[index];
        const set = obj[key];

        console.log({ key });
        console.log({ set });

        if (set)
            for (const element of set) {
                currentCombo[key] = element;
                generateProductCombinations(
                    obj,
                    output,
                    combinationVariables,
                    currentCombo,
                    keys,
                    index + 1
                );
            }
    };
    const prepareFormData = (data) => {
        console.log("come from prepare form data", data);
        const formData = new FormData();
        formData.append("name", data.name);
        formData.append("pan_style", data.pan_style);
        formData.append("product_category_id", data.category.value);
        formData.append("brand_id", data.brand.value);
        formData.append("base_price", data.base_price);
        formData.append("base_cost", data.base_cost);

        const productArray = Object.values(data?.products);
        productArray.forEach((product, product_index) => {
            console.log(
                "come from prepare form dataxxz",
                `products[${product_index}][id]`,
                product.id
            );
            formData.append(
                `products[${product_index}][is_available]`,
                product.is_availale
            );
            formData.append(`products[${product_index}][id]`, product.id);
            formData.append(
                `products[${product_index}][variant_name]`,
                product.variant_name
            );
            formData.append(
                `products[${product_index}][variant]`,
                JSON.stringify(product.variant)
            );
            formData.append(
                `products[${product_index}][product_cost]`,
                product.product_cost
            );
            formData.append(
                `products[${product_index}][product_price]`,
                product.product_price
            );
            formData.append(
                `products[${product_index}][stock_alert]`,
                product.stock_alert
            );
            formData.append(
                `products[${product_index}][quantity_limit]`,
                product.quantity_limit
            );

            const image = product.new_images;

            if (image) {
                image.forEach((imageX, index) => {
                    formData.append(
                        `products[${product_index}][product_image][]`,
                        imageX
                    );
                });
            }
        });

        const transformedData = {};
        for (const key in data.attribute_list) {
            if (Object.hasOwnProperty.call(data.attribute_list, key)) {
                // Extract the "value" from each object within the array
                transformedData[key] = data.attribute_list[key].map(
                    (item) => item.value
                );
            }
        }

        console.log(transformedData, "transformedData");
        formData.append("attributes", JSON.stringify(transformedData));
        formData.append(
            "product_unit",
            data.product_unit && data.product_unit[0]
                ? data.product_unit[0].value
                : data.product_unit.value
        );
        formData.append(
            "sale_unit",
            data.sale_unit && data.sale_unit[0]
                ? data.sale_unit[0].value
                : data.sale_unit.value
        );
        formData.append(
            "purchase_unit",
            data.purchase_unit && data.purchase_unit[0]
                ? data.purchase_unit[0].value
                : data.purchase_unit.value
        );
        formData.append("order_tax", data.order_tax ? data.order_tax : "");
        formData.append(
            "quantity_limit",
            data.sale_quantity_limit ? data.sale_quantity_limit : ""
        );
        formData.append("barcode_symbol", 1);
        // formData.append('products' )
        if (data.tax_type[0]) {
            formData.append(
                "tax_type",
                data.tax_type[0].value ? data.tax_type[0].value : 1
            );
        } else {
            formData.append(
                "tax_type",
                data.tax_type.value ? data.tax_type.value : 1
            );
        }
        formData.append("notes", data.notes);

        if (data?.new_images) {
            data?.new_images &&
                data?.new_images.forEach((image, index) => {
                    formData.append(`images[${index}]`, image);
                });
        }

        return formData;
    };

    const combinationValidation = () => {
        const combinationArray = Object.values(productVariantCombination);

        if (combinationArray.some((item) => item.is_availale === true)) {
            // At least one combination is available
            return true;
        } else {
            dispatch(
                addToast({
                    text: "Please select at least one combination",
                    type: "error",
                })
            );
            return false;
        }
    };

    const onSubmit = (event) => {
        event.preventDefault();
        const valid = handleValidation();
        console.log(valid, "this is from valid");

        const productValue = {
            ...productAbstract,
            products: productVariantCombination,
        };
        // productValue.images = multipleFiles;
        // console.log(productValue, 'this is from on submit')
        // console.log(multipleFiles, 'this is from on submit multiple files')
        // productValue.images = multipleFiles;
        const isCombinationValid = combinationValidation();

        if (
            singleProduct !== null &&
            singleProduct &&
            valid &&
            isCombinationValid === true
        ) {
            editProductAbstraction(id, prepareFormData(productValue), navigate);
        } else {
            if (valid && isCombinationValid === true) {
                productAbstract.images = multipleFiles;
                setProductAbstract(productValue);
                addProductAbstractData(prepareFormData(productValue), navigate);
            }
        }
    };
    const handleValidation = () => {
        let errorss = {};
        let isValid = false;
        if (!productAbstract["name"]) {
            console.log("this is from name");
            errorss["name"] = getFormattedMessage(
                "globally.input.name.validate.label"
            );
        } else if (!productAbstract["pan_style"]) {
            console.log("this is from pan style");
            errorss["pan_style"] = getFormattedMessage(
                "globally.input.panstyle.valid.validate.label"
            );
        } else if (!productAbstract["category"]) {
            console.log("this is from category");

            errorss["category"] = getFormattedMessage(
                "product.input.product-category.validate.label"
            );
        } else if (!productAbstract["brand"]) {
            console.log("this is from brand");
            errorss["brand"] = getFormattedMessage(
                "product.input.brand.validate.label"
            );
        } else if (!productAbstract["product_unit"]) {
            errorss["product_unit"] = getFormattedMessage(
                "product.input.product-unit.validate.label"
            );
        } else if (!productAbstract["sale_unit"]) {
            console.log("this is from sale unit");
            errorss["sale_unit"] = getFormattedMessage(
                "product.input.sale-unit.validate.label"
            );
        } else if (!productAbstract["purchase_unit"]) {
            console.log("this is from purchase unit");
            errorss["purchase_unit"] = getFormattedMessage(
                "product.input.purchase-unit.validate.label"
            );
        } else if (productAbstract["order_tax"] > 100) {
            console.log("this is from order tax");
            errorss["order_tax"] = getFormattedMessage(
                "product.input.order-tax.valid.validate.label"
            );
        } else if (!productAbstract["attributes"]) {
            console.log("this is from attributes");
            errorss["attributes"] = getFormattedMessage(
                "product.input.attributes.validate.label"
            );
        } else if (!productAbstract["base_price"]) {
            console.log("this is from base price");
            errorss["base_price"] = getFormattedMessage(
                "product.input.base-price.validate.label"
            );
        } else if (!productAbstract["base_cost"]) {
            console.log("this is from base cost");

            errorss["base_cost"] = getFormattedMessage(
                "product.input.base-cost.validate.label"
            );
        } else {
            isValid = true;
        }
        setErrors(errorss);
        return isValid;
    };

    console.log(productAbstract, "this is from product abstract form");
    console.log(
        productVariantCombination,
        "this is from productVariantCombination form"
    );
    console.log(singleProduct, "this is from single product");
    console.log(productAbstract["base_cost"], "this is from base cost");
    const handleKeyDown = (e) => {
        // Prevent the default behavior for arrow up and down keys
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
        }
    };
    return (
        <>
            {productAbstract !== null && (<div className="card">
                <div className="card-body">
                    {/* <Form> */}
                    <div className="row">
                        <div className="col-xl-12">
                            <div className="card">
                                <div className="row">
                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "globally.input.name.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <span className="required" />
                                        <input
                                            type="text"
                                            data-type="text"
                                            name="name"
                                            placeholder={placeholderText(
                                                "globally.input.name.placeholder.label"
                                            )}
                                            className="form-control"
                                            autoFocus={true}
                                            value={productAbstract.name ?? ''}
                                            onChange={(e) =>
                                                onChangeInputTextFields(e)
                                            }
                                        />
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["name"]
                                                ? errors["name"]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "globally.input.panStyle.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <span className="required" />
                                        <input
                                            type="text"
                                            data-type="text-code"
                                            name="pan_style"
                                            value={productAbstract.pan_style}
                                            placeholder={placeholderText(
                                                "globally.input.panStyle.placeholder"
                                            )}
                                            className="form-control"
                                            onChange={(e) =>
                                                onChangeInputTextFields(e)
                                            }
                                        />
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["pan_style"]
                                                ? errors["pan_style"]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <ReactSelect
                                            title={getFormattedMessage(
                                                "product.input.product-category.label"
                                            )}
                                            placeholder={placeholderText(
                                                "product.input.product-category.placeholder.label"
                                            )}
                                            defaultValue={
                                                productAbstract.category
                                            }
                                            value={productAbstract.category}
                                            data={productCategories}
                                            onChange={(obj) =>
                                                onChangeSelectFields(
                                                    "category",
                                                    obj
                                                )
                                            }
                                            errors={errors["category"]}
                                            required
                                        />
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <ReactSelect
                                            title={getFormattedMessage(
                                                "product.input.brand.label"
                                            )}
                                            placeholder={placeholderText(
                                                "product.input.brand.placeholder.label"
                                            )}
                                            //defaultValue={selectedBrand}
                                            errors={errors["brand"]}
                                            data={brands}
                                            onChange={(obj) =>
                                                onChangeSelectFields(
                                                    "brand",
                                                    obj
                                                )
                                            }
                                            value={productAbstract.brand}
                                        />
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <InputGroup className="flex-nowrap">
                                            <ReactSelect
                                                className="position-relative"
                                                title={getFormattedMessage(
                                                    "product.input.product-unit.label"
                                                )}
                                                placeholder={placeholderText(
                                                    "product.input.product-unit.placeholder.label"
                                                )}
                                                defaultValue={
                                                    productAbstract.product_unit
                                                }
                                                value={
                                                    productAbstract.product_unit
                                                }
                                                data={baseUnits}
                                                errors={errors["product_unit"]}
                                                onChange={(obj) =>
                                                    onChangeSelectFields(
                                                        "product_unit",
                                                        obj
                                                    )
                                                }
                                            />
                                        </InputGroup>
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <ReactSelect
                                            className="position-relative"
                                            title={getFormattedMessage(
                                                "product.input.sale-unit.label"
                                            )}
                                            placeholder={placeholderText(
                                                "product.input.sale-unit.placeholder.label"
                                            )}
                                            value={productAbstract.sale_unit}
                                            data={baseUnits}
                                            errors={errors["sale_unit"]}
                                            onChange={(obj) =>
                                                onChangeSelectFields(
                                                    "sale_unit",
                                                    obj
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <ReactSelect
                                            className="position-relative"
                                            title={getFormattedMessage(
                                                "product.input.purchase-unit.label"
                                            )}
                                            placeholder={placeholderText(
                                                "product.input.purchase-unit.placeholder.label"
                                            )}
                                            value={
                                                productAbstract.purchase_unit
                                            }
                                            data={baseUnits}
                                            errors={errors["purchase_unit"]}
                                            onChange={(obj) =>
                                                onChangeSelectFields(
                                                    "purchase_unit",
                                                    obj
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "product.input.order-tax.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <InputGroup>
                                            <input
                                                data-type="number"
                                                type="text"
                                                name="order_tax"
                                                className="form-control"
                                                placeholder={placeholderText(
                                                    "product.input.order-tax.placeholder.label"
                                                )}
                                                onChange={(e) =>
                                                    onChangeInputTextFields(e)
                                                }
                                                min={0}
                                                pattern="[0-9]*"
                                                value={
                                                    productAbstract.order_tax
                                                }
                                            />
                                            <InputGroup.Text>%</InputGroup.Text>
                                        </InputGroup>
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["order_tax"]
                                                ? errors["order_tax"]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <ReactSelect
                                            isRequired={true}
                                            title={getFormattedMessage(
                                                "product.input.tax-type.label"
                                            )}
                                            multiLanguageOption={getFormattedOptions(
                                                taxMethodOptions
                                            )}
                                            onChange={(obj) =>
                                                onChangeSelectFields(
                                                    "tax_type",
                                                    obj
                                                )
                                            }
                                            errors={errors["tax_type"]}
                                            value={productAbstract.tax_type}
                                            placeholder={placeholderText(
                                                "product.input.tax-type.placeholder.label"
                                            )}

                                        />
                                    </div>

                                    <div className="col-md-6 mb-3">
                                        <label className="form-label">
                                            {"Attributes"}:{" "}
                                        </label>
                                        <span className="required" />
                                        <Select
                                            closeMenuOnSelect={false}
                                            value={productAbstract.attributes}
                                            isMulti
                                            errors={errors["attributes"]}
                                            options={productAttributesState}
                                            onChange={(obj) =>
                                                onChangeSelectFields(
                                                    "attributes",
                                                    obj
                                                )
                                            }
                                            required
                                        />
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["attributes"]
                                                ? errors["attributes"]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="row col-md-6 mb-3 pr-5">
                                        <div className="col-md-6 mb-3">
                                            <label className="form-label">
                                                {getFormattedMessage(
                                                    "base.cost.label"
                                                )}
                                                :{" "}
                                            </label>
                                            <InputGroup>
                                                <input
                                                    data-type="number"
                                                    type="text"
                                                    name="base_cost"
                                                    className="form-control"
                                                    placeholder={placeholderText(
                                                        "product.input.base_cost.placeholder.label"
                                                    )}
                                                    onChange={(e) =>
                                                        onChangeInputTextFields(
                                                            e
                                                        )
                                                    }
                                                    min={0}
                                                    pattern="[0-9]*"
                                                    value={
                                                        productAbstract.base_cost ??
                                                        0
                                                    }
                                                />
                                                <InputGroup.Text>
                                                    {frontSetting.value &&
                                                        frontSetting.value
                                                            .currency_symbol}
                                                </InputGroup.Text>
                                            </InputGroup>
                                            <span className="text-danger d-block fw-400 fs-small mt-2">
                                                {errors["base_cost"]
                                                    ? errors["base_cost"]
                                                    : null}
                                            </span>
                                        </div>
                                        <div className="col-md-6 mb-3">
                                            <label className="form-label">
                                                {getFormattedMessage(
                                                    "base.price.label"
                                                )}
                                                :{" "}
                                            </label>
                                            <InputGroup>
                                                <input
                                                    data-type="number"
                                                    type="text"
                                                    name="base_price"
                                                    className="form-control"
                                                    placeholder={placeholderText(
                                                        "product.input.price.placeholder.label"
                                                    )}
                                                    onChange={(e) =>
                                                        onChangeInputTextFields(
                                                            e
                                                        )
                                                    }
                                                    pattern="[0-9]*"
                                                    value={
                                                        productAbstract.base_price ??
                                                        0
                                                    }
                                                />
                                                <InputGroup.Text>
                                                    {frontSetting.value &&
                                                        frontSetting.value
                                                            .currency_symbol}
                                                </InputGroup.Text>
                                            </InputGroup>
                                            <span className="text-danger d-block fw-400 fs-small mt-2">
                                                {errors["base_price"]
                                                    ? errors["base_price"]
                                                    : null}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Write code for abstract image handel here */}
                                    <div className="col-md-6 mb-3">
                                        <div className="card">
                                            <label className="form-label">
                                                {getFormattedMessage(
                                                    "product.input.multiple-image.label"
                                                )}
                                                :{" "}
                                            </label>
                                            <MultipleImage
                                                name="images"
                                                product={singleProduct}
                                                fetchFiles={(value) => {
                                                    onChangeFiles(
                                                        value,
                                                        { field: "new_images" },
                                                        "abstract"
                                                    );
                                                }}
                                                transferImage={transferImage}
                                            />
                                        </div>
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["new_images"]
                                                ? errors["new_images"]
                                                : null}
                                        </span>
                                    </div>

                                    {productAbstract.attribute_list && (
                                        <div>
                                            {Object.entries(
                                                productAbstract?.attribute_list
                                            ).map(([key, value], index) => (
                                                <div
                                                    className="col-12 mb-3"
                                                    key={`divi-${key}-${index}`}
                                                >
                                                    <label
                                                        className="form-label"
                                                        htmlFor={`select-${key}-${index}`}
                                                    >
                                                        {_.startCase(
                                                            ucwords(key)
                                                        )}
                                                    </label>
                                                    <CreatableSelect
                                                        key={`create-select-${key}-${index}`}
                                                        //  id={`select-${key}-${index}`}
                                                        // title={"hii"+_.startCase(item.value)}
                                                        placeholder={`Select ${_.startCase(
                                                            key
                                                        )}`}
                                                        isClearable
                                                        isDisabled={false}
                                                        isLoading={false}
                                                        isMulti
                                                        options={
                                                            availableOptions[
                                                            key
                                                            ]
                                                        }
                                                        errors={
                                                            errors["products"]
                                                        }
                                                        onChange={(obj) =>
                                                            onChangeSelectFields(
                                                                "attribute_list",
                                                                obj,
                                                                key
                                                            )
                                                        }
                                                        value={value}
                                                    />
                                                    <span className="text-danger d-block fw-400 fs-small mt-2">
                                                        {errors["products"]
                                                            ? errors[
                                                            "attribute_list"
                                                            ]
                                                            : null}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* <ModelFooter onEditRecord={singleProduct} onSubmit={onSubmit}
                                    editDisabled={disabled}
                                    link='/app/products' addDisabled={!productValue.name} /> */}
                                </div>
                            </div>
                        </div>
                    </div>
                    {/* </Form> */}
                </div>
            </div>)}

            {productVariantCombination && 1 &&
                Object.entries(productVariantCombination).map(
                    ([key, value]) => (
                        <div className="card mt-6" key={key}>
                            <div className="card-body ">
                                <div className="row ">
                                    <div className="col-md-6 mb-3">
                                        {value &&
                                            Object.entries(
                                                value["variant"]
                                            ).map(([key1, value1]) => {
                                                //console.log(key);
                                                return (
                                                    <span key={key1}>
                                                        {" "}
                                                        <span
                                                            className="badge bg-light-success mt-4"
                                                            style={{
                                                                fontSize:
                                                                    "16px",
                                                            }}
                                                        >
                                                            {ucwords(key1)}:{" "}
                                                            {value1.label}
                                                        </span>
                                                        &nbsp;&nbsp;&nbsp;&nbsp;
                                                    </span>
                                                );
                                            })}
                                    </div>

                                    <div className="col-md-6 mb-3 ">
                                        <div className="mt-4">
                                            <Form.Check // prettier-ignore
                                                type="switch"
                                                id="custom-switch"
                                                label="Check to active product variant"
                                                // value={productVariantCombination[key]?.is_availale ?? false}
                                                defaultChecked={
                                                    productVariantCombination[
                                                        key
                                                    ]?.is_availale ?? true
                                                }
                                                onChange={(e) => {
                                                    setProductVariantCombination(
                                                        (prev) => ({
                                                            ...prev,
                                                            [key]: {
                                                                ...prev[key],
                                                                is_availale:
                                                                    e.target
                                                                        .checked,
                                                            },
                                                        })
                                                    );
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <p className="d-none">
                                        {productVariantCombination[key]?.id ??
                                            0}
                                    </p>

                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "product.input.product-cost.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <span className="required" />
                                        <InputGroup>
                                            <input
                                                type="text"
                                                name="product_cost"
                                                data-type="number"
                                                min={0}
                                                className="form-control"
                                                value={
                                                    productVariantCombination[
                                                        key
                                                    ]?.product_cost ?? 0
                                                }
                                                onChange={(e) =>
                                                    onChangeInputTextFields(
                                                        e,
                                                        {
                                                            index: key,
                                                            field: "product_cost",
                                                        },
                                                        "product-combination"
                                                    )
                                                }
                                            // placeholder={placeholderText(
                                            //     "product.input.product-cost.placeholder.label"
                                            // )}
                                            />
                                            <InputGroup.Text>
                                                {frontSetting.value &&
                                                    frontSetting.value
                                                        .currency_symbol}
                                            </InputGroup.Text>
                                        </InputGroup>
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["product_cost"]
                                                ? errors["product_cost"][key]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "product.input.product-price.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <span className="required" />
                                        <InputGroup>
                                            <input
                                                type="text"
                                                data-type="number"
                                                name="product_price"
                                                min={0}
                                                className="form-control"
                                                // placeholder={placeholderText(
                                                //     "product.input.product-price.placeholder.label"
                                                // )}
                                                value={
                                                    productVariantCombination[
                                                        key
                                                    ]?.product_price ?? 0
                                                }
                                                onChange={(e) =>
                                                    onChangeInputTextFields(
                                                        e,
                                                        {
                                                            index: key,
                                                            field: "product_price",
                                                        },
                                                        "product-combination"
                                                    )
                                                }
                                            />
                                            <InputGroup.Text>
                                                {frontSetting.value &&
                                                    frontSetting.value
                                                        .currency_symbol}
                                            </InputGroup.Text>
                                        </InputGroup>
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["product_price"]
                                                ? errors["product_price"][key]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "product.input.stock-alert.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <input
                                            type="number"
                                            data-type="digits"
                                            name="stock_alert"
                                            className="form-control"
                                            onKeyDown={handleKeyDown}

                                            placeholder={placeholderText(
                                                "product.input.stock-alert.placeholder.label"
                                            )}
                                            value={
                                                productVariantCombination[key]
                                                    ?.stock_alert ?? ''
                                            }
                                            onChange={(e) =>
                                                onChangeInputTextFields(
                                                    e,
                                                    {
                                                        index: key,
                                                        field: "stock_alert",
                                                    },
                                                    "product-combination"
                                                )
                                            }


                                        />
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["stock_alert"]
                                                ? errors["stock_alert"][key]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            {getFormattedMessage(
                                                "product.input.code.label"
                                            )}
                                            :{" "}
                                        </label>
                                        <input
                                            type="text"
                                            data-type="text"
                                            name="code"
                                            className="form-control"
                                            onKeyDown={handleKeyDown}

                                            placeholder={placeholderText(
                                                "product.input.code.placeholder.label"
                                            )}
                                            value={
                                                productVariantCombination[key]
                                                    ?.code ?? ''
                                            }
                                            onChange={(e) =>
                                                onChangeInputTextFields(
                                                    e,
                                                    {
                                                        index: key,
                                                        field: "code",
                                                    },
                                                    "product-combination"
                                                )
                                            }


                                        />
                                        <span className="text-danger d-block fw-400 fs-small mt-2">
                                            {errors["code"]
                                                ? errors["code"][key]
                                                : null}
                                        </span>
                                    </div>

                                    <div className="col-md-12 mb-3">
                                        <div className="card">
                                            <label className="form-label">
                                                {getFormattedMessage(
                                                    "product.input.multiple-image.label"
                                                )}
                                                :{" "}
                                            </label>
                                            <MultipleImage
                                                product={value["images"]}
                                                fetchFiles={(value) => {
                                                    onChangeFiles(
                                                        value,
                                                        {
                                                            index: key,
                                                            field: "new_images",
                                                        },
                                                        "product-combination"
                                                    );
                                                }}
                                                transferImage={transferImage}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )
                )}
            <ModelFooter
                // onEditRecord={singleProduct}
                onSubmit={onSubmit}
                // editDisabled={disabled}
                link="/app/products"
            //  addDisabled={!productValue.name}
            />
        </>
    );
};
const mapStateToProps = (state) => {
    console.log(state, "this is from map state to props abstract fpr,");
    const {
        brands,
        productCategories,
        units,
        totalRecord,
        suppliers,
        warehouses,
        productUnits,
        frontSetting,
    } = state;
    return {
        brands,
        productCategories,
        units,
        totalRecord,
        suppliers,
        warehouses,
        productUnits,
        frontSetting,
    };
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
    addUnit,
})(ProductAbsctractForm);
