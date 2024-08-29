import {variantActionType} from '../../constants';

export default (state = {}, action) => {
    switch (action.type) {
        case variantActionType.FETCH_VARIANTS:
            return action.payload;
        case variantActionType.FETCH_ALL_VARIANTS:
            return action.payload;
        case variantActionType.ADD_VARIANT:
            return [...state, action.payload];
        case variantActionType.EDIT_VARIANT:
            return state.map(item => item.id === action.payload.id ? action.payload : item);
            
        case variantActionType.DELETE_VARIANT:
            return state.filter(item => item.id !== action.payload);
        default:
            return state;
    }
}
