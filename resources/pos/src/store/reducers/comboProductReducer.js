import { comboProductType } from '../../constants';

const comboProductReducer = (state = [], action) => {
    switch (action.type) {
        case comboProductType.FETCH_COMBO_PRODUCTS:
            return action.payload; // Return the array of combos
        case comboProductType.FETCH_COMBO_PRODUCT:
            return [action.payload]; // Wrap the single combo in an array
        default:
            return state;
    }
};

export default comboProductReducer;
