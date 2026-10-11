import React, {useEffect, useState} from 'react';
import MasterLayout from '../../MasterLayout';
import TabTitle from '../../../shared/tab-title/TabTitle';
import {currencySymbolHendling, getFormattedMessage, placeholderText} from '../../../shared/sharedMethod';
import {connect} from 'react-redux';
import TopProgressBar from "../../../shared/components/loaders/TopProgressBar";
import {Col, Row} from "react-bootstrap";
import ProfitLossWidget from "../../../shared/Widget/ProfitLossWidget";
import {FontAwesomeIcon} from "@fortawesome/react-fontawesome";
import {
    faArrowLeft,faArrowRight,
    faCartPlus, faSquarePlus,
    faShoppingCart, faSquareMinus,
    faMoneyBillTrendUp, faMoneyBillTransfer
} from "@fortawesome/free-solid-svg-icons";
import DateRangePicker from "../../../shared/datepicker/DateRangePicker";
import {Filters} from "../../../constants";
import {dateFormat} from '../../../constants';
import moment from 'moment';
import { fetchProfitAndLossReports } from '../../../store/action/profitAndLossReportAction';
import { fetchFrontSetting } from '../../../store/action/frontSettingAction';


const ProfitLossReport = (props) => {
    const {fetchFrontSetting,frontSetting, fetchProfitAndLossReports, profitAndLossReport, allConfigData} = props
    const [selectDate, setSelectDate] = useState();
    const [displayCurrency, setDisplayCurrency] = useState('EUR');
    const [created_at] = useState(Filters.OBJ.created_at);
    const startMonth = moment().startOf('month').format(dateFormat.NATIVE);
    const today = moment().format(dateFormat.NATIVE);

    useEffect(() => {
        fetchFrontSetting();
    }, []);

    useEffect(() => {
        onChangeDidMount();
    }, [selectDate]);

    const onChange = (filter) => {
        fetchProfitAndLossReports(filter, true)
    };

    const onDateSelector = (date) => {
        setSelectDate(date.params);
    };

    const onChangeDidMount = () => {
        const filters = {
            created_at: created_at,
            search: '',
            start_date: selectDate ? selectDate.start_date : startMonth,
            end_date: selectDate ? selectDate.end_date : today,
        };
        onChange(filters);
    };

    const isUsd = displayCurrency === 'USD';
    const activeSymbol = isUsd ? '$' : (frontSetting.value && frontSetting.value.currency_symbol ? frontSetting.value.currency_symbol : '€');

    const salesVal = isUsd ? (profitAndLossReport.sales_usd ?? (Number(profitAndLossReport.sales || 0) / 0.92)) : (profitAndLossReport.sales || 0);
    const purchasesVal = isUsd ? (profitAndLossReport.purchases_usd ?? (Number(profitAndLossReport.purchases || 0) / 0.92)) : (profitAndLossReport.purchases || 0);
    const saleReturnsVal = isUsd ? (profitAndLossReport.sale_returns_usd ?? (Number(profitAndLossReport.sale_returns || 0) / 0.92)) : (profitAndLossReport.sale_returns || 0);
    const purchaseReturnsVal = isUsd ? (profitAndLossReport.purchase_returns_usd ?? (Number(profitAndLossReport.purchase_returns || 0) / 0.92)) : (profitAndLossReport.purchase_returns || 0);
    const expensesVal = isUsd ? (profitAndLossReport.expenses_usd ?? (Number(profitAndLossReport.expenses || 0) / 0.92)) : (profitAndLossReport.expenses || 0);
    const revenueVal = isUsd ? (profitAndLossReport.Revenue_usd ?? (Number(profitAndLossReport.Revenue || 0) / 0.92)) : (profitAndLossReport.Revenue || 0);
    const productCostVal = isUsd ? (profitAndLossReport.product_cost_usd ?? (Number(profitAndLossReport.product_cost || 0) / 0.92)) : (profitAndLossReport.product_cost || 0);
    const grossProfitVal = isUsd ? (profitAndLossReport.gross_profit_usd ?? (Number(profitAndLossReport.gross_profit || 0) / 0.92)) : (profitAndLossReport.gross_profit || 0);
    const salesPaymentVal = isUsd ? (profitAndLossReport.sales_payment_amount_usd ?? (Number(profitAndLossReport.sales_payment_amount || 0) / 0.92)) : (profitAndLossReport.sales_payment_amount || 0);
    const paymentsReceivedVal = isUsd ? (profitAndLossReport.payments_received_usd ?? (Number(profitAndLossReport.payments_received || 0) / 0.92)) : (profitAndLossReport.payments_received || 0);

    return (
        <MasterLayout>
            <TopProgressBar/>
            <TabTitle title={placeholderText('profit-loss.reports.title')}/>
            <div className={"d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3"}>
                <DateRangePicker onDateSelector={onDateSelector} isProfitReport={true} selectDate={selectDate}/>
                <div className='d-flex align-items-center'>
                    <span className='fw-bold me-2 text-gray-700'>Currency View:</span>
                    <div className='btn-group' role='group' aria-label='Currency View Switcher'>
                        <button
                            type='button'
                            className={`btn btn-sm ${displayCurrency === 'EUR' ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setDisplayCurrency('EUR')}
                        >
                            💶 EURO (€)
                        </button>
                        <button
                            type='button'
                            className={`btn btn-sm ${displayCurrency === 'USD' ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setDisplayCurrency('USD')}
                        >
                            💵 USD ($)
                        </button>
                    </div>
                </div>
            </div>
            <Row className='g-4'>
                <Col className='col-12 mb-4'>
                    <Row className={"align-items-start"}>
                        <ProfitLossWidget className={'bg-primary'} iconClass='bg-cyan-300' currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faShoppingCart} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage('sales.title')} allConfigData={allConfigData}
                                          value={parseFloat(salesVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'bg-success'} iconClass='bg-green-300' currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faCartPlus} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage('purchases.title')} allConfigData={allConfigData}
                                          value={parseFloat(purchasesVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'bg-info'} iconClass='bg-blue-300' currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faArrowRight} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage("sales-return.title")} allConfigData={allConfigData}
                                          value={parseFloat(saleReturnsVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'bg-warning'} iconClass='bg-yellow-300' currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faArrowLeft} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage("purchases.return.title")} allConfigData={allConfigData}
                                          value={parseFloat(purchaseReturnsVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'widget-bg-purple'} iconClass='widget-bg-blue-700 ' currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faSquareMinus} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage('expenses.title')} allConfigData={allConfigData}
                                          value={parseFloat(expensesVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'widget-bg-pink'} currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faSquarePlus} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage('global.revenue.title')} allConfigData={allConfigData}
                                          moreText={`(${currencySymbolHendling(allConfigData, activeSymbol, salesVal || 0)} ${placeholderText('sales.title')}) - (${currencySymbolHendling(allConfigData, activeSymbol, saleReturnsVal || 0)} ${placeholderText('sales-return.title')})`}
                                          value={parseFloat(revenueVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'widget-bg-blue'} currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faMoneyBillTrendUp} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage('global.gross-profit.title')} allConfigData={allConfigData}
                                          moreText={`(${currencySymbolHendling(allConfigData, activeSymbol, salesVal || 0)} ${placeholderText('sales.title')}) - (${currencySymbolHendling(allConfigData, activeSymbol, productCostVal || 0)} ${placeholderText('product.input.product-cost.label')})`}
                                          value={parseFloat(grossProfitVal || 0).toFixed(2)}/>

                        <ProfitLossWidget className={'widget-bg-red'} currency={activeSymbol}
                                          icon={<FontAwesomeIcon icon={faMoneyBillTransfer} className='fs-1-xl text-white'/>}
                                          title={getFormattedMessage('global.payment-received.title')} allConfigData={allConfigData}
                                          moreText={`(${currencySymbolHendling(allConfigData, activeSymbol, salesPaymentVal || 0)} ${placeholderText('global.payment-received.title')}) + (${currencySymbolHendling(allConfigData, activeSymbol, purchaseReturnsVal || 0)} ${placeholderText('purchases.return.title')})`}
                                          value={parseFloat(paymentsReceivedVal || 0).toFixed(2)}/>
                    </Row>
                </Col>
            </Row>
        </MasterLayout>
    )
};
const mapStateToProps = (state) => {
    const {frontSetting, profitAndLossReport, allConfigData} = state;
    return {frontSetting, profitAndLossReport, allConfigData}
};

export default connect(mapStateToProps, {
    fetchProfitAndLossReports,
    fetchFrontSetting
})(ProfitLossReport);

