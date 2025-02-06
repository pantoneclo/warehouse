
export default (state = 1, action) => {
    switch (action.type) {
        case "UPDATE_PRINT_QTY":
            return action.payload;
        default:
            return state;
    }
}
