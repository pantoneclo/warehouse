import {packageActionType} from '../../constants';

export default (state = [], action) => {
    switch (action.type) {
        case packageActionType.FETCH_PACKAGES:
            return [...action.payload];
        case packageActionType.FETCH_PACKAGE:
            return [action.payload];
        case packageActionType.ADD_PACKAGE:
                return action.payload;
         case packageActionType.EDIT_PACKAGE:
              return state.map(item => item.id === +action.payload.id ? action.payload : item);
        case packageActionType.DELETE_PACKAGE:
              return state.filter(item => item.id !== action.payload);

        case packageActionType.FETCH_ALL_PACKAGES:
            return action.payload;
        
        case packageActionType.ADD_WAREHOUSE_TO_PACKAGES:    
            return action.payload;    
        default:
            return state;
    }
};
