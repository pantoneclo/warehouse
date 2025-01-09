import { warehouseActionType } from '../../constants';

const warehouseIdReducer = (state = '', action) => {
    switch (action.type) {
        case warehouseActionType.SET_WAREHOUSE_ID:
            console.log('Setting warehouse_id:', action.payload);
            return action.payload;
        default:
            return state;
    }
};

export default warehouseIdReducer;
