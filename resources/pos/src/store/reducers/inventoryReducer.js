import { inventoryActionType } from '../../constants';

export default (state = [], action) => {
    switch (action.type) {
        case inventoryActionType.FETCH_INVENTORIES:
            return action.payload;
        case inventoryActionType.FETCH_INVENTORY:
            return [action.payload];
        case inventoryActionType.ADD_INVENTORY:
            return [...state, action.payload];
        case inventoryActionType.EDIT_INVENTORY:
            return state.map(item => item.id === +action.payload.id ? action.payload : item);
        case inventoryActionType.DELETE_INVENTORY:
            return state.filter(item => item.id !== action.payload);
        default:
            return state;
    }
};
