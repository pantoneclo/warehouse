import React, { useEffect, useState } from 'react';
import { connect, useDispatch, useSelector } from 'react-redux';
import { ReactSearchAutocomplete } from 'react-search-autocomplete';
import { addToast } from '../../../../store/action/toastAction';
import { toastType } from '../../../../constants';
import { searchPurchaseProduct } from '../../../../store/action/purchaseProductAction';
import { getFormattedMessage, placeholderText } from '../../../sharedMethod';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faSearch } from "@fortawesome/free-solid-svg-icons";
import { isArray, set } from 'lodash';
import { use } from 'echarts';


const AdvanceSearch = (props) => {
    const {
        values,
        products,
        updateProducts,
        setUpdateProducts,
        customProducts,
        searchPurchaseProduct,
        handleValidation,
        isAllProducts,
    
        fetchAdvancedSearch
    } = props;
    const [searchString, setSearchString] = useState("");
    const dispatch = useDispatch();

    // const filterProducts = products.data ? products.data.filter((item) => {
    //     const attributes = item.attributes;
    //     return attributes.stock && attributes.stock.quantity > 0;
    // }).map((item) => ({
    //     name: item.attributes.name,
    //     code: item.attributes.code,
    //     id: item.id,
    //     variant: item.attributes.variant
    // })) : [];
    const filterProducts = products ? isAllProducts ?
        products.map((item) => ({
            name: item.attributes.name, code: item.attributes.code, id: item.id, variant: item.attributes.variant
        }))
        : products.filter((qty) => qty && qty.attributes && qty.attributes.stock && qty.attributes.stock.quantity > 0).map((item) => ({
            name: item.attributes.name, code: item.attributes.code, id: item.id, variant: item.attributes.variant
        })) : [];

    console.log(filterProducts, 'filterProducts');





    // console.log(filterProducts, 'filterProducts')
    const onProductSearch = async (code) => {
        try {
            if (code.length >= 6) {
                  dispatch(fetchAdvancedSearch({ search: code }));

            console.log(code, 'code');
          
            setSearchString(code);  
            }
        
  

        } catch (error) {
            console.error('Error in onProductSearch:', error);
        }
    };
 

    useEffect(() => {   
        console.log(products, 'products inside of useeffect')       
          
                function filterOutDuplicates(products, newProducts) {
                    return newProducts.filter((newProduct) => {
                        return !products.find((exitId) => exitId?.product_id === newProduct?.product_id);
                    });
                }

                if (products&&products.length > 0) {
                    // Create a new array to store the new products
                    const newProducts = [];
    
                    products.forEach((product) => {
                        console.log(product, 'product inside of single product')
                        const newProduct = customProducts.find(element => element?.product_id === product.attributes?.product_id );
                        newProducts.push(newProduct); // Add each new product to the new array
                    });
    
                    // Filter out duplicates and add to updateProducts
                    console.log (updateProducts, 'updateProducts before')
                    const filteredNewProducts = filterOutDuplicates(updateProducts, newProducts);
                    console.log(filteredNewProducts, 'filteredNewProducts which i got')
    
                    if (filteredNewProducts?.length > 0) {
                        setUpdateProducts([...updateProducts, ...filteredNewProducts]); // Spread the new products into updateProducts
                        console.log(updateProducts, 'updateProducts after');
                        setSearchString ('');
                    } else {
                        // No new products found
                        dispatch(addToast({
                            text: getFormattedMessage('globally.product-already-added.validate.message'),
                            type: toastType.ERROR
                        }));
                        setSearchString ('');
                    }
                } 
              
    }, [ products]);

    

    const handleOnSearch = (string) => {
        onProductSearch(string);
    };

    console.log(updateProducts, 'updateProducts this is update products');
    console.log(products, 'products this is products search form')
    console.log(customProducts, 'customProducts this is customProducts search form')

    // const handleOnSelect = (result) => {
    //     console.log(result, 'result')
    //     onProductSearch(result);
    // }

    const formatResult = (item) => {
        console.log(item, 'item')

        // Map over the 'item.variant' array and format its elements as a string
        const variantKeys = Object.keys(item.variant);
        const formattedVariants = variantKeys.map((key) => {
            return `${key}: ${item.variant[key]}`;
        }).join(', ');


        return (
            <span onClick={(e) => e.stopPropagation()}>
                {item.code} ({item.name}) ({formattedVariants})( {item.pan_style})
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
                // onSelect={handleOnSelect}
                formatResult={formatResult}
                showIcon={false}
                showClear={false}


            />
            <FontAwesomeIcon icon={faSearch}
                className='d-flex align-items-center top-0 bottom-0 react-search-icon my-auto text-gray-600 position-absolute' />
        </div>
    );
}

export default connect(null, { searchPurchaseProduct })(AdvanceSearch);
