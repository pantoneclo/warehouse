import React, { useEffect, useState } from "react";

import { useParams } from "react-router";
import SweetAlert from "react-bootstrap-sweetalert";
import { useDispatch, useSelector } from "react-redux";
import { DragDropContext, Draggable, Droppable } from "react-beautiful-dnd";

import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faPenToSquare,
    faSearch,
    faTrash,
    faArrowDown
} from "@fortawesome/free-solid-svg-icons";

import MasterLayout from "../MasterLayout";
import remove from "../../assets/images/remove.png";
import TabTitle from "../../shared/tab-title/TabTitle";
import { editSetting } from "../../store/action/settingAction";
import { fetchFrontSetting } from "../../store/action/frontSettingAction";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import {
    getFormattedMessage,
    placeholderText,
    ucwords,
} from "../../shared/sharedMethod";
import { useRef } from "react";

const VariantDetails = () => {
    const dispatch = useDispatch();
    const { id } = useParams();
    const { frontSetting } = useSelector((state) => state);

    // declare state
    const [possibleVariants, setPossibleVariants] = useState({});
    const [possibleVariantsArr, setPossibleVariantsArr] = useState([]);
    // modal on/off state
    const [show, setShow] = useState(false);
    // edit handle state
    const [editState, setEditState] = useState(false);
    const [textToEdit, setTextToEdit] = useState("");
    const [inputText, setInputText] = useState("");
    const [disableSave, setDisableSave] = useState(true);
    const [openSweetAlert, setOpenSweetAlert] = useState(false);
    const [deleteText, setDeleteText] = useState("");
    const [activeFilterField, setActiveFilterField] = useState(false);
    const [filteredArray, setFilteredArray] = useState([]);

    // drag option
    function handleOnDragEnd(result) {
        if (!result.destination) return;
        const items = Array.from(possibleVariantsArr);
        const [reorderedItem] = items.splice(result.source.index, 1);
        items.splice(result.destination.index, 0, reorderedItem);
        setPossibleVariantsArr(items);
        setDisableSave(false);
    }

    const bottomEle = useRef(null)
    const scrollToBottom = () => {
        bottomEle?.current?.scrollIntoView();
    }
    // reorder variants
    const reorderVariants = (data) => {
        let desiredData = {};
        if (data) {
            desiredData = {
                possible_variant_list: data,
            };
        }
        return desiredData;
    };

    const handleChange = (e) => {
        const text = e.target.value;
        setInputText(text);
    };

    const handleClose = () => {
        setShow(false);
    };

    const handleShow = () => {
        setShow(true);
    };

    // new form submit data (controlled)
    const onSubmitFormData = (e) => {
        e.preventDefault();
        setDisableSave(false);
        if (!editState) {
            setPossibleVariantsArr((prev) => [...prev, inputText]);
        }
        if (editState) {
            // Find the index of the item that matches textToEdit
            const indexToEdit = possibleVariantsArr.findIndex(
                (item) => item === textToEdit
            );

            // Replace the item at the found index with inputText
            if (indexToEdit !== -1) {
                setPossibleVariantsArr((prev) => [
                    ...prev.slice(0, indexToEdit),
                    inputText,
                    ...prev.slice(indexToEdit + 1),
                ]);
            }
        }
        // reorderVariants();
        setEditState(false);
        setInputText("");
        handleClose();
    };

    // on save call
    const handleSubmitData = () => {
        setDisableSave(true);
        const x = { ...possibleVariants, [id]: possibleVariantsArr };
        dispatch(editSetting(reorderVariants(x)));
        dispatch(fetchFrontSetting());
    };

    // on variant add
    const handleAddVariant = (e) => {
        setInputText("");
        const text = e.target.innerHTML;
        setEditState(text.toLowerCase().includes("edit"));
        handleShow();
    };

    // on variant delete
    const handleDelete = (e) => {
        const text = e.currentTarget.parentElement.previousSibling.innerHTML;
        setDeleteText(text);
        setOpenSweetAlert(true);
    };

    // if yes delete item
    const deleteItem = () => {
        // Find the index of the item that matches the text
        const indexToDelete = possibleVariantsArr.findIndex(
            (item) => item === deleteText
        );

        // Remove the item from the array if it is found
        if (indexToDelete !== -1) {
            setPossibleVariantsArr((prev) => [
                ...prev.slice(0, indexToDelete),
                ...prev.slice(indexToDelete + 1),
            ]);
        }

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

    // handle filter
    const handleFilter = (e) => {
        // setFilterText(e.target.value);
        let text = e.target.value;
        console.log(text);
        const copy = [...possibleVariantsArr];
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
            setFilteredArray(possibleVariantsArr);
        }
    };

    // load possible variants objects on frontsettings change ->  [{color: ['red', 'green'], size: ['x', 'xxl', 'm']}]
    useEffect(() => {
        setPossibleVariants(frontSetting?.value?.possible_variant_list);
    }, [frontSetting]);

    // load possible variants array -> [color, size]
    useEffect(() => {
        if (possibleVariants && id) {
            setPossibleVariantsArr(possibleVariants[id]);
        }
    }, [possibleVariants]);

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
                        Add Variant Item
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
                    <Modal.Header closeButton>
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
                                    Variant Item Name
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
                                            : "Add Variant Item"
                                    }
                                />
                            </div>
                            <button
                                disabled={
                                    inputText.length <= 0 ||
                                    textToEdit.toLowerCase() ===
                                    inputText.toLowerCase()
                                }
                                type="submit"
                                className="btn btn-primary"
                            >
                                {editState ? "Update" : "Add"}
                            </button>
                        </form>
                    </Modal.Body>
                </Modal>
            </div>
            <div className="d-flex align-items-center "style={{gap:'20px'}}>
                <div>
                    <h2>Variant Name: {ucwords(id)}</h2>
                </div>
                <div>
                    <button className="btn btn-success mb-3" onClick={scrollToBottom}
                      
                    >

                        <FontAwesomeIcon
                            icon={
                                faArrowDown
                            }
                        />
                    </button>
                </div>


            </div>


            {activeFilterField ? (
                <div>
                    <DragDropContext onDragEnd={handleOnDragEnd}>
                        <Droppable droppableId="variants">
                            {(provided) => (
                                <ul
                                    className="variants list-group"
                                    {...provided.droppableProps}
                                    ref={provided.innerRef}
                                >
                                    {filteredArray?.map((item, index) => {
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
            ) : (
                <div>
                    <DragDropContext onDragEnd={handleOnDragEnd}>
                        <Droppable droppableId="variants">
                            {(provided) => (
                                <ul
                                    className="variants list-group"
                                    {...provided.droppableProps}
                                    ref={provided.innerRef}
                                >
                                    {possibleVariantsArr?.map((item, index) => {
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

                    <div className="mt-5" ref={bottomEle}>
                        <Button
                            disabled={disableSave}
                            type="primary"
                            onClick={handleSubmitData}
                        >
                            Save
                        </Button>
                    </div>
                </div>
            )}
            {/* Draggable component */}
        </MasterLayout>
    );
};

export default VariantDetails;
