import {productAbstractActionType} from '../../constants';

export default (state = {}, action) => {
    switch (action.type) {
        case productAbstractActionType.FETCH_ABSTRACT_PRODUCTS:
            return action.payload;
        case productAbstractActionType.ADD_ABSTRACT_PRODUCT:
            return [...state, action.payload];
        case productAbstractActionType.FETCH_ABSTRACT_PRODUCT:
            return [action.payload];
        case productAbstractActionType.EDIT_ABSTRACT_PRODUCT:
            return state.map(item => item.id === +action.payload.id ? action.payload : item);
        case productAbstractActionType.DELETE_ABSTRACT_PRODUCT:
            return state.filter(item => item.id !== +action.payload);

        case productAbstractActionType.FETCH_ALL_ABSTRACT_PRODUCTS:
            return action.payload;

        default:
            return state;
    }
}

