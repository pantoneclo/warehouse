import React, { useEffect, useState } from 'react';
import { connect, useDispatch, useSelector } from 'react-redux';
import { ReactSearchAutocomplete } from 'react-search-autocomplete';
import { addToast } from '../../../../store/action/toastAction';
import { toastType } from '../../../../constants';
import { searchPurchaseProduct } from '../../../../store/action/purchaseProductAction';
import { getFormattedMessage, placeholderText } from '../../../sharedMethod';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faSearch, faSpinner } from "@fortawesome/free-solid-svg-icons";
import { isArray, set } from 'lodash';
import { use } from 'echarts';


import { preparePurchaseProductArray } from '../../../prepareArray/preparePurchaseArray';

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
    const [isSearching, setIsSearching] = useState(false);
    const dispatch = useDispatch();
    const isLoading = useSelector(state => state.isLoading);
    console.log("AdvanceSearch props values:", values);

    const filterProducts = products ? isAllProducts ?
        products.map((item) => ({
            name: item.attributes.name, brand: item.attributes.brand_name, code: item.attributes.code, id: item.id, variant: item.attributes.variant, pan_style: item.attributes.pan_style
        }))
        : products.filter((qty) => {
            if (!qty || !qty.attributes) return false;
            const whId = values?.warehouse_id?.value || values?.warehouse_id;
            const stockInWh = qty.attributes.stocks?.find(s => s.warehouse_id === whId);
            const qtyInWh = stockInWh ? stockInWh.quantity : (qty.attributes.stock ? qty.attributes.stock.quantity : 0);
            return qtyInWh > 0 || isAllProducts;
        }).map((item) => ({
            name: item.attributes.name, brand: item.attributes.brand_name, code: item.attributes.code, id: item.id, variant: item.attributes.variant, pan_style: item.attributes.pan_style
        })) : [];

    console.log(products, 'After filterProducts');
    console.log(filterProducts, 'filterProducts');

    const onProductSearch = async (code) => {
        try {
            if (code.length >= 2) {
                setIsSearching(true);
                const warehouseId = values?.warehouse_id?.value || values?.warehouse_id;
                dispatch(fetchAdvancedSearch({ search: code, warehouse_id: warehouseId }));

                console.log(code, 'code');

                setSearchString(code);
            } else {
                setIsSearching(false);
            }
        } catch (error) {
            console.error('Error in onProductSearch:', error);
            setIsSearching(false);
        }
    };


    useEffect(() => {
        console.log(products, 'products inside of useeffect')

        function filterOutDuplicates(existingProducts, newProducts) {
            return newProducts.filter((newProduct) => {
                return newProduct && !existingProducts.find((exitItem) => (
                    (exitItem?.product_id && exitItem?.product_id === newProduct?.product_id) ||
                    (exitItem?.id && exitItem?.id === newProduct?.id)
                ));
            });
        }

        if (products && products.length > 0) {
            const warehouseId = values?.warehouse_id?.value || values?.warehouse_id;
            let preparedProducts = preparePurchaseProductArray(products, warehouseId);

            if ((!preparedProducts || preparedProducts.length === 0 || preparedProducts.some(p => !p)) && customProducts && customProducts.length > 0) {
                preparedProducts = products.map((product) => {
                    return customProducts.find(element => element?.product_id === (product.attributes?.product_id || product.id)) || preparePurchaseProductArray([product], warehouseId)[0];
                }).filter(Boolean);
            }

            console.log(updateProducts, 'updateProducts before')
            const filteredNewProducts = filterOutDuplicates(updateProducts, preparedProducts);
            console.log(filteredNewProducts, 'filteredNewProducts which i got')

            if (filteredNewProducts?.length > 0) {
                setUpdateProducts([...updateProducts, ...filteredNewProducts]);
                console.log(updateProducts, 'updateProducts after');
                setSearchString('');
            } else {
                dispatch(addToast({
                    text: getFormattedMessage('globally.product-already-added.validate.message'),
                    type: toastType.ERROR
                }));
                setSearchString('');
            }
        }

        setIsSearching(false);
    }, [products]);



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
            <FontAwesomeIcon
                icon={isSearching || isLoading ? faSpinner : faSearch}
                className={`d-flex align-items-center top-0 bottom-0 react-search-icon my-auto text-gray-600 position-absolute ${isSearching || isLoading ? 'fa-spin' : ''}`}
            />
        </div>
    );
}

export default connect(null, { searchPurchaseProduct })(AdvanceSearch);
