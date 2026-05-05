import {matrixLeadActionType} from '../../constants';

export default (state = [], action) => {
    switch (action.type) {
        case matrixLeadActionType.FETCH_MATRIX_LEADS:
            return action.payload;
        case matrixLeadActionType.UPDATE_MATRIX_LEAD_STATUS:
            return state.map(lead => lead.id === action.payload.id ? action.payload : lead);
        default:
            return state;
    }
};
