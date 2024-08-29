import React, { useState } from 'react';
import { connect, useDispatch } from 'react-redux';
import { ReactSearchAutocomplete } from 'react-search-autocomplete';
import { addToast } from '../../../../store/action/toastAction';
import { toastType } from '../../../../constants';
import { searchPurchaseProduct } from '../../../../store/action/purchaseProductAction';
import { getFormattedMessage, placeholderText } from '../../../sharedMethod';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faSearch } from "@fortawesome/free-solid-svg-icons";
import { set } from 'lodash';

const ProductSearch = (props) => {
    const {
        values,
        products,
        updateProducts,
        setUpdateProducts,
        customProducts,
        searchPurchaseProduct,
        handleValidation,
        isAllProducts
    } = props;
    const [searchString, setSearchString] = useState("");
    const dispatch = useDispatch();
    const filterProducts = isAllProducts && values.warehouse_id ? products.map((item) => ({
        name: item.attributes.name, code: item.attributes.code, id: item.id, variant: item.attributes.variant, pan_style: item.attributes.pan_style, package_code: item.attributes.package_code
    })) : values.warehouse_id && products.filter((qty) => qty && qty.attributes && qty.attributes.stock && qty.attributes.stock.quantity > 0).map((item) => ({
        name: item.attributes.name, code: item.attributes.code, id: item.id, variant: item.attributes.variant, pan_style: item.attributes.pan_style, package_code: item.attributes.package_code
    }))
    console.log(filterProducts, 'filterProducts')
    
    const onProductSearch = (code) => {
        if (!values.warehouse_id) {
            handleValidation();
        } else {
            setSearchString(code);
 
            // Function to filter out duplicate product IDs
            function filterOutDuplicates(products, newProducts) {
                return newProducts.filter((newProduct) => {
                    return !products.find((exitId) => exitId.product_id === newProduct.product_id);
                });
            }

            // Code block for pan styles
            //IF I WANT ONLY PAN ,WHICH IS SERACH BY QR CODE SCANNER THEN
            // I WILL USE( /^PAN/.test(code) ) THIS REGULAR EXPRESSION
            //SAMA FOR PACKAGE CODE ALSO
            if (/^PAN[0-9A-Fa-f]{7}$/.test(code)) {
                // Extract the pan_style value
                const panStyleProducts = filterProducts.filter((item) => item.pan_style === code);
                // console.log(panStyleProducts , 'panStyleProducts')

                if (panStyleProducts.length > 0) {
                    // Create a new array to store the new products
                    const newProducts = [];

                    panStyleProducts.forEach((product) => {
                        const newProduct = customProducts.find(element => element.product_id === product.id);
                        newProducts.push(newProduct); // Add each new product to the new array
                    });

                    // Filter out duplicates and add to updateProducts
                    const filteredNewProducts = filterOutDuplicates(updateProducts, newProducts);

                    if (filteredNewProducts.length > 0) {
                        setUpdateProducts([...updateProducts, ...filteredNewProducts]); // Spread the new products into updateProducts
                        console.log(updateProducts, 'updateProducts after');
                    } else {
                        // No new products found
                        dispatch(addToast({
                            text: getFormattedMessage('globally.product-already-added.validate.message'),
                            type: toastType.ERROR
                        }));
                    }
                } else {
                    // No matching products found
                    dispatch(addToast({
                        text: getFormattedMessage('not.found.panstyle'),
                        type: toastType.ERROR
                    }));
                }

                removeSearchClass();
                setSearchString("");
            }

            // Code block for package codes
            else if (/^PK_[0-9A-Fa-f]{10}$/.test(code)) {
                // Extract the package_code value
                const packageCodeProducts = filterProducts.filter((item) => item.package_code.includes(code));
                console.log(packageCodeProducts, 'packageCodeProducts')
                console.log(updateProducts, 'updateProducts after');
                if (packageCodeProducts.length > 0) {
                    // Create a new array to store the new products
                    const newProducts = [];

                    packageCodeProducts.forEach((product) => {
                        const newProduct = customProducts.find(element => element.product_id === product.id);
                        newProducts.push(newProduct); // Add each product to the new array
                    });

                    // Filter out duplicates and add to updateProducts
                    const filteredNewProducts = filterOutDuplicates(updateProducts, newProducts);

                    if (filteredNewProducts.length > 0) {
                        setUpdateProducts([...updateProducts, ...filteredNewProducts]); 
                        
                    } else {
                        // No new products found
                        dispatch(addToast({
                            text: getFormattedMessage('globally.product-already-added.validate.message'),
                            type: toastType.ERROR
                        }));
                    }
                } else {
                    // No matching products found
                    dispatch(addToast({
                        text: getFormattedMessage('not.found.package_code'),
                        type: toastType.ERROR
                    }));
                }

                removeSearchClass();
                setSearchString("");
            }

          
           
            else {
          
                const newId = products.filter((item) => item.attributes.code === code || item.attributes.code === code.code).map((item) => item.id);
                const finalIdArrays = customProducts.map((id) => id.product_id);
                const finalId = finalIdArrays.filter((finalIdArray) => finalIdArray === newId[0]);
                if (finalId[0] !== undefined) {
                    if (updateProducts.find(exitId => exitId.product_id === finalId[0])) {
                        dispatch(addToast({
                            text: getFormattedMessage('globally.product-already-added.validate.message'),
                            type: toastType.ERROR
                        }));
                    } else {
                        searchPurchaseProduct(newId[0])
                        const pushArray = [...customProducts]
                        if (updateProducts.filter(product => product.code === code || product.code === code.code).length > 0) {
                            setUpdateProducts(updateProducts => updateProducts.map((item) => {
                                return item
                            }))
                        } else {
                            
                            const newProduct = pushArray.find(element => element.product_id === finalId[0]);
                            console.log(updateProducts, 'updateProducts from inside el')

                            setUpdateProducts([...updateProducts, newProduct]);
                        }
                    }
                    removeSearchClass();
                    setSearchString("");
                }
            }

        }
    }
    console.log(products , 'products')


console.log(updateProducts ,'products inside of single product updateProducts')
    const handleOnSearch = (string) => {
        onProductSearch(string);
    }

    const handleOnSelect = (result) => {
        onProductSearch(result);
    }


    const formatResult = (item) => {
        // Map over the 'item.variant' array and format its elements as a string
        const variantKeys = Object.keys(item.variant);
        const formattedVariants = variantKeys.map((key) => {
            return `${key}: ${item.variant[key]}`;
        }).join(', ');

        const package_code = item.package_code ? item.package_code : '';
        console.log(package_code, 'package_code')

        return (
            <span onClick={(e) => e.stopPropagation()}>
                {item.code} ({item.name}) ({formattedVariants})( {item.pan_style}) ({package_code})
            </span>
        );
    };

    const removeSearchClass = () => {
        const html = document.getElementsByClassName(`custom-search`)[0].firstChild.firstChild.lastChild;
        html.style.display = 'none'
    }

    return (
        <div className='position-relative custom-search'>
            <ReactSearchAutocomplete
                items={filterProducts}
                onSearch={handleOnSearch}
                inputSearchString={searchString}
                fuseOptions={{ keys: ['code', 'name', 'variant', 'pan_style'] }}
                resultStringKeyName='code'
                placeholder={placeholderText('globally.search.field.label')}
                onSelect={handleOnSelect}
                formatResult={formatResult}
                showIcon={false}
                showClear={false}
            />
            <FontAwesomeIcon icon={faSearch}
                className='d-flex align-items-center top-0 bottom-0 react-search-icon my-auto text-gray-600 position-absolute' />
        </div>
    );
}

export default connect(null, { searchPurchaseProduct })(ProductSearch);
