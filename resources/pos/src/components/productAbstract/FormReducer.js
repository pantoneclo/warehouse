export const INITIAL_STATE = {
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
    products: []
};

export const formReducer = (state, action) => {
    console.log('action', action , 'state', state)

    switch (action.type) {
        case 'CHANGE_INPUT':
            return {
                ...state,
                [action.payload.name]: action.payload.value
            };
        case 'CHANGE_CATEGORY':
            return {
                ...state,
                product_category_id: action.payload.value
            };
        case 'CHANGE_BRAND':
            return {
                ...state,
                brand_id: action.payload.value
            };
        case 'CHANGE_PRODUCT_UNIT':
            return {
                ...state,
                product_unit: action.payload.value
            };
        case 'CHANGE_SALE_UNIT':
            return {
                ...state,
                sale_unit: action.payload.value
            };
        case 'CHANGE_PURCHASE_UNIT':
            return {
                ...state,
                purchase_unit: action.payload.value
            };
        case 'CHANGE_TAX_TYPE':
            return {
                ...state,
                tax_type: action.payload.value
            };
        
        
        default:
            return state;
    }

};