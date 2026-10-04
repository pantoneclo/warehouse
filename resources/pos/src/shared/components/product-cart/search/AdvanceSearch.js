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

    const formatVariantText = (variant) => {
        if (!variant) return '';
        if (typeof variant === 'string') return variant;
        return Object.keys(variant).map(k => `${k}: ${variant[k]}`).join(', ');
    };

    const filterProducts = products ? isAllProducts ?
        products.map((item) => ({
            name: item.attributes.name, brand: item.attributes.brand_name, code: item.attributes.code, id: item.id, variant: item.attributes.variant, pan_style: item.attributes.pan_style, variant_text: formatVariantText(item.attributes.variant)
        }))
        : products.filter((qty) => {
            if (!qty || !qty.attributes) return false;
            const whId = values?.warehouse_id?.value || values?.warehouse_id;
            const stockInWh = qty.attributes.stocks?.find(s => s.warehouse_id === whId);
            const qtyInWh = stockInWh ? stockInWh.quantity : (qty.attributes.stock ? qty.attributes.stock.quantity : 0);
            return qtyInWh > 0 || isAllProducts;
        }).map((item) => ({
            name: item.attributes.name, brand: item.attributes.brand_name, code: item.attributes.code, id: item.id, variant: item.attributes.variant, pan_style: item.attributes.pan_style, variant_text: formatVariantText(item.attributes.variant)
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


    const processProductAddition = (newProd, prevProducts) => {
        if (!newProd) return prevProducts;
        const warehouseStock = Number(newProd.stock || 0);

        const existingIndex = prevProducts.findIndex(exist =>
            (exist.product_id != null && newProd.product_id != null && String(exist.product_id) === String(newProd.product_id)) ||
            (exist.id != null && newProd.id != null && String(exist.id) === String(newProd.id)) ||
            (exist.code && newProd.code && String(exist.code).trim().toLowerCase() === String(newProd.code).trim().toLowerCase())
        );

        if (existingIndex !== -1) {
            const existingItem = prevProducts[existingIndex];
            const currentQty = Number(existingItem.quantity || 0);
            const newQty = currentQty + 1;

            if (!isAllProducts && newQty > warehouseStock) {
                dispatch(addToast({
                    text: getFormattedMessage('sale.product-qty-validate.message') || `Quantity exceeds available warehouse stock (${warehouseStock})`,
                    type: toastType.ERROR
                }));
                return prevProducts;
            }

            const unitPrice = Number(existingItem.net_unit_price || existingItem.product_price || 0);
            const newSubTotal = Number(newQty * unitPrice).toFixed(2);
            const updated = [...prevProducts];
            updated[existingIndex] = {
                ...existingItem,
                quantity: newQty,
                sub_total: newSubTotal
            };
            return updated;
        } else {
            if (!isAllProducts && warehouseStock < 1) {
                dispatch(addToast({
                    text: getFormattedMessage('globally.product-out-of-stock.validate.message') || `Product is out of stock in this warehouse`,
                    type: toastType.ERROR
                }));
                return prevProducts;
            }

            const unitPrice = Number(newProd.net_unit_price || newProd.product_price || 0);
            const initialQty = 1;
            const formattedNewProd = {
                ...newProd,
                quantity: initialQty,
                sub_total: (initialQty * unitPrice).toFixed(2)
            };
            return [...prevProducts, formattedNewProd];
        }
    };

    useEffect(() => {
        console.log(products, 'products inside of useeffect')

        if (products && products.length > 0 && searchString) {
            const warehouseId = values?.warehouse_id?.value || values?.warehouse_id;
            let preparedProducts = preparePurchaseProductArray(products, warehouseId);

            if ((!preparedProducts || preparedProducts.length === 0 || preparedProducts.some(p => !p)) && customProducts && customProducts.length > 0) {
                preparedProducts = products.map((product) => {
                    return customProducts.find(element => element?.product_id === (product.attributes?.product_id || product.id)) || preparePurchaseProductArray([product], warehouseId)[0];
                }).filter(Boolean);
            }

            if (preparedProducts && preparedProducts.length > 0) {
                const searchTrimmed = searchString.trim();
                const searchLower = searchTrimmed.toLowerCase();
                const isComboSearch = searchTrimmed.toUpperCase().startsWith('COMBO');
                const isPackageSearch = searchTrimmed.toUpperCase().startsWith('PK_');

                // Check if search matches a single product code exactly
                const exactCodeMatch = preparedProducts.find(p => String(p.code).trim().toLowerCase() === searchLower);

                if (exactCodeMatch) {
                    // Exact product code match (user scanned or searched a specific product barcode/code)
                    setUpdateProducts(prevProducts => processProductAddition(exactCodeMatch, prevProducts));
                    setSearchString('');
                } else if (preparedProducts.length === 1 && !isComboSearch && !isPackageSearch) {
                    // Only 1 single product returned
                    setUpdateProducts(prevProducts => processProductAddition(preparedProducts[0], prevProducts));
                    setSearchString('');
                } else {
                    // Multiple products returned (e.g. pan_style, package, combo, or style with all variants)
                    // Add ALL variant products to updateProducts without any restriction
                    setUpdateProducts(prevProducts => {
                        let updated = [...prevProducts];
                        preparedProducts.forEach(prod => {
                            if (prod) {
                                updated = processProductAddition(prod, updated);
                            }
                        });
                        return updated;
                    });
                    setSearchString('');
                }
            }
        }

        setIsSearching(false);
    }, [products]);



    const handleOnSearch = (string) => {
        onProductSearch(string);
    };

    const handleOnSelect = (result) => {
        if (!result) return;
        const warehouseId = values?.warehouse_id?.value || values?.warehouse_id;
        let matchedProduct = products && products.find(p => String(p.id) === String(result.id) || String(p.attributes?.code).trim().toLowerCase() === String(result.code).trim().toLowerCase());
        let prepared = matchedProduct ? preparePurchaseProductArray([matchedProduct], warehouseId) : [];

        if ((!prepared || prepared.length === 0 || !prepared[0]) && customProducts) {
            const fallback = customProducts.find(p => String(p.product_id || p.id) === String(result.id) || String(p.code).trim().toLowerCase() === String(result.code).trim().toLowerCase());
            if (fallback) prepared = [fallback];
        }

        if (prepared && prepared.length > 0 && prepared[0]) {
            setUpdateProducts(prevProducts => processProductAddition(prepared[0], prevProducts));
        }
        setSearchString('');
    };

    const formatResult = (item) => {
        console.log(item, 'item')

        const variantKeys = item.variant ? Object.keys(item.variant) : [];
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
        if (html) html.style.display = 'none'
    }

    return (

        <div className='position-relative custom-search'>
            <ReactSearchAutocomplete
                items={filterProducts}
                onSearch={handleOnSearch}
                inputSearchString={searchString}
                fuseOptions={{ keys: ['code', 'name', 'variant_text', 'pan_style'] }}
                resultStringKeyName='code'
                placeholder={placeholderText('globally.search.field.label')}
                onSelect={handleOnSelect}
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
