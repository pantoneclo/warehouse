import { advancedSearchActionType } from "../../constants";
export default (state = [], action) => {
    switch (action.type) {
        case advancedSearchActionType.FETCH_WAREHOUSE_PRODUCTS_SEARCH:
        return action.payload;
    
        default:
        return state;
    }
    ;}
