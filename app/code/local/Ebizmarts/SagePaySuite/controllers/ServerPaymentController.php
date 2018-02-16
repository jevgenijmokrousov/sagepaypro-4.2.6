<?php

/**
 * SERVER payment controller
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_ServerPaymentController extends Mage_Core_Controller_Front_Action
{
    /*
     * Define end of line character used to correctly format response to Sage Pay Server
     * @access public
     */

    public $eoln = "\r\n";

    public function preDispatch()
    {
        $storeId = $this->getRequest()->getParam('storeid');
        if ($storeId) {
            Mage::app()->setCurrentStore((int)$storeId);
        }

        //Thanks to our partner agency de-facto.com for the feedback on this one.
        parent::preDispatch();
        Mage::getModel('sagepaysuite/api_payment')->getQuote();

        return $this;
    }

    /**
     * Load and order by its incremental ID attribute
     *
     * @access protected
     * @param $orderId integer The ID of the order
     * @return Order Object
     */
    protected function _loadOrderById($orderId)
    {
        return Mage:: getModel('sales/order')->loadByAttribute('entity_id', (int)$orderId);
    }

    public function getServerModel()
    {
        return Mage:: getModel('sagepaysuite/sagePayServer');
    }

    private function isPreSaveEnabled()
    {
        return (int)Mage::getStoreConfig('payment/sagepayserver/pre_save') === 1;
    }

    public function saveOrderAction()
    {

        $this->_expireAjax();

        $resultData = array();

        try {
            Mage::helper('sagepaysuite')->validateQuote();

            $result = $this->getServerModel()->registerTransaction($this->getRequest()->getPost());
            $resultData = $result->getData();

            if ($result->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment :: RESPONSE_CODE_APPROVED) {
                if ($this->isPreSaveEnabled()) {
                    //save order process
                    $dbtrn = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')->loadByVendorTxCode($result->getVendorTxCode());

                    //disable email sending until order is completed
                    Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");

                    //save order
                    $sOrder = $this->_saveMagentoOrder();

                    if ($sOrder !== true) {
                        $resultData['response_status'] = 'ERROR';
                        $resultData['response_status_detail'] = 'An error occurred: ' . $sOrder;
                    } else {
                        $orderId = Mage::registry('last_order_id');
                        $msOrderIds = $this->_getMsOrderIds();
                        if ($orderId || $msOrderIds) {
                            if (false !== $msOrderIds) {
                                $aidis = array_keys($msOrderIds);
                                $orderId = $aidis[0];
                                $dbtrn->setOrderId($aidis[0])->save();

                                unset($aidis[0]);
                                $trns = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                    ->getCollection()
                                    ->getChilds($dbtrn->getId())
                                    ->load()->toArray();
                                foreach ($aidis as $_order) {
                                    foreach ($trns['items'] as $ka => $_t) {
                                        Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                            ->load($_t['id'])->setOrderId($_order)->save();
                                        unset($trns['items'][$ka]);
                                        break;
                                    }
                                }
                            }
                        }

                        $order = Mage::getModel('sales/order')->load($orderId);

                        $dbtrn->setOrderId($orderId)->save();

                        //set pre-saved order flag in checkout session
                        Mage::getSingleton('sagepaysuite/session')->setData("sagepaysuite_presaved_order_pending_payment", $order->getId());

                        $redirectUrl = $result->getNextUrl();
                        $resultData['success'] = true;
                        $resultData['error'] = false;
                    }
                } else {
                    $redirectUrl = $result->getNextUrl();
                    $resultData['success'] = true;
                    $resultData['error'] = false;
                }
            }
        } catch (Exception $e) {
            $resultData['response_status'] = 'ERROR';
            $resultData['response_status_detail'] = $e->getMessage();
            Mage::dispatchEvent('sagepay_payment_failed', array('quote' => Mage::getSingleton('sagepaysuite/api_payment')->getQuote(), 'message' => $e->getMessage()));
        }

        if (isset($redirectUrl)) {
            $resultData['redirect'] = $redirectUrl;
        }

        //remove security key
        $resultData['security_key'] = "HIDDEN";

        return $this->getResponse()->setBody(Zend_Json::encode($resultData));
    }

    public function getSPSModel()
    {
        return Mage:: getModel('sagepaysuite/sagePayServer');
    }

    protected function _expireAjax()
    {
        if (!Mage:: getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired')->setHeader('Login-Required', 'true')->sendResponse();
            return;
        }
    }

    protected function _setAdditioanlPaymentInfo($status)
    {
        $requestParams = $this->getRequest()->getParams();
        unset($requestParams['SID']);
        unset($requestParams['VPSProtocol']);
        unset($requestParams['TxType']);
        unset($requestParams['VPSSignature']);

        $requestParams['CustomStatusCode'] = $this->_getSagePayServerSession()->getTrnDoneStatus();
        $info = serialize($requestParams);

        $this->_getSagePayServerSession()->setTrnDoneStatus(null);

        return $info;
    }

    protected function _getAbortRedirectUrl()
    {
        $url = Mage:: getUrl(
            'sgps/ServerPayment/abortredirect', array(
            '_secure' => true,
            '_current' => true,
            '_store' => $this->getRequest()->getParam('storeid', Mage::app()->getStore()->getId()),
            'storeid' => $this->getRequest()->getParam('storeid', Mage::app()->getStore()->getId()),
            )
        );

        return $url;
    }

    protected function _getSuccessRedirectUrl($params = array())
    {
        $encodedParams = Mage::helper('sagepaysuite')->sanitizeParamsForQuery($params);

        $myParams = array_merge(
            array(
            '_secure'  => true,
            '_current' => true,
            '_store'   => $this->getRequest()->getParam('storeid', Mage::app()->getStore()->getId()),
            'storeid'  => $this->getRequest()->getParam('storeid', Mage::app()->getStore()->getId()),
            ), $encodedParams
        );

        $url = Mage:: getUrl('sgps/ServerPayment/success', $myParams);

        return $url;
    }

    protected function _getFailedRedirectUrl()
    {
        $url = Mage:: getUrl(
            'sgps/ServerPayment/failure', array(
            '_secure' => true,
            '_current' => true,
            '_store' => $this->getRequest()->getParam('storeid', Mage::app()->getStore()->getId()),
            'storeid' => $this->getRequest()->getParam('storeid', Mage::app()->getStore()->getId()),
            )
        );

        return $url;
    }

    protected function _trn()
    {
        return Mage::getModel('sagepaysuite2/sagepaysuite_transaction')->loadByVendorTxCode($this->getRequest()->getParam('VendorTxCode'));
    }

    private function _returnOkAbort()
    {
        $strResponse = 'Status=OK' . $this->eoln;
        $strResponse .= 'StatusDetail=Transaction ABORTED successfully' . $this->eoln;
        $strResponse .= 'RedirectURL=' . $this->_getAbortRedirectUrl() . $this->eoln;

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        Sage_Log::log($strResponse, null, 'SagePaySuite_SERVER_RESPONSE.log');

        return;
    }

    private function _returnOk($params = array())
    {
        $strResponse = 'Status=OK' . $this->eoln;
        $strResponse .= 'StatusDetail=Transaction completed successfully' . $this->eoln;
        $strResponse .= 'RedirectURL=' . $this->_getSuccessRedirectUrl($params) . $this->eoln;

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($strResponse);

        Sage_Log::log($strResponse, null, 'SagePaySuite_SERVER_RESPONSE.log');

        return;
    }

    private function _returnInvalid($message = 'Unable to find the transaction in our database.')
    {
        $response = 'Status=INVALID' . $this->eoln;
        $response .= 'RedirectURL=' . $this->_getFailedRedirectUrl() . $this->eoln;
        $response .= 'StatusDetail=' . $message . $this->eoln;

        if (!$this->_getSagePayServerSession()->getFailStatus()) {
            $this->_getSagePayServerSession()->setFailStatus($message);
        }

        Sage_Log::log($message);
        Sage_Log::log($this->getRequest()->getPost());
        Sage_log::log($this->_getSagePayServerSession()->getData());

        Mage::dispatchEvent('sagepay_payment_failed', array('quote' => $this->getOnepage()->getQuote(), 'message' => $message));

        $this->getResponse()->setHeader('Content-type', 'text/plain');
        $this->getResponse()->setBody($response);

        Sage_Log::log($response, null, 'SagePaySuite_SERVER_RESPONSE.log');

        return;
    }

    protected function _getHRStatus($strStatus, $strStatusDetail)
    {
        if ($strStatus == 'OK')
            $strDBStatus = 'AUTHORISED - The transaction was successfully authorised with the bank.';
        elseif ($strStatus == 'NOTAUTHED') {
            if ($strStatusDetail == "1003 : The transaction timed out.") {
                $strDBStatus = 'ERROR - The transaction timed out.';
            } else {
                $strDBStatus = 'DECLINED - The transaction was not authorised by the bank.';
            }
        } elseif ($strStatus == 'ABORT')
            $strDBStatus = 'ABORTED - The customer clicked Cancel on the payment pages, or the transaction was timed out due to customer inactivity.';
        elseif ($strStatus == 'REJECTED')
            $strDBStatus = 'REJECTED - The transaction was failed by your 3D-Secure or AVS/CV2 rule-bases.';
        elseif ($strStatus == 'AUTHENTICATED')
            $strDBStatus = 'AUTHENTICATED - The transaction was successfully 3D-Secure Authenticated and can now be Authorised.';
        elseif ($strStatus == 'REGISTERED')
            $strDBStatus = 'REGISTERED - The transaction could not be 3D-Secure Authenticated, but has been registered to be Authorised.';
        elseif ($strStatus == 'ERROR')
            $strDBStatus = 'ERROR - There was an error during the payment process.  The error details are: ' . $strStatusDetail;
        elseif ($strStatus == 'PENDING')
            $strDBStatus = 'PENDING - Transaction pending';
        else
            $strDBStatus = 'UNKNOWN - An unknown status was returned from Sage Pay.  The Status was: ' . $strStatus . ', with StatusDetail:' . $strStatusDetail;

        return $strDBStatus;
    }

    /**
     * Create Token in database after receiving token confirmation from API.
     *
     * @param type $_transaction
     */
    protected function _saveToken($_transaction)
    {

        $result = $this->getRequest();

        //Saving TOKEN.
        if ($result->getPost('Token')) {
            $tokenData = array(
                'Token' => $result->getPost('Token'),
                'Status' => $result->getPost('Status'),
                'Vendor' => $_transaction->getVendorname(),
                'CardType' => $result->getPost('CardType'),
                'ExpiryDate' => $result->getPost('ExpiryDate'),
                'StatusDetail' => $result->getPost('StatusDetail'),
                'Nickname' => filter_var($_transaction->getNickname(), FILTER_SANITIZE_STRING),
                'Protocol' => 'server',
                'CardNumber' => '00000000' . $result->getPost('Last4Digits')
            );

            $queryCustomerId = null;
            if ($this->getRequest()->getParam('c')) {
                $queryCustomerId = $this->getRequest()->getParam('c');
            }

            Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData, $queryCustomerId);
        }
    }

    protected function getLastNotifyId()
    {
        return Mage::getSingleton('core/session')->getRunningNotify();
    }

    protected function setLastNotifyId($id)
    {
        Mage::getSingleton('core/session')->setRunningNotify($id);
    }

    public function notifyAction()
    {
        Sage_Log::log($this->getRequest()->getPost(), null, 'SagePaySuite_POST_Requests.log');

        $postUniqueId = $this->getRequest()->getPost('VendorTxCode');

        if ($this->getLastNotifyId() != $postUniqueId) {
            $this->setLastNotifyId($postUniqueId);
        } else {
            Sage_Log::log("Duplicate post ignored: " . $postUniqueId, null, 'SagePaySuite_POST_Requests.log');
            return;
        }

        if ($this->isPreSaveEnabled()) {
            return $this->notifyActionWhenOrderPreSaved();
        }

        $request = $this->getRequest();
        $dbtrn = $this->_trn();
        $quote = null;

        //set euro payments status
        $euroPaymentStatus = ((string)$request->getPost("Status") == "PENDING" ? "PENDING" : (string)$dbtrn->getEuroPaymentsStatus());
        $returningFromEuroPayment = false;
        //if it was already 'PENDING' I update it
        if ((string)$dbtrn->getStatus() == "PENDING") {
            $euroPaymentStatus = $request->getPost("Status");
            $returningFromEuroPayment = true;
        }

        //reset session flag
        $this->_getSagePayServerSession()->setEuroPaymentIsPending(false);

        //$dbtrn->addData(Mage::helper('sagepaysuite')->arrayKeysToUnderscore($_POST))
        $dbtrn->setData("vps_protocol", $this->getRequest()->getPost('VPSProtocol'))
            ->setData("tx_type", $this->getRequest()->getPost('TxType'))
            ->setData("vendor_tx_code", $this->getRequest()->getPost('VendorTxCode'))
            ->setData("vps_tx_id", $this->getRequest()->getPost('VPSTxId'))
            ->setData("status", $this->getRequest()->getPost('Status'))
            ->setData("status_detail", $this->getRequest()->getPost('StatusDetail'))
            ->setData("tx_auth_no", $this->getRequest()->getPost('TxAuthNo'))
            ->setData("avscv2", $this->getRequest()->getPost('AVSCV2'))
            ->setData("address_result", $this->getRequest()->getPost('AddressResult'))
            ->setData("gift_aid", $this->getRequest()->getPost('GiftAid'))
            ->setData("cavv", $this->getRequest()->getPost('CAVV'))
            ->setData("card_type", $this->getRequest()->getPost('CardType'))
            ->setData("vps_signature", $this->getRequest()->getPost('VPSSignature'))
            ->setData("card_expiry_date", $this->getRequest()->getPost('ExpiryDate'))
            ->setPostcodeResult($this->getRequest()->getPost('PostCodeResult'))
            ->setData('cv2result', $this->getRequest()->getPost('CV2Result'))
            ->setThreedSecureStatus($this->getRequest()->getPost('3DSecureStatus'))
            ->setLastFourDigits($this->getRequest()->getPost('Last4Digits'))
            ->setRedFraudResponse($this->getRequest()->getPost('FraudResponse'))
            ->setSurchargeAmount($this->getRequest()->getPost('Surcharge'))
            ->setBankAuthCode($this->getRequest()->getPost('BankAuthCode'))
            ->setDeclineCode($this->getRequest()->getPost('DeclineCode'))
            ->setEuroPaymentsStatus($euroPaymentStatus)
            ->setData("server_notify_arrived", true)
            ->save();

        //Sage_Log::log("Notified: Transaction updated.", null, 'SagePaySuite_SERVER_RESPONSE.log');

        //Handle ABORT
        $sageStatus = $request->getParam('Status');
        if ($sageStatus == 'ABORT') {
            //Sage_Log::log("Notified: Aborted.", null, 'SagePaySuite_SERVER_RESPONSE.log');

            $this->_getSagePayServerSession()->setFailStatus($request->getParam('StatusDetail'));
            $dbtrn->setStatus($sageStatus)
                ->setStatusDetail($request->getParam('StatusDetail'))
                ->save();
            $this->_returnOkAbort();
            return;
        }

        if ($dbtrn->getId() && $dbtrn->getOrderId()) {
            //Sage_Log::log("Notified: Order already exists: " . $dbtrn->getOrderId(), null, 'SagePaySuite_SERVER_RESPONSE.log');

            if ($returningFromEuroPayment == true) { //EURO Payment PENDING doing 2nd POST

                if ($euroPaymentStatus == "OK") {
                    //invoice it
                    $order = Mage::getModel('sales/order')->load($dbtrn->getOrderId());
                    $invoiced = Mage::getModel('sagepaysuite/api_payment')->invoiceOrder($order);
                    if ($invoiced == true) {
                        $dbtrn->setStatusDetail($request->getParam('StatusDetail'))->save();
                        $order->setStatus("processing")->save();
                    }
                } else {
                    //transaction was failed, cancel it
                    Mage::helper('sagepaysuite')->cancelTransaction($dbtrn);
                    Sage_Log::log("Transaction " . $dbtrn->getVendorTxCode() . " cancelled due to error " . $request->getParam('StatusDetail'), '', '');
                }

                $this->_returnOk();
                return;
            } else {
                //Sage_Log::log("Notified: Sagepay RETRY.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                //general POST retry by SagePay
                if ($dbtrn->getStatus() == "OK") {
                    $order = Mage::getModel('sales/order')->load($dbtrn->getOrderId());
                    $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());

                    $createInvoice = $order->getInvoiceCollection()->count() ? false : true;

                    if ($order->getId() && $quote->getId()) {
                        $getDataToSend = array('inv' => (int)$createInvoice,
                            'cusid' => $quote->getData('customer_id'),
                            'qide' => $quote->getId(),
                            'incide' => $order->getIncrementId(),
                            'oide' => $order->getId());
                    }
                }

                $this->_returnOk(isset($getDataToSend) ? $getDataToSend : array());
                return;
            }
        }

        //Check cart health on callback.
        if (1 === (int)Mage::getStoreConfig('payment/sagepaysuite/verify_cart_consistency')) {
            if (Mage::helper('sagepaysuite/checkout')->cartExpire($this->getOnepage()->getQuote())) {
                try {
                    Mage::helper('sagepaysuite')->voidTransaction($dbtrn->getVendorTxCode(), 'sagepayserver');

                    Sage_Log::log("Transaction " . $dbtrn->getVendorTxCode() . " cancelled, cart was modified while customer on payment pages.", Zend_Log::CRIT, 'SagePaySuite_POST_Requests.log');
                } catch (Exception $ex) {
                    Sage_Log::log("Transaction " . $dbtrn->getVendorTxCode() . " could not be cancelled and order was not created, cart was modified while customer on payment pages.", Zend_Log::CRIT, 'SagePaySuite_POST_Requests.log');
                }

                $this->_returnInvalid('Your order could not be completed, please try again. Thanks.');
                return;
            }
        }

        //Check cart health on callback.

        $sagePayServerSession = $this->_getSagePayServerSession();

        $strVendorName = $this->getSPSModel()->getConfigData('vendor');
        $strStatus = $request->getParam('Status', '');
        $strVendorTxCode = $request->getParam('VendorTxCode', '');
        $strVPSTxId = $request->getParam('VPSTxId', '');
        $strSecurityKey = '';
        if ($dbtrn->getVendorTxCode() == $strVendorTxCode && $dbtrn->getVpsTxId() == $strVPSTxId) {
            $strSecurityKey = $dbtrn->getSecurityKey();
            $sagePayServerSession->setVpsTxId($strVPSTxId);
        }

        $response = '';


        if (strlen($strSecurityKey) == 0) { //check security key invalid

            Sage_Log::log("Security Key invalid", null, 'SagePaySuite_POST_Requests.log');
            Sage_Log::log("TRN from DB:", null, 'SagePaySuite_POST_Requests.log');
            Sage_Log::log($dbtrn->toArray(), null, 'SagePaySuite_POST_Requests.log');

            $dbtrn->setStatus('MAGE_ERROR')
                ->setStatusDetail("Security Key invalid. " . $dbtrn->getStatusDetail())
                ->save();

            $this->_returnInvalid('Security Key invalid');
        } else {
            $strStatusDetail = $strTxAuthNo = $strAVSCV2 = $strAddressResult = $strPostCodeResult = $strCV2Result = $strGiftAid = $str3DSecureStatus = $strCAVV = $strAddressStatus = $strPayerStatus = $strCardType = $strPayerStatus = $strLast4Digits = $strMySignature = '';

            $strVPSSignature = $request->getParam('VPSSignature', '');
            $strStatusDetail = $request->getParam('StatusDetail', '');

            if (strlen($request->getParam('TxAuthNo', '')) > 0) {
                $strTxAuthNo = $request->getParam('TxAuthNo', '');

                $sagePayServerSession->setTxAuthNo($strTxAuthNo);
            }

            $strAVSCV2 = $request->getParam('AVSCV2', '');
            $strAddressResult = $request->getParam('AddressResult', '');
            $strPostCodeResult = $request->getParam('PostCodeResult', '');
            $strCV2Result = $request->getParam('CV2Result', '');
            $strGiftAid = $request->getParam('GiftAid', '');
            $str3DSecureStatus = $request->getParam('3DSecureStatus', '');
            $strCAVV = $request->getParam('CAVV', '');
            $strAddressStatus = $request->getParam('AddressStatus', '');
            $strPayerStatus = $request->getParam('PayerStatus', '');
            $strCardType = $request->getParam('CardType', '');
            $strLast4Digits = $request->getParam('Last4Digits', '');
            $strDeclineCode = $request->getParam('DeclineCode', '');
            $strExpiryDate = $request->getParam('ExpiryDate', '');
            $strFraudResponse = $request->getParam('FraudResponse', '');
            $strBankAuthCode = $request->getParam('BankAuthCode', '');

            $strMessage = $strVPSTxId . $strVendorTxCode . $strStatus . $strTxAuthNo . $strVendorName . $strAVSCV2 . $strSecurityKey
                . $strAddressResult . $strPostCodeResult . $strCV2Result . $strGiftAid . $str3DSecureStatus . $strCAVV
                . $strAddressStatus . $strPayerStatus . $strCardType . $strLast4Digits . $strDeclineCode
                . $strExpiryDate . $strFraudResponse . $strBankAuthCode;

            $strMySignature = strtoupper(md5($strMessage));

            $response = '';

            /** We can now compare our MD5 Hash signature with that from Sage Pay Server * */
            $validSignature = ($strMySignature !== $strVPSSignature);

            if ($validSignature) { //check signature

                Sage_Log::log("Cannot match the MD5 Hash", null, 'SagePaySuite_POST_Requests.log');
                Sage_Log::log("My Message: $strMessage", null, 'SagePaySuite_POST_Requests.log');
                Sage_Log::log("My Signature: $strMySignature", null, 'SagePaySuite_POST_Requests.log');
                Sage_Log::log("VPS Signature: $strVPSSignature", null, 'SagePaySuite_POST_Requests.log');
                Sage_Log::log("TRN from DB:", null, 'SagePaySuite_POST_Requests.log');
                Sage_Log::log($dbtrn->toArray(), null, 'SagePaySuite_POST_Requests.log');

                $dbtrn->setStatus('MAGE_ERROR')
                    ->setStatusDetail("Cannot match the MD5 Hash. " . $dbtrn->getStatusDetail())
                    ->save();

                $this->_returnInvalid('Cannot match the MD5 Hash. Order might be tampered with. ' . $strStatusDetail);
                return;
            } else {
                $strDBStatus = $this->_getHRStatus($strStatus, $strStatusDetail);

                if ($strStatus == 'OK' || $strStatus == 'AUTHENTICATED' || $strStatus == 'REGISTERED') {
                    //Sage_Log::log("Notified: Transaction OK.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                    try {
                        $sagePayServerSession->setTrnhData($this->_setAdditioanlPaymentInfo($strDBStatus));

                        $checkoutSession = Mage::getSingleton('checkout/session');
                        if ($checkoutSession->getSagePayRewInst()) {
                            $this->getOnepage()->getQuote()
                                ->setUseRewardPoints(1)
                                ->setRewardInstance($checkoutSession->getSagePayRewInst());
                        }

                        if ($checkoutSession->getSagePayCustBalanceInst()) {
                            $this->getOnepage()->getQuote()
                                ->setUseCustomerBalance(1)
                                ->setCustomerBalanceInstance($checkoutSession->getSagePayCustBalanceInst());
                        }

                        if ((string)$request->getParam('Status') == 'OK' && (string)$request->getParam('TxType') == 'PAYMENT') {
                            $this->_getSagePayServerSession()->setInvoicePayment(true);
                            Mage::register('sagepay_create_invoice', 1, true);//For Magento 1.9+ when customer is Checkout=Register
                        }

                        $sageserverpost = new Varien_Object($this->getRequest()->getPost());

                        if (Mage::helper('sagepaysuite')->surchargesModuleEnabled() == true) {
                            //save surcharge to server post for later use if not already saved
                            $sessionSurchargeAmount = Mage::getSingleton('sagepaysuite/session')->getSurcharge();
                            if (!is_null($sessionSurchargeAmount) && $sessionSurchargeAmount > 0) {
                                $sageserverpost->setData('Surcharge', $sessionSurchargeAmount);
                            }
                        }

                        Mage::register('sageserverpost', $sageserverpost);

                        //1.9.1 ssl fix
                        $customerId = null;
                        $quote = $this->getOnepage()->getQuote();
                        if ($quote->getId() == null) {
                            $rqQuoteId = Mage::app()->getRequest()->getParam('qid');
                            $quote = Mage::getModel('sales/quote')->loadActive($rqQuoteId);
                            $this->getOnepage()->setQuote($quote);
                            Mage::app()->getStore()->setCurrentCurrencyCode($quote->getQuoteCurrencyCode()); //Thanks to Ross Kinsman for his input on this.
                            //Sage_Log::log("Notified: Checkout quote reloaded with id: " . $rqQuoteId, null, 'SagePaySuite_SERVER_RESPONSE.log');
                            $customerId = $this->getOnepage()->getQuote()->getData('customer_id');
                            if (!is_null($customerId)) {
                                $customer = Mage::getModel('customer/customer')->load($customerId);
                                if (!is_null($customer)) {
                                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                                    //Sage_Log::log("Notified: Customer logged-in", null, 'SagePaySuite_SERVER_RESPONSE.log');
                                }
                            }
                        }

                        //sweet tooth fix
                        if (class_exists('rewards/session', FALSE)) {
                            Mage::getSingleton('rewards/session')->getQuote()->setData('checkout_method', $this->getOnepage()->getQuote()->getData('checkout_method'));
                        }

                        //order comments
                        if ($dbtrn->getId() && $dbtrn->getData("server_order_comments")) {
                            if (FALSE !== Mage::getConfig()->getNode('modules/Idev_OneStepCheckout')) {
                                $quote->setOnestepcheckoutCustomercomment($dbtrn->getData("server_order_comments"));
                            } else {
                                Mage::getSingleton('sagepaysuite/session')->setOrderComments($dbtrn->getData("server_order_comments"));
                            }
                        }

                        //Saving TOKEN.
                        $this->_saveToken($dbtrn);

                        //saving order
                        $sOrder = $this->_saveMagentoOrder();

                        if ($sOrder !== true) {
                            //Sage_Log::log("Notified: Order NOT saved.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                            $sagePayServerSession->setFailStatus('An error occurred: ' . $sOrder);

                            /** The status indicates a failure of one state or another, so send the customer to orderFailed instead * */
                            $strRedirectPage = $this->_getFailedRedirectUrl();

                            //Mage::helper('sagepaysuite')->cancelTransaction($dbtrn);

                            $dbtrn->setStatus('MAGE_ERROR')
                                ->setStatusDetail('Could not save order: ' . $sOrder . $dbtrn->getStatusDetail())
                                ->save();

                            $this->_returnInvalid('Could not save order: ' . $sOrder);
                            return;
                        } else {
                            //Sage_Log::log("Notified: Order saved.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                            $orderId = Mage::registry('last_order_id');
                            $msOrderIds = $this->_getMsOrderIds();
                            if ($orderId || $msOrderIds) {
                                if (false !== $msOrderIds) {
                                    $aidis = array_keys($msOrderIds);
                                    $orderId = $aidis[0];
                                    #Mage::register('ms_parent_trn_id', $dbtrn->getId());
                                    $dbtrn->setOrderId($aidis[0])->save();

                                    unset($aidis[0]);
                                    $trns = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                        ->getCollection()
                                        ->getChilds($dbtrn->getId())
                                        ->load()->toArray();
                                    foreach ($aidis as $_order) {
                                        foreach ($trns['items'] as $ka => $_t) {
                                            Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                                ->load($_t['id'])->setOrderId($_order)->save();
                                            unset($trns['items'][$ka]);
                                            break;
                                        }
                                    }
                                }
                            }

                            $dbtrn->setOrderId($orderId)->save();
                            $sagePayServerSession->setSuccessStatus($strDBStatus);
                        }

                        $order = Mage::getModel('sales/order')->load($orderId);
                        $isRegister = ($quote->getData('checkout_method') == 'register');

                        if ($isRegister) {
                            //sweet tooth fix
                            if (Mage::registry('rewards_createPointsTransfers_run')) {
                                Mage::unregister('rewards_createPointsTransfers_run');
                                Mage::dispatchEvent('sales_order_save_commit_after', array('order' => $order));
                            }
                        }

                        //save server session data on db as it gets lost sometimes
                        $serverSession = array();
                        $messages = Mage::getSingleton('core/session')->getMessages();
                        $successes = $messages->getItemsByType("success");
                        $errors = $messages->getItemsByType("error");
                        if (!is_null($successes) && count($successes) > 0) {
                            $serverSession["core_messages"] = array();
                            $serverSession["core_messages"]["success"] = array();
                            foreach ($successes as $msg) {
                                $serverSession["core_messages"]["success"][] = $msg->getCode();
                            }
                        }

                        if (!is_null($errors) && count($errors) > 0) {
                            if (!array_key_exists("core_messages", $serverSession)) {
                                $serverSession["core_messages"] = array();
                            }

                            $serverSession["core_messages"]["error"] = array();
                            foreach ($errors as $msg) {
                                $serverSession["core_messages"]["error"][] = $msg->getCode();
                            }
                        }

                        if (count(array_keys($serverSession)) > 0) {
                            try {
                                $serverSessionJson = json_encode($serverSession);
                                $dbtrn->setData("server_session", $serverSessionJson)->save();
                            } catch (Exception $e) {
                                //unable to save server session data for later :/
                                Mage::logException($e);
                            }
                        }

                        Mage::getSingleton('checkout/session')->setSagePayRewInst(null)->setSagePayCustBalanceInst(null);
                        if ($quote->getId()) {
                            //Sage_Log::log("Notified: Return OK with quote.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                            $getDataToSend = array('inv' => (int)Mage::registry('sagepay_create_invoice'),
                                'cusid' => is_null($customerId) ? $quote->getData('customer_id') : $customerId,
                                'qide' => $quote->getId(),
                                'incide' => $order->getIncrementId(),
                                'oide' => $order->getId());

                            if (!empty($msOrderIds)) {
                                $idStrings = "";
                                foreach ($msOrderIds as $key => $value) {
                                    if ($idStrings != '') {
                                        $idStrings .= ",";
                                    }

                                    $idStrings .= $key . ":" . $value;
                                }

                                $getDataToSend["msorderids"] = $idStrings;
                            }

                            $this->_returnOk($getDataToSend);
                        } else {
                            //Sage_Log::log("Notified: Return OK.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                            $this->_returnOk();
                        }

                        return;
                    } catch (Exception $e) {
                        //Sage_Log::log("Notified: Catched Error.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                        $dbtrn->setStatus('MAGE_ERROR')
                            ->setStatusDetail($e->getMessage() . $dbtrn->getStatusDetail())
                            ->save();
                        Mage::logException($e);
                        Mage::log($e->getMessage());

                        Mage::dispatchEvent('sagepay_payment_failed', array('quote' => $this->getOnepage()->getQuote(), 'message' => $e->getMessage()));

                        $this->_returnOkAbort();
                    }
                } else if ($strStatus == 'PENDING') { //handle EURO payments

                    //set flag
                    $this->_getSagePayServerSession()->setEuroPaymentIsPending(true);

                    try {
                        $sagePayServerSession->setTrnhData($this->_setAdditioanlPaymentInfo($strDBStatus));

                        $checkoutSession = Mage::getSingleton('checkout/session');
                        if ($checkoutSession->getSagePayRewInst()) {
                            $this->getOnepage()->getQuote()
                                ->setUseRewardPoints(1)
                                ->setRewardInstance($checkoutSession->getSagePayRewInst());
                        }

                        if ($checkoutSession->getSagePayCustBalanceInst()) {
                            $this->getOnepage()->getQuote()
                                ->setUseCustomerBalance(1)
                                ->setCustomerBalanceInstance($checkoutSession->getSagePayCustBalanceInst());
                        }

                        //no invoice since it's PENDING
                        $this->_getSagePayServerSession()->setInvoicePayment(false);
                        Mage::register('sagepay_create_invoice', 0, true);

                        $sageserverpost = new Varien_Object($this->getRequest()->getPost());

                        if (Mage::helper('sagepaysuite')->surchargesModuleEnabled() == true) {
                            //save surcharge to server post for later use if not already saved
                            $sessionSurchargeAmount = Mage::getSingleton('sagepaysuite/session')->getSurcharge();
                            if (!is_null($sessionSurchargeAmount) && $sessionSurchargeAmount > 0) {
                                $sageserverpost->setData('Surcharge', $sessionSurchargeAmount);
                            }
                        }

                        Mage::register('sageserverpost', $sageserverpost);

                        //1.9.1 ssl fix
                        $customerId = null;
                        if ($this->getOnepage()->getQuote()->getId() == null) {
                            $rqQuoteId = Mage::app()->getRequest()->getParam('qid');
                            $quote = Mage::getModel('sales/quote')->loadActive($rqQuoteId);
                            $this->getOnepage()->setQuote($quote);
                            Mage::app()->getStore()->setCurrentCurrencyCode($quote->getQuoteCurrencyCode()); //Thanks to Ross Kinsman for his input on this.
                            $customerId = $this->getOnepage()->getQuote()->getData('customer_id');
                            if (!is_null($customerId)) {
                                $customer = Mage::getModel('customer/customer')->load($customerId);
                                if (!is_null($customer)) {
                                    Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                                }
                            }
                        }

                        //Saving TOKEN.
                        $this->_saveToken($dbtrn);

                        //save order
                        $sOrder = $this->_saveMagentoOrder();

                        if ($sOrder !== true) {
                            $sagePayServerSession->setFailStatus('An error occurred: ' . $sOrder);

                            /** The status indicates a failure of one state or another, so send the customer to orderFailed instead * */
                            $strRedirectPage = $this->_getFailedRedirectUrl();

                            //Mage::helper('sagepaysuite')->cancelTransaction($dbtrn);

                            $dbtrn->setStatus('MAGE_ERROR')
                                ->setStatusDetail('Could not save order: ' . $sOrder . $dbtrn->getStatusDetail())
                                ->save();

                            $this->_returnInvalid('Could not save order: ' . $sOrder);
                            return;
                        } else {
                            $orderId = Mage::registry('last_order_id');
                            $msOrderIds = $this->_getMsOrderIds();
                            if ($orderId || $msOrderIds) {
                                if (false !== $msOrderIds) {
                                    $aidis = array_keys($msOrderIds);
                                    $orderId = $aidis[0];
                                    #Mage::register('ms_parent_trn_id', $dbtrn->getId());
                                    $dbtrn->setOrderId($aidis[0])->save();

                                    unset($aidis[0]);
                                    $trns = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                        ->getCollection()
                                        ->getChilds($dbtrn->getId())
                                        ->load()->toArray();
                                    foreach ($aidis as $_order) {
                                        foreach ($trns['items'] as $ka => $_t) {
                                            Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                                ->load($_t['id'])->setOrderId($_order)->save();
                                            unset($trns['items'][$ka]);
                                            break;
                                        }
                                    }
                                }
                            }

                            $dbtrn
                                ->setOrderId($orderId)
                                ->save();

                            //set PENDING status
                            //$order = Mage::getModel('sales/order')->load($orderId);
                            //$order->setStatus("pending")->save();

                            $sagePayServerSession->setSuccessStatus($strDBStatus);
                        }

                        Mage::getSingleton('checkout/session')->setSagePayRewInst(null)->setSagePayCustBalanceInst(null);

                        if (Mage::registry('sagepay_last_quote_id')) {
                            $this->_returnOk(
                                array('inv' => (int)Mage::registry('sagepay_create_invoice'),
                                'cusid' => is_null($customerId) ? Mage::registry('sagepay_customer_id') : $customerId,
                                'qide' => Mage::registry('sagepay_last_quote_id'),
                                'incide' => Mage::registry('sagepay_last_real_order_id'),
                                'oide' => Mage::registry('sagepay_last_order_id'))
                            );
                        } else {
                            $this->_returnOk();
                        }

                        return;
                    } catch (Exception $e) {
                        $dbtrn->setStatus('MAGE_ERROR')
                            ->setStatusDetail($e->getMessage() . $dbtrn->getStatusDetail())
                            ->save();
                        Mage::logException($e);
                        Mage::log($e->getMessage());

                        Mage::dispatchEvent('sagepay_payment_failed', array('quote' => $this->getOnepage()->getQuote(), 'message' => $e->getMessage()));
                    }
                } else {
                    //Mage::helper('sagepaysuite')->cancelTransaction($this->_trn());

                    $dbtrn->setStatus('MAGE_ERROR')
                        ->setStatusDetail($strDBStatus . $dbtrn->getStatusDetail())
                        ->save();

                    $sagePayServerSession->setFailStatus($strDBStatus);
                    /** The status indicates a failure of one state or another, so send the customer to orderFailed instead * */
                    $this->_returnInvalid($strDBStatus);
                    return;
                }
            }
        }
    }

    protected function notifyActionWhenOrderPreSaved()
    {
        $request = $this->getRequest();
        $dbtrn = $this->_trn();
        $quote = null;

        /**
         * ORDER PRE SAVE WITH SERVER IS STILL IN BETA STATE
         */

        $dbtrn->setData("vps_protocol", $this->getRequest()->getPost('VPSProtocol'))
            ->setData("tx_type", $this->getRequest()->getPost('TxType'))
            ->setData("vendor_tx_code", $this->getRequest()->getPost('VendorTxCode'))
            ->setData("vps_tx_id", $this->getRequest()->getPost('VPSTxId'))
            ->setData("status", $this->getRequest()->getPost('Status'))
            ->setData("status_detail", $this->getRequest()->getPost('StatusDetail'))
            ->setData("tx_auth_no", $this->getRequest()->getPost('TxAuthNo'))
            ->setData("avscv2", $this->getRequest()->getPost('AVSCV2'))
            ->setData("address_result", $this->getRequest()->getPost('AddressResult'))
            ->setData("gift_aid", $this->getRequest()->getPost('GiftAid'))
            ->setData("cavv", $this->getRequest()->getPost('CAVV'))
            ->setData("card_type", $this->getRequest()->getPost('CardType'))
            ->setData("vps_signature", $this->getRequest()->getPost('VPSSignature'))
            ->setData("card_expiry_date", $this->getRequest()->getPost('ExpiryDate'))
            ->setPostcodeResult($this->getRequest()->getPost('PostCodeResult'))
            ->setData('cv2result', $this->getRequest()->getPost('CV2Result'))
            ->setThreedSecureStatus($this->getRequest()->getPost('3DSecureStatus'))
            ->setLastFourDigits($this->getRequest()->getPost('Last4Digits'))
            ->setRedFraudResponse($this->getRequest()->getPost('FraudResponse'))
            ->setSurchargeAmount($this->getRequest()->getPost('Surcharge'))
            ->setBankAuthCode($this->getRequest()->getPost('BankAuthCode'))
            ->setDeclineCode($this->getRequest()->getPost('DeclineCode'))
            ->setData("server_notify_arrived", true)
            ->save();

        //Handle ABORT
        $sageStatus = $request->getParam('Status');
        if ($sageStatus == 'ABORT') {
            Sage_Log::log("URL Notified: Aborted.", null, 'SagePaySuite_SERVER_RESPONSE.log');

            $this->_getSagePayServerSession()->setFailStatus($request->getParam('StatusDetail'));
            $dbtrn->setStatus($sageStatus)
                ->setStatusDetail($request->getParam('StatusDetail'))
                ->save();

            //cancel order
            $this->_cancelAndRecoverQuote($dbtrn);

            $this->_returnOkAbort();
            return;
        }

        if ($dbtrn->getId() && $dbtrn->getOrderId()) {
            Sage_Log::log("URL Notified: " . $dbtrn->getStatus(), null, 'SagePaySuite_SERVER_RESPONSE.log');

            if ($dbtrn->getStatus() == "OK" || $dbtrn->getStatus() == 'AUTHENTICATED' || $dbtrn->getStatus() == 'REGISTERED') {
                $order = Mage::getModel('sales/order')->load($dbtrn->getOrderId());
                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());

                //Saving TOKEN.
                $this->_saveToken($dbtrn);

                $createInvoice = 0;
                if ((string)$request->getParam('Status') == 'OK' && (string)$request->getParam('TxType') == 'PAYMENT') {
                    $this->_getSagePayServerSession()->setInvoicePayment(true);
                    Mage::register('sagepay_create_invoice', 1, true);
                    $createInvoice = 1;
                }

                if ($order->getId() && $quote->getId()) {
                    $getDataToSend = array('inv' => (int)$createInvoice,
                        'cusid' => $quote->getData('customer_id'),
                        'qide' => $quote->getId(),
                        'incide' => $order->getIncrementId(),
                        'oide' => $order->getId());
                }

                $this->_returnOk(isset($getDataToSend) ? $getDataToSend : array());
                return;
            }
        }

        //something went wrong
        $strStatus = $request->getParam('Status', '');
        $strDBStatus = $this->_getHRStatus($strStatus, '');

        $dbtrn->setStatus('MAGE_ERROR')
            ->setStatusDetail($strDBStatus . $dbtrn->getStatusDetail())
            ->save();

        $this->_getSagePayServerSession()->setFailStatus($strDBStatus);

        //cancel order
        $this->_cancelAndRecoverQuote($dbtrn);

        $this->_returnInvalid($strDBStatus);
        return;
    }

    protected function _cancelAndRecoverQuote($dbtrn)
    {
        if ($dbtrn->getOrderId()) {
            $order = Mage::getModel('sales/order')->load($dbtrn->getOrderId());
            if ($order->canCancel()) {
                try {
                    $order->cancel();
                    $order->setStatus("sagepaysuite_pending_cancel");
                    $order->save();

                    //recover quote
                    $quote = Mage::getModel('sales/quote')
                        ->load($order->getQuoteId());
                    if ($quote->getId()) {
                        $quote->setIsActive(1)
                            ->setReservedOrderId(NULL)
                            ->save();
                        Mage::getSingleton('checkout/session')->replaceQuote($quote);
                    }

                    //Unset data
                    Mage::getSingleton('checkout/session')->unsLastRealOrderId();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    public function redirectAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('sagepaysuite/checkout_servercallback')->toHtml());
    }

    public function failedAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('sagepaysuite/checkout_serverfail')->toHtml());
    }

    public function failureAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('sagepaysuite/checkout_serverfail')->toHtml());
    }

    public function successAction()
    {
        Mage::helper('sagepaysuite')->deleteQuote();
        $this->getResponse()->setBody($this->getLayout()->createBlock('sagepaysuite/checkout_serversuccess')->setMultiShipping($this->_isMultishippingCheckout())->toHtml());
    }

    public function abortredirectAction()
    {
        $this->_getSagePayServerSession()->setLastSavedTokenccid(null);
        $this->getResponse()->setBody($this->getLayout()->createBlock('sagepaysuite/checkout_serverfail')->toHtml());
    }

    protected function _getSagePayServerSession()
    {
        return Mage::getSingleton('sagepaysuite/session');
    }

    public function getOnepage()
    {
        return Mage:: getSingleton('checkout/type_onepage');
    }

    /**
     * Retrieve checkout model
     *
     * @return Mage_Checkout_Model_Type_Multishipping
     */
    public function getMultishipping()
    {
        return Mage:: getSingleton('checkout/type_multishipping');
    }

    protected function _getMsOrderIds()
    {
        $ids = Mage::getSingleton('core/session')->getOrderIds();
        if ($ids && is_array($ids)) {
            return $ids;
        }

        return false;
    }

    /**
     * Check if current quote is multishipping
     */
    protected function _isMultishippingCheckout()
    {
        return (bool)Mage:: getSingleton('checkout/session')->getQuote()->getIsMultiShipping();
    }

    protected function _getMsState()
    {
        return Mage:: getSingleton('checkout/type_multishipping_state');
    }

    private function _saveMagentoOrder()
    {
        try {
            if ($this->_isMultishippingCheckout()) {
                $this->getOnepage()->getQuote()->collectTotals();

                $this->getMultishipping()->createOrders();
                $this->_getMsState()->setActiveStep(
                    Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS
                );
                $this->_getMsState()->setCompleteStep(
                    Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW
                );
                $this->getMultishipping()->getCheckoutSession()->clear();
                $this->getMultishipping()->getCheckoutSession()->setDisplaySuccess(true);

                return true;
            } else {
                $this->getOnepage()->getQuote()->collectTotals();

                Mage::helper('sagepaysuite')->ignoreAddressValidation($this->getOnepage()->getQuote());

                //Sage_Log::log("Notified: Before save.", null, 'SagePaySuite_SERVER_RESPONSE.log');
                $this->getOnepage()->saveOrder();
                //Sage_Log::log("Notified: After save.", null, 'SagePaySuite_SERVER_RESPONSE.log');

                Mage::register('last_order_id', Mage::getSingleton('checkout/session')->getLastOrderId());

                Mage::helper('sagepaysuite')->deleteQuote();

                return true;
            }
        } catch (Exception $e) {
            //Mage::helper('sagepaysuite')->cancelTransaction($this->_trn());

            Mage::log($e->getMessage());
            Mage::logException($e);

            Mage::dispatchEvent('sagepay_payment_failed', array('quote' => $this->getOnepage()->getQuote(), 'message' => $e->getMessage()));

            $this->getOnepage()->getQuote()->save();

            $message = "MAGENTO ERROR: " . $e->getMessage() . ". ";
            $trn = $this->_trn();
            $trn
                ->setStatus('MAGE_ERROR')
                ->setStatusDetail($message . $trn->getStatusDetail())
                ->save();

            return $e->getMessage();
        }
    }

}