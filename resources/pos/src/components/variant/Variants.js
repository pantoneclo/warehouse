import { useEffect, useState } from "react";

import SweetAlert from "react-bootstrap-sweetalert";
import { useDispatch, useSelector } from "react-redux";
import { DragDropContext, Draggable, Droppable } from "react-beautiful-dnd";

import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faEye,
    faPenToSquare,
    faSearch,
    faTrash,
} from "@fortawesome/free-solid-svg-icons";

import MasterLayout from "../MasterLayout";
import remove from "../../assets/images/remove.png";
import TabTitle from "../../shared/tab-title/TabTitle";
import { editSetting } from "../../store/action/settingAction";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";

const Variant = () => {
    const dispatch = useDispatch();
    const { frontSetting } = useSelector((state) => state);
    const [possibleVariants, setPossibleVariants] = useState({});
    const [possibleVariantsNew, setPossibleVariantsNew] = useState({});
    const [possibleVariantsArray, setPossibleVariantsArray] = useState([]);
    const [editState, setEditState] = useState(false);
    const [textToEdit, setTextToEdit] = useState("");
    const [inputText, setInputText] = useState("");
    const [disableSave, setDisableSave] = useState(true);
    const [deleteText, setDeleteText] = useState("");
    const [show, setShow] = useState(false);
    const [openSweetAlert, setOpenSweetAlert] = useState(false);
    const [activeFilterField, setActiveFilterField] = useState(false);
    const [filteredArray, setFilteredArray] = useState([]);

    const handleChange = (e) => {
        const text = e.target.value;
        setInputText(text);
    };

    // close modal(add/edit) - state update
    const handleClose = () => {
        setShow(false);
    };

    // open modal(add/edit) - state update
    const handleShow = () => {
        setShow(true);
    };

    const reorderVariants = () => {
        const newVariants = {};
        let desiredData = {};
        possibleVariantsArray.forEach((key) => {
            if (possibleVariants[key]) {
                newVariants[key] = possibleVariants[key];
            } else {
                newVariants[key] = [];
            }
            desiredData = {
                possible_variant_list: newVariants,
            };
        });
        return newVariants;
    };

    // drag option
    function handleOnDragEnd(result) {
        if (!result.destination) return;
        const items = Array.from(possibleVariantsArray);
        const [reorderedItem] = items.splice(result.source.index, 1);
        items.splice(result.destination.index, 0, reorderedItem);
        setPossibleVariantsArray(items);
        setDisableSave(false);
    }

    const gotoDetailScreen = (item) => {
        window.location.href = "#/app/variants/details/" + item;
    };

    // new form submit data (controlled)
    const onSubmitFormData = (e) => {
        e.preventDefault();
        setInputText("");
        handleClose();
        if (!editState) {
            setPossibleVariantsArray((prev) => [...prev, inputText]);
        }
        if (editState) {
            // Convert the object into an array of [key, value] pairs
            const entries = Object.entries(possibleVariants);

            // Find the index of the entry that needs to be updated
            const entryIndex = entries.findIndex(
                (entry) => entry[0] === textToEdit
            );

            // If the key is found in the array
            if (entryIndex !== -1) {
                // Update the key while preserving the value
                entries[entryIndex] = [inputText, entries[entryIndex][1]];

                // Convert the array back into an object
                const updatedObject = Object.fromEntries(entries);

                // Update the state
                setPossibleVariants(updatedObject);
            }
        }
        reorderVariants();
        setEditState(false);
        setDisableSave(false);
    };

    // on save
    const handleSubmitData = () => {
        setDisableSave(true);
        dispatch(
            editSetting({
                possible_variant_list: possibleVariantsNew,
            })
        );
    };

    // on variant delete - show delete modal
    const handleDelete = (e) => {
        const text = e.currentTarget.parentElement.previousSibling.innerHTML;
        setDeleteText(text);
        setOpenSweetAlert(true);
    };

    // if yes delete item
    const deleteItem = () => {
        const updatedVariants = { ...possibleVariants };
        delete updatedVariants[deleteText];
        setPossibleVariants(updatedVariants);
        setDeleteText("");
        setDisableSave(false);
        setOpenSweetAlert(false);
    };

    // on variant edit
    const handleEdit = (e) => {
        const text = e.currentTarget.parentElement.previousSibling.innerHTML;
        setEditState(true);
        handleShow();
        setTextToEdit(text);
        setInputText(text);
    };

    // on variant add
    const handleAddVariant = (e) => {
        setInputText("");
        setEditState(false);
        handleShow();
    };

    // on modal close
    const handleModalCancel = () => {
        handleClose();
        setTextToEdit("");
    };

    // handle filter
    const handleFilter = (e) => {
        let text = e.target.value;
        console.log(text);
        const copy = [...possibleVariantsArray];
        if (text) {
            setActiveFilterField(true);
        } else {
            setActiveFilterField(false);
        }

        if (text.length > 0) {
            const filteredArray = copy.filter((elm) => elm.includes(text));
            console.log("if text true");
            setFilteredArray(filteredArray);
        } else {
            console.log("if text false");
            setFilteredArray(possibleVariantsArray);
        }
    };

    // load possible variants on frontsetting(redux state) change
    useEffect(() => {
        setPossibleVariants(frontSetting?.value?.possible_variant_list);
    }, [frontSetting]);

    // on possible varinat change , load possible variants array
    useEffect(() => {
        if (possibleVariants) {
            setPossibleVariantsArray(Object.keys(possibleVariants));
        }
    }, [possibleVariants]);

    // if dragged a list load new variants array to get the updated variants
    useEffect(() => {
        if (possibleVariantsArray.length > 0) {
            setPossibleVariantsNew(reorderVariants());
        }
    }, [possibleVariantsArray]);

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText("variant.title")} />

            {openSweetAlert && (
                <SweetAlert
                    custom
                    confirmBtnBsStyle="danger mb-3 fs-5 rounded"
                    cancelBtnBsStyle="secondary mb-3 fs-5 rounded text-white"
                    confirmBtnText={getFormattedMessage("delete-modal.yes-btn")}
                    cancelBtnText={getFormattedMessage("delete-modal.no-btn")}
                    title={getFormattedMessage("delete-modal.title")}
                    onConfirm={deleteItem}
                    onCancel={() => setOpenSweetAlert(false)}
                    showCancel
                    focusCancelBtn
                    customIcon={remove}
                >
                    <span className="sweet-text">
                        {getFormattedMessage("delete-modal.msg")} '{deleteText}'
                        ?
                    </span>
                </SweetAlert>
            )}

            <div className="mb-5">
                <div className="d-flex justify-content-between">
                    <Button variant="primary" onClick={handleAddVariant}>
                        Add Variant
                    </Button>
                    <div>
                        <form
                            style={{ width: "100%" }}
                            className="d-flex position-relative col-12 col-xxl-4 col-md-3 col-lg-4 mb-lg-0 mb-md-0 mb-3"
                        >
                            <div className="position-relative d-flex width-320">
                                <input
                                    className="form-control ps-8"
                                    type="search"
                                    id="search"
                                    placeholder={placeholderText(
                                        "react-data-table.searchbar.placeholder"
                                    )}
                                    aria-label="Search"
                                    onChange={handleFilter}
                                />
                                <span className="position-absolute d-flex align-items-center top-0 bottom-0 left-0 text-gray-600 ms-3">
                                    <FontAwesomeIcon icon={faSearch} />
                                </span>
                            </div>
                        </form>
                    </div>
                </div>

                {/* modal */}
                <Modal show={show} onHide={handleClose}>
                    <Modal.Header>
                        <Modal.Title>
                            {editState ? "Edit Variant" : "Add Variant"}
                        </Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <form onSubmit={onSubmitFormData}>
                            <div className="mb-3">
                                <label
                                    htmlFor="variant-name"
                                    className="form-label"
                                >
                                    Variant Name
                                </label>
                                <input
                                    autoFocus
                                    className="form-control"
                                    type="text"
                                    name="variantName"
                                    id="variantName"
                                    value={inputText}
                                    onChange={(e) => handleChange(e)}
                                    placeholder={
                                        editState
                                            ? "Edit Variant"
                                            : "Add Variant"
                                    }
                                />
                            </div>

                            <button
                                disabled={inputText.length <= 0}
                                type="submit"
                                className="btn btn-primary me-5"
                            >
                                Save
                            </button>

                            <button
                                type="button"
                                onClick={handleModalCancel}
                                className="btn btn-danger"
                            >
                                Cancel
                            </button>
                        </form>
                    </Modal.Body>
                </Modal>
            </div>

            {/* list items */}
            <div>
                {activeFilterField ? (
                    <DragDropContext onDragEnd={handleOnDragEnd}>
                        <Droppable droppableId="variants">
                            {(provided) => (
                                <ul
                                    className="variants list-group"
                                    {...provided.droppableProps}
                                    ref={provided.innerRef}
                                >
                                    {filteredArray.map((item, index) => {
                                        return (
                                            <Draggable
                                                key={item}
                                                draggableId={item}
                                                index={index}
                                            >
                                                {(provided) => (
                                                    <li
                                                        className="list-group-item bg-white d-flex align-items-center justify-content-between"
                                                        ref={provided.innerRef}
                                                        {...provided.draggableProps}
                                                        {...provided.dragHandleProps}
                                                    >
                                                        <span>{item}</span>
                                                        <div>
                                                            <button
                                                                disabled={
                                                                    !disableSave
                                                                }
                                                                onClick={() =>
                                                                    gotoDetailScreen(
                                                                        item
                                                                    )
                                                                }
                                                                title={placeholderText(
                                                                    "globally.view.tooltip.label"
                                                                )}
                                                                className="btn px-2 pe-0 text-info fs-3 border-0"
                                                            >
                                                                <FontAwesomeIcon
                                                                    icon={faEye}
                                                                />
                                                            </button>
                                                            <button
                                                                onClick={
                                                                    handleEdit
                                                                }
                                                                title={placeholderText(
                                                                    "globally.edit.tooltip.label"
                                                                )}
                                                                className="btn px-2 pe-0 text-primary fs-3 border-0"
                                                            >
                                                                <FontAwesomeIcon
                                                                    icon={
                                                                        faPenToSquare
                                                                    }
                                                                />
                                                            </button>
                                                            <button
                                                                onClick={
                                                                    handleDelete
                                                                }
                                                                title={placeholderText(
                                                                    "globally.delete.tooltip.label"
                                                                )}
                                                                className="btn px-2 pe-0 text-danger fs-3 border-0"
                                                            >
                                                                <FontAwesomeIcon
                                                                    icon={
                                                                        faTrash
                                                                    }
                                                                />
                                                            </button>
                                                        </div>
                                                    </li>
                                                )}
                                            </Draggable>
                                        );
                                    })}

                                    {provided.placeholder}
                                </ul>
                            )}
                        </Droppable>
                    </DragDropContext>
                ) : (
                    <DragDropContext onDragEnd={handleOnDragEnd}>
                        <Droppable droppableId="variants">
                            {(provided) => (
                                <ul
                                    className="variants list-group"
                                    {...provided.droppableProps}
                                    ref={provided.innerRef}
                                >
                                    {possibleVariantsArray.map(
                                        (item, index) => {
                                            return (
                                                <Draggable
                                                    key={item}
                                                    draggableId={item}
                                                    index={index}
                                                >
                                                    {(provided) => (
                                                        <li
                                                            className="list-group-item bg-white d-flex align-items-center justify-content-between"
                                                            ref={
                                                                provided.innerRef
                                                            }
                                                            {...provided.draggableProps}
                                                            {...provided.dragHandleProps}
                                                        >
                                                            <span>{item}</span>
                                                            <div>
                                                                <button
                                                                    disabled={
                                                                        !disableSave
                                                                    }
                                                                    onClick={() =>
                                                                        gotoDetailScreen(
                                                                            item
                                                                        )
                                                                    }
                                                                    title={placeholderText(
                                                                        "globally.view.tooltip.label"
                                                                    )}
                                                                    className="btn px-2 pe-0 text-info fs-3 border-0"
                                                                >
                                                                    <FontAwesomeIcon
                                                                        icon={
                                                                            faEye
                                                                        }
                                                                    />
                                                                </button>
                                                                <button
                                                                    onClick={
                                                                        handleEdit
                                                                    }
                                                                    title={placeholderText(
                                                                        "globally.edit.tooltip.label"
                                                                    )}
                                                                    className="btn px-2 pe-0 text-primary fs-3 border-0"
                                                                >
                                                                    <FontAwesomeIcon
                                                                        icon={
                                                                            faPenToSquare
                                                                        }
                                                                    />
                                                                </button>
                                                                <button
                                                                    onClick={
                                                                        handleDelete
                                                                    }
                                                                    title={placeholderText(
                                                                        "globally.delete.tooltip.label"
                                                                    )}
                                                                    className="btn px-2 pe-0 text-danger fs-3 border-0"
                                                                >
                                                                    <FontAwesomeIcon
                                                                        icon={
                                                                            faTrash
                                                                        }
                                                                    />
                                                                </button>
                                                            </div>
                                                        </li>
                                                    )}
                                                </Draggable>
                                            );
                                        }
                                    )}

                                    {provided.placeholder}
                                </ul>
                            )}
                        </Droppable>
                    </DragDropContext>
                )}

                <div className="mt-5">
                    <Button
                        disabled={disableSave}
                        type="primary"
                        onClick={handleSubmitData}
                    >
                        Save
                    </Button>
                </div>
            </div>
        </MasterLayout>
    );
};

export default Variant;
