<?php

/**
 * DIRECT main model
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Model_SagePayDirectPro extends Ebizmarts_SagePaySuite_Model_Api_Payment
{

    protected $_code = 'sagepaydirectpro';
    protected $_formBlockType = 'sagepaysuite/form_sagePayDirectPro';
    protected $_infoBlockType = 'sagepaysuite/info_sagePayDirectPro';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;

    public function registerToken($payment)
    {
        if (true === $this->getTokenModel()->isEnabled()) {
            $result = $this->getTokenModel()->registerCard($this->getNewTokenCardArray($payment), true);
            if ($result['Status'] != 'OK') {
                Mage::throwException(Mage::helper('sagepaysuite')->__($result['StatusDetail']));
            }

            return $result;
        }
    }

    /**
     * COMPLETE PayPal transaction.
     *
     * @param array $request
     * @param $quote
     * @return array|string
     */
    public function completePayPalTransaction(array $request, $quote)
    {

        $vpsTxId = $request['VPSTxId'];

        $pdata = array();
        $pdata ['VPSProtocol'] = $this->getVpsProtocolVersion();
        $pdata ['TxType'] = parent::REQUEST_TYPE_COMPLETE;
        $pdata ['VPSTxId'] = $vpsTxId;

        $trnCurrency = (string)$this->getConfigData('trncurrency');

        if ($trnCurrency == 'store') {
            $pdata ['Amount'] = $this->formatAmount($quote->getGrandTotal(), $quote->getCurrencyCode());
        } else if ($trnCurrency == 'switcher') {
            $pdata ['Amount'] = $this->formatAmount($quote->getGrandTotal(), Mage::app()->getStore()->getCurrentCurrencyCode());
        } else {
            $pdata ['Amount'] = $this->formatAmount($quote->getBaseGrandTotal(), $quote->getQuoteCurrencyCode());
        }

        if ($request['Status'] == parent::RESPONSE_CODE_PAYPAL_OK) {
            $pdata ['Accept'] = 'YES';
        } else {
            $pdata ['Accept'] = 'NO';
        }

        //Get config data from PayPal model
        $mode = Mage::getModel('sagepaysuite/sagePayPayPal')->getConfigData('mode');

        //POST COMPLETE request
        $_res = $this->requestPost($this->getUrl('paypalcompletion', false, null, $mode), $pdata);

        $vtx = $this->getSageSuiteSession()->getLastVendorTxCode();
        $saveData = Mage::helper('sagepaysuite')->arrayKeysToUnderscore($_res);

        //REGISTERED - For AUTHENTICATE transactions.
        if ($_res['Status'] == 'OK' or $_res['Status'] == 'REGISTERED') {
            Mage::getModel('sagepaysuite2/sagepaysuite_paypaltransaction')
                ->loadByVendorTxCode($vtx)
                ->addData($saveData)
                ->setVpsTxId($_res['VPSTxId'])
                ->setTrndate(Mage::getModel('sagepaysuite/api_payment')->getDate())
                ->save();

            $_trn = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                ->loadByVendorTxCode($vtx)
                ->addData($saveData);

            if (isset($_res['PostCodeResult'])) {
                $_trn->setPostcodeResult($_res['PostCodeResult']);
            }

            if (isset($_res['VPSTxId'])) {
                $_trn->setVpsTxId($_res['VPSTxId']);
            }

            if (isset($_res['3DSecureStatus'])) {
                $_trn->setThreedSecureStatus($_res['3DSecureStatus']);
            }

            $_trn->save();

            if (strtoupper($_trn->getTxType()) == 'PAYMENT') {
                $this->getSageSuiteSession()->setInvoicePayment(true);
            }
        } else {
            $dbtrn = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                ->loadByVendorTxCode($vtx);

            $dbtrn->setStatus('MAGE_ERROR')
                ->setStatusDetail(Mage::helper('sagepaysuite')->__($_res['StatusDetail']) . $dbtrn->getStatusDetail())
                ->save();

            Mage::throwException($_res['StatusDetail']);
        }

        Sage_Log::log($_res);

        return $_res;
    }

    /**
     * @param Varien_Object $payment
     * @param $amount
     * @return $this
     */
    public function directRegisterTransaction(Varien_Object $payment, $amount)
    {
        #Process invoice
        if (!$payment->getRealCapture()) {
            return $this->captureInvoice($payment, $amount);
        }

        /**
         * Token Transaction
         */
        if (true === $this->_tokenPresent()) {
            $_info = new Varien_Object(array('payment' => $payment));
            $result = $this->getTokenModel()->tokenTransaction($_info);

            $this->invalidStatusHalt($result);

            if (strtoupper($this->getConfigData('payment_action')) == self::REQUEST_TYPE_PAYMENT) {
                $this->getSageSuiteSession()->setInvoicePayment(true);
            }

            $this->setSagePayResult($result);

            if ($result['Status'] == self::RESPONSE_CODE_3DAUTH) {
                $payment->getOrder()->setIsThreedWaiting(true);

                $this->getSageSuiteSession()->setSecure3dMethod('directCallBack3D');

                Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                    ->loadByVendorTxCode($payment->getVendorTxCode())
                    ->setVendorTxCode($payment->getVendorTxCode())
                    ->setMd($result['MD'])
                    ->setPareq($result['PAReq'])
                    ->setAcsurl($result['ACSURL'])
                    ->save();

                $this->getSageSuiteSession()
                    ->setAcsurl($result['ACSURL'])
                    ->setEmede($result['MD'])
                    ->setPareq($result['PAReq']);
                $this->setVndor3DTxCode($payment->getVendorTxCode());
            }

            return $this;
        }

        /**
         * Token Transaction
         */
        if ($this->_getIsAdmin() && (int)$this->_getAdminQuote()->getCustomerId() === 0) {
            //$cs = Mage::getModel('customer/customer')->setWebsiteId($this->_getAdminQuote()->getStoreId())->loadByEmail($this->_getAdminQuote()->getCustomerEmail());
            $cs = Mage::helper('sagepaysuite')->existsCustomerForEmail($this->_getAdminQuote()->getCustomerEmail(), $this->_getAdminQuote()->getStore()->getWebsite()->getId());
            if ($cs) {
                Mage::throwException($this->_SageHelper()->__('Customer already exists.'));
            }
        }

        if ($this->_getIsAdmin()) {
            $payment->setRequestVendor($this->getConfigData('vendor', $this->_getAdminQuote()->getStoreId()));
        }

        if ($this->getSageSuiteSession()->getSecure3d()) {
            $this->directCallBack3D(
                $payment, $this->getSageSuiteSession()->getPares(), $this->getSageSuiteSession()->getEmede()
            );
            $this->getSageSuiteSession()->setSecure3d(null);
            return $this;
        }

        $this->getSageSuiteSession()->setMd(null)
            ->setAcsurl(null)
            ->setPareq(null);

        $error = false;

        $payment->setAnetTransType(strtoupper($this->getConfigData('payment_action')));

        $payment->setAmount($amount);

        $request = $this->_buildRequest($payment);

        Mage::dispatchEvent('sagepaysuite_direct_request_post_before', array('request' => $request, 'payment' => $this));

        $result = $this->_postRequest($request);

        $dbTrn = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
            ->loadByVendorTxCode($request->getData('VendorTxCode'))
            ->setVendorTxCode($request->getData('VendorTxCode'))
            ->setCustomerContactInfo($request->getData('ContactNumber'))
            ->setCustomerCcHolderName($request->getData('CustomerName'))
            ->setVendorname($request->getData('Vendor'))
            ->setTxType($request->getData('InternalTxtype'))
            ->setTrnCurrency($request->getCurrency())
            ->setIntegration('direct')
            ->setCardType($request->getData('CardType'))
            ->setCardExpiryDate($request->getData('ExpiryDate'))
            ->setLastFourDigits(substr($request->getData('CardNumber'), -4))
            ->setToken($request->getData('Token'))
            ->setNickname(filter_var($request->getData('Nickname'), FILTER_SANITIZE_STRING))
            ->setTrnCurrency($request->getData('Currency'))
            ->setMode($this->getConfigData('mode'))
            ->setTrndate($this->getDate())
            ->setStatus($result->getResponseStatus())
            ->setStatusDetail($result->getResponseStatusDetail())
            ->save();

        switch ($result->getResponseStatus()) {
            case 'FAIL':
                $error = $result->getResponseStatusDetail();
                $payment
                    ->setStatus('FAIL')
                    ->setCcTransId($result->getVPSTxId())
                    ->setLastTransId($result->getVPSTxId())
                    ->setCcApproval('FAIL')
                    ->setAddressResult($result->getAddressResult())
                    ->setPostcodeResult($result->getPostCodeResult())
                    ->setCv2Result($result->getCV2Result())
                    ->setCcCidStatus($result->getTxAuthNo())
                    ->setSecurityKey($result->getSecurityKey())
                    ->setAdditionalData($result->getResponseStatusDetail());
                break;
            case 'FAIL_NOMAIL':
                $error = $result->getResponseStatusDetail();
                break;
            case self::RESPONSE_CODE_APPROVED:
            case self::RESPONSE_CODE_REGISTERED:

                $payment->setSagePayResult($result);

                $payment
                    ->setStatus(self::RESPONSE_CODE_APPROVED)
                    ->setCcTransId($result->getVPSTxId())
                    ->setLastTransId($result->getVPSTxId())
                    ->setCcApproval(self::RESPONSE_CODE_APPROVED)
                    ->setAddressResult($result->getAddressResult())
                    ->setPostcodeResult($result->getPostCodeResult())
                    ->setCv2Result($result->getCV2Result())
                    ->setCcCidStatus($result->getTxAuthNo())
                    ->setSecurityKey($result->getSecurityKey());

                if (strtoupper($this->getConfigData('payment_action')) == self::REQUEST_TYPE_PAYMENT) {
                    $this->getSageSuiteSession()->setInvoicePayment(true);
                }
                break;
            case self::RESPONSE_CODE_PAYPAL_REDIRECT:

                $payment->setSagePayResult($result);

                break;
            case self::RESPONSE_CODE_3DAUTH:

                $payment->setSagePayResult($result);

                $payment->getOrder()->setIsThreedWaiting(true);

                $this->getSageSuiteSession()->setSecure3dMethod('directCallBack3D');

                $this->getSageSuiteSession()
                    ->setAcsurl($result->getData('a_cs_ur_l'))
                    ->setEmede($result->getData('m_d'))
                    ->setPareq($result->getData('p_areq'));

                $dbTrn->setMd($result->getData('m_d'))
                    ->setPareq($result->getData('p_areq'))
                    ->setAcsurl($result->getData('a_cs_ur_l'))
                    ->save();

                $this->setVndor3DTxCode($payment->getVendorTxCode());

                break;
            default:
                if ($result->getResponseStatusDetail()) {
                    $error = $this->returnHumanFriendlyErrorMessage($result);
                    $error .= $result->getResponseStatusDetail();
                } else {
                    $error = $this->_SageHelper()->__('Error in capturing the payment');
                }
                break;
        }

        $this->showErrorMessage($error);

        return $this;
    }

    protected function _getPayPalCallbackUrl()
    {
        return Mage::getModel('core/url')->addSessionParam()->getUrl('sgps/paypalexpress/callback', array('_secure' => true));
    }

    public function getPayPalMode()
    {
        return Mage::getStoreConfig('payment/sagepaypaypal/mode', Mage::app()->getStore()->getId());
    }

    protected function _getPayPalRequest()
    {

        $quoteObj = $this->_getQuote();
        $paymentAction = strtoupper(Mage::getStoreConfig('payment/sagepaypaypal/payment_action', Mage::app()->getStore()->getId()));
        $paypalVendor = (string)Mage::getStoreConfig('payment/sagepaypaypal/vendor', Mage::app()->getStore()->getId());
        $payment = $this->_getBuildPaymentObject($quoteObj);
        $rq = Mage::app()->getRequest()->getParam('quick');

        if ($rq) { //Button checkout
            $_grq = Mage::getModel('sagepaysuite/sagepaysuite_request');

            //Basket if force estimate enabled
            if (Mage::getStoreConfig('payment/sagepaypaypal/shipping_method_selection') == 'estimate') {
                $forceXml = false;
                if (Mage::getStoreConfig('payment/sagepaypaypal/force_basketxml_paypal') == TRUE) {
                    $forceXml = true;
                }

                $basket = Mage::helper('sagepaysuite')->getSagePayBasket($quoteObj, $forceXml);
                if (!empty($basket)) {
                    if ($basket[0] == "<") {
                        $_grq->setBasketXML($basket);
                    } else {
                        $_grq->setBasket($basket);
                    }
                }
            }
        } else { //In checkout method
            $_grq = $this->_buildRequest($payment);
        }

        $request = $_grq
            ->setVPSProtocol((string)$this->getVpsProtocolVersion($this->getPayPalMode()))
            ->setTxType($paymentAction)
            ->setVendor(((strlen($paypalVendor) > 1) ? $paypalVendor : $this->getConfigData('vendor')))
            ->setVendorTxCode($this->_getTrnVendorTxCode())
            ->setCardType(self::CARD_TYPE_PAYPAL)
            ->setPayPalCallbackURL($this->_getPayPalCallbackUrl())
            ->setCustomerEMail($this->getCustomerLoggedEmail())
            ->setApplyAVSCV2('0')
            ->setClientIPAddress($this->getClientIp())
            ->setApply3DSecure('0')
            ->setGiftAidPayment('0')
            ->setAccountType('E')
            ->setReferrerID($this->getConfigData('referrer_id'))
            ->setInternalTxtype($paymentAction)
            ->setMode($this->getPayPalMode());

        $this->_setRequestCurrencyAmount($request, $quoteObj);

        if (strlen($request->getDescription()) === 1 || !$request->getDescription()) {
            $request->setDescription('PayPal transaction.');
        }

        //billing agreement
        if (Mage::getStoreConfigFlag('payment/sagepaypaypal/billing_agreement') == true) {
            $request->setBillingAgreement(1);
        }

        return $request;
    }

    /**
     * Post PayPal transaction to SagePay.
     */
    public function registerPayPalTransaction()
    {

        $_req = $this->_getPayPalRequest();
        $_res = $this->_postRequest($_req, false);

        $trn = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
            ->loadByVendorTxCode($_req->getData('VendorTxCode'))
            ->setVendorTxCode($_req->getData('VendorTxCode'))
            ->setVpsProtocol($_res->getData('VPSProtocol'))
            ->setVendorname($_req->getData('Vendor'))
            ->setMode($this->getPayPalMode())
            ->setTxType($_req->getData('InternalTxtype'))
            ->setTrnCurrency($_req->getCurrency())
            ->setIntegration('direct')
            ->setCardType($_req->getData('CardType'))
            ->setStatus($_res->getResponseStatus())
            ->setStatusDetail($_res->getResponseStatusDetail())
            ->setVpsTxId($_res->getData('VPSTxId'))
            ->setTrnCurrency($_req->getData('Currency'))
            ->setTrnAmount($_req->getData('Amount'))
            ->setTrndate($this->getDate());
        $trn->save();

        if ($_res->getResponseStatus() != parent::RESPONSE_CODE_PAYPAL_REDIRECT) {
            Mage::throwException($_res->getResponseStatusDetail());
        }

        $this->getSageSuiteSession()->setLastVendorTxCode($_req->getData('VendorTxCode'));

        return $_res;
    }

    public function directCallBack3D(Varien_Object $payment, $PARes, $MD)
    {
        $error = '';

        $request = $this->_buildRequest3D($PARes, $MD);
        Sage_Log::log($request, null, '3D-Request.log');
        $result = $this->_postRequest($request, true);
        Sage_Log::log($result, null, '3D-Result.log');

        if (Mage::helper('sagepaysuite')->surchargesModuleEnabled() == true) {
            //save surcharge to server post for later use
            $sessionSurchargeAmount = Mage::getSingleton('sagepaysuite/session')->getSurcharge();
            if (!is_null($sessionSurchargeAmount) && $sessionSurchargeAmount > 0) {
                $result->setData('Surcharge', $sessionSurchargeAmount);
            }
        }

        Mage::register('sageserverpost', $result);

        if ($result->getResponseStatus() == self::RESPONSE_CODE_APPROVED ||
            $result->getResponseStatus() == 'AUTHENTICATED' ||
            $result->getResponseStatus() == self::RESPONSE_CODE_REGISTERED
        ) {
            if (strtoupper($this->getConfigData('payment_action')) == self::REQUEST_TYPE_PAYMENT) {
                $this->getSageSuiteSession()->setInvoicePayment(true);
            }

            $_transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                ->loadByVendorTxCode($this->getSageSuiteSession()->getLastVendorTxCode())
                ->setVpsProtocol($result->getData('VPSProtocol'))
                ->setSecurityKey($result->getData('SecurityKey'))
                ->setStatus($result->getData('Status'))
                ->setStatusDetail($result->getData('StatusDetail'))
                ->setVpsTxId($result->getData('VPSTxId'))
                ->setTxAuthNo($result->getData('TxAuthNo'))
                ->setAvscv2($result->getData('AVSCV2'))
                ->setPostcodeResult($result->getData('PostCodeResult'))
                ->setAddressResult($result->getData('AddressResult'))
                ->setCv2result($result->getData('CV2Result'))
                ->setThreedSecureStatus($result->getData('3DSecureStatus'))
                ->setCavv($result->getData('CAVV'))
                ->setRedFraudResponse($result->getData('FraudResponse'))
                ->setSurchargeAmount($result->getData('Surcharge'))
                ->setBankAuthCode($result->getData('BankAuthCode'))
                ->setDeclineCode($result->getData('DeclineCode'))
                ->save();

            $onePage = Mage::getSingleton('checkout/type_onepage');
            $quote = $onePage->getQuote();
            $quote->collectTotals();

            Mage::helper('sagepaysuite')->ignoreAddressValidation($quote);

            try {
                $onePage->saveOrder();
            } catch (Exception $ex) {

                try {
                    Sage_Log::log("Voiding payment. Error saving the order: " . $ex->getMessage(), null, 'saveOrder.log');
                    Mage::getModel('sagepaysuite/api_payment')->voidPayment($_transaction);
                    Mage::getSingleton('checkout/session')->addError(Mage::helper('checkout')->__($ex->getMessage()));
                } catch(Exception $e) {
                    Sage_Log::log("Error voiding payment. Check your orphan transaction grid." . $e->getMessage(), null, 'saveOrder.log');
                    Mage::getSingleton('checkout/session')->addError(Mage::helper('checkout')->__("There was an error while saving the order. Please contact the administrator to confirm if the payment was taken."));
                }

                return;
            }

            //Saving TOKEN after 3D response.
            if ($result->getData('Token')) {
                $tokenData = array(
                    'Token' => $result->getData('Token'),
                    'Status' => $result->getData('Status'),
                    'Vendor' => $_transaction->getVendorname(),
                    'CardType' => $_transaction->getCardType(),
                    'ExpiryDate' => $result->getData('ExpiryDate'),
                    'StatusDetail' => $result->getData('StatusDetail'),
                    'Protocol' => 'direct',
                    'CardNumber' => $_transaction->getLastFourDigits(),
                    'Nickname' => filter_var($_transaction->getNickname(), FILTER_SANITIZE_STRING)
                );
                Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData);
            }

            $payment->setSagePayResult($result);

            $payment->setStatus(self::STATUS_APPROVED)
                ->setCcTransId($result->getVPSTxId())
                ->setCcApproval(self::RESPONSE_CODE_APPROVED)
                ->setLastTransId($result->getVPSTxId())
                ->setAddressResult($result->getAddressResult())
                ->setPostcodeResult($result->getPostCodeResult())
                ->setCv2Result($result->getCV2Result())
                ->setSecurityKey($result->getSecurityKey())
                ->setCcCidStatus($result->getTxAuthNo())
                ->setAdditionalData($result->getResponseStatusDetail());
            $payment->save();
        } else {
            //Update status if 3d failed
            Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                ->loadByVendorTxCode($this->getSageSuiteSession()->getLastVendorTxCode())
                ->setStatus($result->getResponseStatus())
                ->setStatusDetail($result->getResponseStatusDetail())
                ->setVpsTxId($result->getVpsTxId())
                ->setSecurityKey($result->getSecurityKey())
                ->setPares(null)//Resetting data so we dont get "5036 : transaction not found" error for repeated calls to sagepay on 3d callback.
                ->setMd(null)//Resetting data so we dont get "5036 : transaction not found" error for repeated calls to sagepay on 3d callback.
                ->setPareq(null)
                ->save();

            if ($result->getResponseStatusDetail()) {
                if ($result->getResponseStatus() == self::RESPONSE_CODE_NOTAUTHED) {
                    $error = $this->_sageHelper()->__('Your credit card can not be authenticated: ');
                } else if ($result->getResponseStatus() == self::RESPONSE_CODE_REJECTED) {
                    $error = $this->_sageHelper()->__('Your credit card was rejected: ');
                }

                $error .= $result->getResponseStatusDetail();
            } else {
                $error = $this->_sageHelper()->__('Error in capturing the payment');
            }
        }

        if (!empty($error)) {
            Mage::throwException($error);
        }

        return $this;
    }

    protected function _buildRequest3D($PARes, $MD)
    {
        return $this->_getRequest()
            ->setMD($MD)
            ->setPARes($PARes);
    }

    /**
     * Register DIRECT transation.
     *
     * @param array $params
     * @param bool $onlyToken
     * @param float $macOrder MAC single order
     */
    public function registerTransaction($params = null, $onlyToken = false, $macOrder = null)
    {

        $quoteObj = $this->_getQuote();
        $quoteObj2 = $this->getQuoteDb($quoteObj);

        if (is_null($macOrder)) {
            $amount = $this->formatAmount($quoteObj2->getGrandTotal(), $quoteObj2->getCurrencyCode());
        } else {
            $amount = $this->formatAmount($macOrder->getGrandTotal(), $macOrder->getCurrencyCode());

            $baseAmount = $this->formatAmount($macOrder->getBaseGrandTotal(), $macOrder->getQuoteCurrencyCode());

            $quoteObj->setMacAmount($amount);
            $quoteObj->setBaseMacAmount($baseAmount);
        }

        if (!is_null($params)) {
            $payment = $this->_getBuildPaymentObject($quoteObj2, $params);
        } else {
            $payment = $this->_getBuildPaymentObject($quoteObj2);
        }

        if ($onlyToken) {
            return $this->registerToken($payment);
        }

        $_rs = $this->directRegisterTransaction($payment, $amount);
        $_req = $payment->getSagePayResult()->getRequest();
        $_res = $payment->getSagePayResult();

        #Last order vendortxcode
        $this->getSageSuiteSession()->setLastVendorTxCode($_req->getData('VendorTxCode'));
        if ($this->isMsOnOverview()) {
            $tx = array();
            $regTxCodes = Mage::registry('sagepaysuite_ms_txcodes');
            if ($regTxCodes) {
                $tx += $regTxCodes;
                Mage::unregister('sagepaysuite_ms_txcodes');
            }

            $tx [] = $_req->getData('VendorTxCode');
            Mage::register('sagepaysuite_ms_txcodes', $tx);
        }

        Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
            ->loadByVendorTxCode($_req->getData('VendorTxCode'))
            ->setVendorTxCode($_req->getData('VendorTxCode'))
            ->setToken($_req->getData('Token'))
            ->setTrnCurrency($_req->getData('Currency'))
            ->setTrnAmount($_req->getData('Amount'))
            ->setTxType($_req->getData('Txtype'))
            ->setMode($this->getConfigData('mode'))
            ->setVendorname($_req->getData('Vendor'))
            ->setVpsProtocol($_res->getData('VPSProtocol'))
            ->setSecurityKey($_res->getData('SecurityKey'))
            ->setVpsTxId($_res->getData('VPSTxId'))
            ->setTxAuthNo($_res->getData('TxAuthNo'))
            ->setAvscv2($_res->getData('AVSCV2'))
            ->setPostcodeResult($_res->getData('PostCodeResult'))
            ->setAddressResult($_res->getData('AddressResult'))
            ->setCv2result($_res->getData('CV2Result'))
            ->setThreedSecureStatus($_res->getData('3DSecureStatus'))
            ->setCavv($_res->getData('CAVV'))
            ->setRedFraudResponse($_res->getData('FraudResponse'))
            ->setSurchargeAmount($_res->getData('Surcharge'))
            ->setBankAuthCode($_res->getData('BankAuthCode'))
            ->setDeclineCode($_res->getData('DeclineCode'))
            ->save();

        return $_res;
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        $info = $this->getInfoInstance();

        $tokenCardId = (int)$info->getSagepayTokenCcId();

        if ($tokenCardId) {
            $valid = $this->getTokenModel()->isTokenValid($tokenCardId);
            if (false === $valid) {
                Mage::throwException($this->_getHelper()->__('Token card not valid. %s', $tokenCardId));
            }

            return $this;
        }

        if (Mage::getSingleton('sagepaysuite/session')->getSagepaypaypalRqpost() || !is_null(Mage::registry('Ebizmarts_SagePaySuite_Model_Api_Payment::recoverTransaction'))) {
            return $this;
        }

        /*
         * calling parent validate function
         */

        $info = $this->getInfoInstance();
        $errorMsg = false;
        if ($this->_code == "sagepaydirectpro") {
            $availableTypes = explode(',', Mage::getStoreConfig('payment/sagepaydirectpro/cctypesSagePayDirectPro'));
        } else if ($this->_code == "sagepaydirectpro_moto") {
            $availableTypes = explode(',', Mage::getStoreConfig('payment/sagepaydirectpro_moto/cctypesSagePayDirectPro'));
        }

        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';

        if ($ccNumber) {
            // ccNumber is not present after 3Dcallback, in this case we supose cc is already checked

            if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
                $errorCode = 'ccsave_expiration,ccsave_expiration_yr';
                $errorMsg = $this->_getHelper()->__('Incorrect credit card expiration date');
            }

            if (in_array($info->getCcType(), $availableTypes)) {
                if ($this->validateCcNum($ccNumber)
                    // Other credit card type number validation
                    || ($this->OtherCcType($info->getCcType()) && $this->validateCcNumOther($ccNumber))
                ) {
                    $ccType = 'OT';
                    $ccTypeRegExpList = array(
                        'VISA' => '/^4[0-9]{12}([0-9]{3})?$/', // Visa
                        'MC' => '/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/', // Master Card
                        'AMEX' => '/^3[47][0-9]{13}$/'//,        // American Express
                        //'DI' => '/^6011[0-9]{12}$/'          // Discovery
                    );

                    foreach ($ccTypeRegExpList as $ccTypeMatch => $ccTypeRegExp) {
                        if (preg_match($ccTypeRegExp, $ccNumber)) {
                            if ($info->getCcType() == "MCDEBIT" && $ccTypeMatch == "MC") {
                                $ccType = "MCDEBIT";
                            } else {
                                $ccType = $ccTypeMatch;
                            }

                            break;
                        }
                    }

                    if (!$this->OtherCcType($info->getCcType()) && $ccType != $info->getCcType()) {
                        $errorCode = 'ccsave_cc_type,ccsave_cc_number';
                        $errorMsg = $this->_getHelper()->__("Credit card number mismatch with credit card type");
                    }
                } else {
                    $errorCode = 'ccsave_cc_number';
                    $errorMsg = $this->_getHelper()->__('Invalid Credit Card Number');
                }
            } else {
                $errorCode = 'ccsave_cc_type';
                $errorMsg = $this->_getHelper()->__('Credit card type is not allowed for this payment method');
            }
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;

        /*
         * calling parent validate function

          return parent::validate(); */
    }

    public function OtherCcType($type)
    {
        return $type == 'OT' || $type == 'SOLO' || $type == 'DELTA' || $type == 'UKE' || $type == 'MAESTRO' || $type == 'SWITCH' || $type == 'LASER' || $type == 'JCB' || $type == 'DC';
    }

    protected function _buildRequest(Varien_Object $payment)
    {

        $order = $payment->getOrder();

        $vendorTxCode = $this->_getTrnVendorTxCode();

        $payment->setVendorTxCode($vendorTxCode);

        $_mode = ($payment->getRequestMode() ? $payment->getRequestMode() : $this->getConfigData('mode'));

        $request = Mage::getModel('sagepaysuite/sagepaysuite_request')
            ->setVPSProtocol($this->getVpsProtocolVersion($_mode))
            ->setMode($_mode)
            ->setReferrerID($this->getConfigData('referrer_id'))
            ->setTxType($payment->getAnetTransType())
            ->setInternalTxtype($payment->getAnetTransType())# Just for storing in transactions table
            ->setVendor(($payment->getRequestVendor() ? $payment->getRequestVendor() : $this->getConfigData('vendor')))
            ->setVendorTxCode($vendorTxCode)
            ->setDescription($this->ss(($payment->getCcOwner() ? $payment->getCcOwner() : '.'), 100))
            ->setClientIPAddress($this->getClientIp());


        //basket
        $this->setBasketXmlToRequest($order, $request);

        if ($request->getToken()) {
            $request->setData('store_token', 1);
        }

        if ($this->_getIsAdminOrder()) {
            $request->setAccountType('M');
        }

        $this->validateAmountOrdered($payment, $request);

        if (!empty($order)) {
            //set billing address
            $billing = $order->getBillingAddress();

            $this->setBillingDataOnRequest($billing, $request);

            //set shipping address
            if (!$order->getIsVirtual()) {
                $shipping = $order->getShippingAddress();

                $this->setShippingDataOnRequest($shipping, $request);
            } else {
                #If the cart only has virtual products, I need to put an shipping address to Sage Pay.
                #Then the billing address will be the shipping address to
                $request->setDeliveryAddress(
                    $billing->getStreet(1) . ' ' . $billing->getCity() . ' ' .
                    $billing->getRegion() . ' ' . $billing->getCountry()
                )
                    ->setDeliverySurname($this->ss($billing->getLastname(), 20))
                    ->setDeliveryFirstnames($this->ss($billing->getFirstname(), 20))
                    ->setDeliveryPostCode($this->sanitizePostcode($this->ss($billing->getPostcode(), 10)))
                    ->setDeliveryAddress1($this->ss($billing->getStreet(1), 100))
                    ->setDeliveryAddress2($this->ss($billing->getStreet(2), 100))
                    ->setDeliveryCity($billing->getCity())
                    ->setDeliveryCountry(trim($billing->getCountry()));

                //shipping state
                $shippingState = $billing->getRegionCode();
                if (!is_null($shippingState) && strlen($shippingState) > 2) {
                    $shippingState = substr($shippingState, 0, 2);
                }

                if (!empty($shippingState)) {
                    $request->setDeliveryState($shippingState);
                }
            }
        }

        $this->setCreditCardDataOnRequest($payment, $request);

        $this->setThreedSecureDataOnRequest($request);

        if ($request->getAccountType() != 'M' && $this->_forceCardChecking($payment->getCcType()) === true) {
            $request->setApply3DSecure('1');
        }

        $request->setData('ApplyAVSCV2', $this->getConfigData('avscv2'));

        if ($payment->getCcGiftaid() == 1 || $payment->getCcGiftaid() == 'on') {
            $request->setData('GiftAidPayment', 1);
        }

        if (!$request->getDeliveryPostCode()) {
            $request->setDeliveryPostCode('000');
        }

        if (!$request->getBillingPostCode()) {
            $request->setBillingPostCode('000');
        }

        //Set to CreateToken if needed
        if ($this->_createToken() OR $payment->getRemembertoken()) {
            if (!$request->setCreateToken(1, $payment->getCcNumber(), $request->getExpiryDate(), $payment->getCcType())) {
                $message = Mage::helper('sagepaysuite')->__('Credit card could not be saved for future use. You already have this card attached to your account or you have reached your account\'s maximum card storage capacity.');
                Mage::getSingleton('core/session')->addWarning($message);
            }
        }

        $request->setWebsite(Mage::app()->getStore()->getWebsite()->getName());

        $customerXML = $this->getCustomerXml($this->_getQuote());
        if (!is_null($customerXML)) {
            $request->setCustomerXML($customerXML);
        }

        //Skip PostCode and Address Validation for overseas orders
        if ((int)Mage::getStoreConfig('payment/sagepaysuite/apply_AVSCV2') === 1) {
            if ($this->_SageHelper()->isOverseasOrder($billing->getCountry())) {
                $request->setData('ApplyAVSCV2', 2);
            }
        }

        return $request;
    }

    /**
     * Force 3D secure checking based on card rule
     */
    protected function _forceCardChecking($ccType = null)
    {
        $config = $this->getConfigData('force_threed_cards');

        if (is_null($ccType) || strlen($config) === 0) {
            return false;
        }

        $config = explode(',', $config);
        if (in_array($ccType, $config)) {
            return true;
        }

        return false;
    }

    /**
     * @param Varien_Object $request
     * @param bool $callback3D
     * @return false|Mage_Core_Model_Abstract
     */
    protected function _postRequest(Varien_Object $request, $callback3D = false)
    {

        $result = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $mode = $this->getBuildRequestMode($request);

        $uri = $this->getUrl('post', $callback3D, null, $mode);

        try {
            $response = $this->requestPost($uri, $request->getData());
        } catch (Exception $e) {
            $result->setResponseCode(-1)
                ->setResponseReasonCode($e->getCode())
                ->setResponseReasonText($e->getMessage());

            Mage::throwException(
                $this->_SageHelper()->__('Gateway request error: %s', $e->getMessage())
            );
        }

        $r = $response;


        $result->setRequest($request);

        try {
            if (empty($r) OR !isset($r['Status'])) {
                $msg = $this->_SageHelper()->__('Sage Pay is not available at this time. Please try again later.');
                Sage_Log::log($msg, 1);
                $result
                    ->setResponseStatus('ERROR')
                    ->setResponseStatusDetail($msg);
                return $result;
            }

            $this->setNotMandatoryFields($r, $result);

            switch ($r['Status']) {
                case 'FAIL':
                    $params['order'] = Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
                    $params['error'] = Mage::helper('sagepaysuite')->__($r['StatusDetail']);

                    $result->setResponseStatus($r['Status'])
                        ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($r['StatusDetail']))
                        ->setVPSTxID(1)
                        ->setSecurityKey(1)
                        ->setTxAuthNo(1)
                        ->setAVSCV2(1)
                        ->setAddressResult(1)
                        ->setPostCodeResult(1)
                        ->setCV2Result(1)
                        ->setTrnSecuritykey(1);
                    return $result;
                    break;
                case 'FAIL_NOMAIL':
                    Mage::throwException($this->_SageHelper()->__($r['StatusDetail']));
                    break;
                case parent::RESPONSE_CODE_INVALID:
                    Mage::throwException($this->_SageHelper()->__('INVALID. %s', Mage::helper('sagepaysuite')->__($r['StatusDetail'])));
                    break;
                case parent::RESPONSE_CODE_MALFORMED:
                    Mage::throwException($this->_SageHelper()->__('MALFORMED. %s', Mage::helper('sagepaysuite')->__($r['StatusDetail'])));
                    break;
                case parent::RESPONSE_CODE_ERROR:
                    Mage::throwException($this->_SageHelper()->__('ERROR. %s', Mage::helper('sagepaysuite')->__($r['StatusDetail'])));
                    break;
                case parent::RESPONSE_CODE_REJECTED:
                    Mage::throwException($this->_SageHelper()->__('REJECTED. %s', Mage::helper('sagepaysuite')->__($r['StatusDetail'])));
                    break;
                case parent::RESPONSE_CODE_3DAUTH:
                    $result->setResponseStatus($r['Status'])
                        ->setResponseStatusDetail((isset($r['StatusDetail']) ? $r['StatusDetail'] : ''))//Fix for simulator
                        ->set3DSecureStatus($r['3DSecureStatus'])// to store
                        ->setMD($r['MD'])// to store
                        ->setACSURL($r['ACSURL'])
                        ->setPAReq($r['PAReq']);
                    break;
                case parent::RESPONSE_CODE_PAYPAL_REDIRECT:
                    $result->setResponseStatus($r['Status'])
                        ->setResponseStatusDetail($r['StatusDetail'])
                        ->setVpsTxId($r['VPSTxId'])
                        ->setPayPalRedirectUrl($r['PayPalRedirectURL']);
                    break;
                default:

                    $result->setResponseStatus($r['Status'])
                        ->setResponseStatusDetail($r['StatusDetail'])// to store
                        ->setVpsTxId($r['VPSTxId'])// to store
                        ->setSecurityKey($r['SecurityKey'])// to store
                        ->setTrnSecuritykey($r['SecurityKey']);

                    $this->setDefaultNotMandatoryData($r, $result);

                    $result->addData($r);

                    //Saving TOKEN.
                    if (!$callback3D && $result->getData('Token')) {
                        $tokenData = array(
                            'Token' => $result->getData('Token'),
                            'Status' => $result->getData('Status'),
                            'Vendor' => $request->getData('Vendor'),
                            'CardType' => $request->getData('CardType'),
                            'ExpiryDate' => $request->getData('ExpiryDate'),
                            'StatusDetail' => $result->getData('StatusDetail'),
                            'Protocol' => 'direct',
                            'CardNumber' => $request->getData('CardNumber'),
                            'Nickname' => $request->getData('Nickname')
                        );

                        Mage::getModel('sagepaysuite/sagePayToken')->persistCard($tokenData);
                    }
                    break;
            }
        } catch (Exception $e) {
            Sage_Log::logException($e);

            $result
                ->setResponseStatus('ERROR')
                ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($e->getMessage()));
            return $result;
        }

        return $result;
    }

    public function getNewTokenCardArray(Varien_Object $payment)
    {
        $data = array();
        $data ['CardHolder'] = $payment->getCcOwner();
        $data ['CardNumber'] = $payment->getCcNumber();
        $data ['CardType'] = $payment->getCcType();
        $data ['Currency'] = $payment->getOrder()->getOrderCurrencyCode();
        $data ['CV2'] = $payment->getCcCid();
        $data ['Nickname'] = filter_var($payment->getCcNickname(), FILTER_SANITIZE_STRING);
        $data ['Protocol'] = 'direct'; #For persistant storing
        $data ['ExpiryDate'] = str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT) . substr($payment->getCcExpYear(), 2);
        if ($payment->getCcSsStartMonth() && $payment->getCcSsStartYear()) {
            $data['StartDate'] = str_pad($payment->getCcSsStartMonth(), 2, '0', STR_PAD_LEFT) . substr($payment->getCcSsStartYear(), 2);
        }

        return $data;
    }

    /**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        #Process invoice
        if (!$payment->getRealCapture()) {
            return $this->captureInvoice($payment, $amount);
        }

        /**
         * Token Transaction
         */
        if (true === $this->_tokenPresent()/* || $this->_getSageSuiteSession()->getLastSavedTokenccid() */) {
            $_info = new Varien_Object(array('payment' => $payment));
            $result = $this->getTokenModel()->tokenTransaction($_info);
            if ($result['Status'] != 'OK') {
                Mage::throwException(Mage::helper('sagepaysuite')->__($result['StatusDetail']));
            }

            $this->getSageSuiteSession()->setInvoicePayment(true);

            $this->setSagePayResult($result);
            return $this;
        }

        /**
         * Token Transaction
         */
        if ($this->_getIsAdmin() && (int)$this->_getAdminQuote()->getCustomerId() === 0) {
            //$cs = Mage::getModel('customer/customer')->setWebsiteId($this->_getAdminQuote()->getStoreId())->loadByEmail($this->_getAdminQuote()->getCustomerEmail());
            $cs = Mage::helper('sagepaysuite')->existsCustomerForEmail($this->_getAdminQuote()->getCustomerEmail(), $this->_getAdminQuote()->getStore()->getWebsite()->getId());
            if ($cs) {
                Mage::throwException($this->_SageHelper()->__('Customer already exists.'));
            }
        }

        /* if ($this->getSageSuiteSession()->getSecure3d()) {
          $this->capture3D(
          $payment,
          $this->getSageSuiteSession()->getPares(),
          $this->getSageSuiteSession()->getEmede());
          $this->getSageSuiteSession()->setSecure3d(null);
          return $this;
          } */
        $this->getSageSuiteSession()->setMd(null)
            ->setAcsurl(null)
            ->setPareq(null);

        $error = false;

        $payment->setAnetTransType(parent::REQUEST_TYPE_PAYMENT);

        $payment->setAmount($amount);

        $request = $this->_buildRequest($payment);
        $result = $this->_postRequest($request);
        switch ($result->getResponseStatus()) {
            case 'FAIL':
                $payment
                    ->setStatus('FAIL')
                    ->setCcTransId($result->getVPSTxId())
                    ->setLastTransId($result->getVPSTxId())
                    ->setCcApproval('FAIL')
                    ->setAddressResult($result->getAddressResult())
                    ->setPostcodeResult($result->getPostCodeResult())
                    ->setCv2Result($result->getCV2Result())
                    ->setCcCidStatus($result->getTxAuthNo())
                    ->setSecurityKey($result->getSecurityKey())
                    ->setAdditionalData($result->getResponseStatusDetail());
                break;
            case 'FAIL_NOMAIL':
                $error = $result->getResponseStatusDetail();
                break;
            case parent::RESPONSE_CODE_APPROVED:

                $payment->setSagePayResult($result);

                $payment
                    ->setStatus(parent::RESPONSE_CODE_APPROVED)
                    ->setCcTransId($result->getVPSTxId())
                    ->setLastTransId($result->getVPSTxId())
                    ->setCcApproval(parent::RESPONSE_CODE_APPROVED)
                    ->setAddressResult($result->getAddressResult())
                    ->setPostcodeResult($result->getPostCodeResult())
                    ->setCv2Result($result->getCV2Result())
                    ->setCcCidStatus($result->getTxAuthNo())
                    ->setSecurityKey($result->getSecurityKey());

                $this->getSageSuiteSession()->setInvoicePayment(true);

                break;
            case parent::RESPONSE_CODE_3DAUTH:

                $payment->setSagePayResult($result);

                $payment->getOrder()->setIsThreedWaiting(true);

                $this->getSageSuiteSession()->setSecure3dMethod('directCallBack3D');

                $this->getSageSuiteSession()
                    ->setAcsurl($result->getData('a_cs_ur_l'))
                    ->setEmede($result->getData('m_d'))
                    ->setPareq($result->getData('p_areq'));
                $this->setVndor3DTxCode($payment->getVendorTxCode());

                break;
            default:
                if ($result->getResponseStatusDetail()) {
                    $error = '';
                    if ($result->getResponseStatus() == parent::RESPONSE_CODE_NOTAUTHED) {
                        $error = $this->_SageHelper()->__('Your credit card can not be authenticated: ');
                    } else if ($result->getResponseStatus() == parent::RESPONSE_CODE_REJECTED) {
                        $error = $this->_SageHelper()->__('Your credit card was rejected: ');
                    }

                    $error .= $result->getResponseStatusDetail();
                } else {
                    $error = $this->_SageHelper()->__('Error in capturing the payment');
                }
                break;
        }

        if ($error !== false) {
            Mage::throwException($error);
        }

        return $this;
    }

    public function saveOrderAfter3dSecure($pares, $md)
    {

        $this->getSageSuiteSession()->setSecure3d(true);
        $this->getSageSuiteSession()->setPares($pares);
        $this->getSageSuiteSession()->setMd($md);

        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $order = $this->directCallBack3D($quote->getPayment(), $pares, $md);

        $this->getSageSuiteSession()
            ->setAcsurl(null)
            ->setPareq(null)
            ->setSageOrderId(null)
            ->setSecure3d(null)
            ->setEmede(null)
            ->setPares(null)
            ->setMd(null)
            ->setSurcharge(null);

        return $order;
    }

    public function sendNotificationEmail($toEmail = '', $toName = '', $params = array())
    {
        $translate = Mage::getSingleton('core/translate');

        $translate->setTranslateInline(false);

        $storeId = $this->getStoreId();

        if ($this->getWebsiteId() != '0' && $storeId == '0') {
            $storeIds = Mage::app()->getWebsite($this->getWebsiteId())->getStoreIds();
            reset($storeIds);
            $storeId = current($storeIds);
        }

        $toEmail = Mage::getStoreConfig('trans_email/ident_support/email', $storeId);
        $toName = Mage::getStoreConfig('trans_email/ident_support/name', $storeId);


        $mail = Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
            ->sendTransactional(
                Mage::getStoreConfig('payment/sagepaydirectpro/email_timeout_template'), array('name' => Mage::getStoreConfig('trans_email/ident_general/name', $storeId), 'email' => Mage::getStoreConfig('trans_email/ident_general/email', $storeId)),
                //                Mage::getStoreConfig('payment/sagepaydirectpro/email_timeout_identity'),
                $toEmail, $toName, $params
            );

        $translate->setTranslateInline(true);

        return $mail->getSentSuccess();
    }

    /* public function getOrderPlaceRedirectUrl()
      {
      $tmp = $this->getSageSuiteSession();

      Ebizmarts_SagePaySuite_Log::w($tmp->getAcsurl().'-'.$tmp->getEmede().'-'.$tmp->getPareq());

      if ( $tmp->getAcsurl() && $tmp->getEmede() && $tmp->getPareq()) {
      #return Mage::getUrl('sagepaydirectpro/payment/redirect', array('_secure' => true));
      return Mage::getUrl('sagepaydirectpro-3dsecure', array('_secure' => true));
      } else {
      return false;
      }
      } */

    public function getPayPalTitle()
    {
        return Mage::getStoreConfig('payment/sagepaypaypal/title', Mage::app()->getStore()->getId());
    }

    /**
     * @return array
     */
    public function getConfigSafeFields()
    {
        return array('active', 'mode', 'title', 'useccv', 'threed_iframe_height', 'threed_iframe_width', 'threed_layout');
    }

    /**
     * @param array $result
     * @throws Mage_Core_Exception
     */
    private function invalidStatusHalt($result)
    {
        if ($result['Status'] != self::RESPONSE_CODE_APPROVED && $result['Status'] != self::RESPONSE_CODE_3DAUTH && $result['Status'] != self::RESPONSE_CODE_REGISTERED) {
            Mage::throwException(Mage::helper('sagepaysuite')->__($result['StatusDetail']));
        }
    }

    /**
     * @param $error
     */
    private function showErrorMessage($error)
    {
        if ($error !== false) {
            if (Mage::helper('adminhtml')->getCurrentUserId() !== FALSE) {
                Mage::getSingleton('adminhtml/session')->addError($error);
            }

            Mage::throwException($error);
        }
    }

    /**
     * @param Ebizmarts_SagePaySuite_Model_Sagepaysuite_Result $result
     * @return string
     */
    private function returnHumanFriendlyErrorMessage($result)
    {
        $error = "";
        if ($result->getResponseStatus() == self::RESPONSE_CODE_NOTAUTHED) {
            $this->getSageSuiteSession()->setAcsurl(null)->setEmede(null)->setPareq(null);

            $error = $this->_SageHelper()->__('Your credit card can not be authenticated: ');
        } else if ($result->getResponseStatus() == self::RESPONSE_CODE_REJECTED) {
            $this->getSageSuiteSession()->setAcsurl(null)->setEmede(null)->setPareq(null);
            $error = $this->_SageHelper()->__('Your credit card was rejected: ');
        }

        return $error;
    }

    /**
     * @param $order
     * @param Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request $request
     */
    protected function setBasketXmlToRequest($order, $request)
    {
        $forceXml = false;
        if ($order->getPayment()->getMethodInstance()->getCode() == 'sagepaypaypal' &&
            Mage::getStoreConfig('payment/sagepaypaypal/force_basketxml_paypal') == TRUE
        ) {
            //force XML for paypal
            $forceXml = true;
        }

        $basket = Mage::helper('sagepaysuite')->getSagePayBasket($this->_getQuote(), $forceXml);
        if (!empty($basket)) {
            if ($basket[0] == "<") {
                $request->setBasketXML($basket);
            } else {
                $request->setBasket($basket);
            }
        }
    }

    /**
     * @param Varien_Object $payment
     * @param Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request $request
     * @throws Mage_Core_Exception
     */
    protected function validateAmountOrdered(Varien_Object $payment, $request)
    {
        if ($payment->getAmountOrdered()) {
            $this->_setRequestCurrencyAmount($request, $this->_getQuote());
        } else {
            Sage_Log::log('No amount on payment');
            Mage::throwException('No amount on payment');
        }
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $billing
     * @param Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request$request
     */
    protected function setBillingDataOnRequest($billing, $request)
    {
        if (!empty($billing)) {
            $request->setBillingAddress($this->ss($billing->getStreet(1) . ' ' . $billing->getCity() . ' ' . $billing->getRegion() . ' ' . $billing->getCountry(), 100))->setBillingSurname($this->ss($billing->getLastname(), 20))->setBillingFirstnames($this->ss($billing->getFirstname(), 20))->setBillingPostCode($this->sanitizePostcode($this->ss($billing->getPostcode(), 10)))->setBillingAddress1($this->ss($billing->getStreet(1), 100))->setBillingAddress2($this->ss($billing->getStreet(2), 100))->setBillingCity($this->ss($billing->getCity(), 40))->setBillingCountry(trim($billing->getCountry()))->setCustomerName($this->ss($billing->getFirstname() . ' ' . $billing->getLastname(), 100))->setContactNumber(substr($this->_cphone($billing->getTelephone()), 0, 20))->setContactFax($billing->getFax());

            //billing state
            $billingState = $billing->getRegionCode();
            if (!is_null($billingState) && strlen($billingState) > 2) {
                $billingState = substr($billingState, 0, 2);
            }

            if (!empty($billingState)) {
                $request->setBillingState($billingState);
            }

            $request->setCustomerEMail($this->ss($billing->getEmail(), 255));
        }
    }

    /**
     * @param Varien_Object $payment
     * @param Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request $request
     */
    protected function setCreditCardDataOnRequest(Varien_Object $payment, $request)
    {
        if ($payment->getCcNumber()) {
            $request->setCardNumber($payment->getCcNumber())
                ->setExpiryDate(sprintf('%02d%02d', $payment->getCcExpMonth(), substr($payment->getCcExpYear(), strlen($payment->getCcExpYear()) - 2)))
                ->setCardType($payment->getCcType())->setCV2($payment->getCcCid())
                ->setCardHolder($payment->getCcOwner())
                ->setNickname($payment->getCcNickname());

            if ($payment->getCcIssue()) {
                $request->setIssueNumber($payment->getCcIssue());
            }

            if ($payment->getCcStartMonth() && $payment->getCcStartYear()) {
                $request->setStartDate(sprintf('%02d%02d', $payment->getCcStartMonth(), substr($payment->getCcStartYear(), strlen($payment->getCcStartYear()) - 2)));
            }
        } else {
            if ($payment->getCcType() && ($payment->getCcType() == parent::CARD_TYPE_PAYPAL)) {
                $request->setCardType($payment->getCcType());
                $request->setPayPalCallbackURL($this->_getPayPalCallbackUrl());
            }
        }
    }

    /**
     * @param $request
     */
    protected function setThreedSecureDataOnRequest($request)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn() || $this->isMobile()) {
            $request->setApply3DSecure('2');
        } else if ($this->_isMultishippingCheckout()) {
            $request->setApply3DSecure('2');
        } else {
            $request->setApply3DSecure($this->getConfigData('secure3d'));
        }
    }

    /**
     * @param $shipping
     * @param $request
     */
    protected function setShippingDataOnRequest($shipping, $request)
    {
        if (!empty($shipping)) {
            $request->setDeliveryAddress($shipping->getStreet(1) . ' ' . $shipping->getCity() . ' ' . $shipping->getRegion() . ' ' . $shipping->getCountry())->setDeliverySurname($this->ss($shipping->getLastname(), 20))->setDeliveryFirstnames($this->ss($shipping->getFirstname(), 20))->setDeliveryPostCode($this->sanitizePostcode($this->ss($shipping->getPostcode(), 10)))->setDeliveryAddress1($this->ss($shipping->getStreet(1), 100))->setDeliveryAddress2($this->ss($shipping->getStreet(2), 100))->setDeliveryCity($this->ss($shipping->getCity(), 40))//->setDeliveryCountry($shipping->getCountry());
                ->setDeliveryCountry(trim($shipping->getCountry()));

            //shipping state
            $shippingState = $shipping->getRegionCode();
            if (!is_null($shippingState) && strlen($shippingState) > 2) {
                $shippingState = substr($shippingState, 0, 2);
            }

            if (!empty($shippingState)) {
                $request->setDeliveryState($shippingState);
            }
        }
    }

    /**
     * @param Varien_Object $request
     * @return null
     */
    protected function getBuildRequestMode(Varien_Object $request)
    {
        $mode = (($request->getMode()) ? $request->getMode() : null);
        return $mode;
    }

    /**
     * @param $r
     * @param $result
     */
    protected function setNotMandatoryFields($r, $result)
    {
        if (isset($r['VPSTxId'])) {
            $result->setVpsTxId($r['VPSTxId']);
        }

        if (isset($r['SecurityKey'])) {
            $result->setSecurityKey($r['SecurityKey']);
        }
    }

    /**
     * @param $r
     * @param $result
     */
    protected function setDefaultNotMandatoryData($r, $result)
    {
        if (isset($r['3DSecureStatus']))
            $result->set3DSecureStatus($r['3DSecureStatus']);
        if (isset($r['CAVV']))
            $result->setCAVV($r['CAVV']);
        if (isset($r['TxAuthNo']))
            $result->setTxAuthNo($r['TxAuthNo']);
        if (isset($r['AVSCV2']))
            $result->setAvscv2($r['AVSCV2']);
        if (isset($r['PostCodeResult']))
            $result->setPostCodeResult($r['PostCodeResult']);
        if (isset($r['CV2Result']))
            $result->setCv2result($r['CV2Result']);
        if (isset($r['AddressResult']))
            $result->setAddressResult($r['AddressResult']);
    }

}

