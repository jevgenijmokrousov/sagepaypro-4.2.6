<?php

class Ebizmarts_SagePaySuite_Model_SagePayNit extends Ebizmarts_SagePaySuite_Model_Api_Payment
{

    protected $_code = 'sagepaynit';
    protected $_formBlockType = 'sagepaysuite/form_sagePayNit';
    protected $_infoBlockType = 'sagepaysuite/info_sagePayNit';

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
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;


    public function registerTransaction($params = null, $macOrder = null)
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

        $_rs  = $this->nitRegisterTransaction($payment, $amount);
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
            ->setReferrerID($this->getConfigData('referrer_id'))
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
            ->setBankAuthCode($_res->getData('BankAuthCode'))
            ->setDeclineCode($_res->getData('DeclineCode'))
            ->save();

        return $_res;
    }

    public function nitRegisterTransaction(Varien_Object $payment, $amount) 
    {


        #Process invoice
        if (!$payment->getRealCapture()) {
            return $this->captureInvoice($payment, $amount);
        }

        $_info = new Varien_Object(array('payment' => $payment));

        $result = $this->nitTransaction($_info);

            if ($result['Status'] != self::RESPONSE_CODE_APPROVED
                && $result['Status'] != self::RESPONSE_CODE_3DAUTH
                && $result['Status'] != self::RESPONSE_CODE_REGISTERED) {
                Mage::throwException(Mage::helper('sagepaysuite')->__($result['StatusDetail']));
            }

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
                    ->setIntegration('nit')
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

    public function validate() 
    {
        $info = $this->getInfoInstance();

        return $this;
    }

    public function nitTransaction(Varien_Object $info) 
    {

        $postData                   = array();
        $postData                   += $this->_getGeneralTrnData($info->getPayment(), $info->getParameters())->getData();
        $postData['VendorTxCode']   = substr($postData['vendor_tx_code'], 0, 40);
        $postData['Txtype']         = $info->getPayment()->getTransactionType();
        $postData['InternalTxtype'] = $postData['Txtype'];
        $postData['Token']          = $info->getPayment()->getNitCardIdentifier();
        $postData['ECDType']        = 1;
        $postData['Description']    = 'Purchased Goods.';
        $postData['Vendor']         = $this->getConfigData('vendor'); //@TODO: Check this for token MOTO transactions.

        //remove unused fields
        if (array_key_exists("c_v2", $postData)) {
            unset($postData["c_v2"]);
        }

        if (array_key_exists("card_holder", $postData)) {
            unset($postData["card_holder"]);
        }

        if (array_key_exists("card_number", $postData)) {
            unset($postData["card_number"]);
        }

        if (array_key_exists("card_type", $postData)) {
            unset($postData["card_type"]);
        }

        if (array_key_exists("expiry_date", $postData)) {
            unset($postData["expiry_date"]);
        }

        $postData = Mage::helper('sagepaysuite')->arrayKeysToCamelCase($postData);
        //$postData['Apply3DSecure'] = (int) Mage::getStoreConfig("payment/sagepaydirectpro/secure3d");

        $urlPost = $this->getTokenUrl('post', 'nit');

        $rs            = $this->requestPost($urlPost, $postData);
        $rs['request'] = new Varien_Object($postData);

        $objRs = new Varien_Object($rs);
        $objRs->setResponseStatus($objRs->getData('Status'))
            ->setResponseStatusDetail($objRs->getData('StatusDetail'));

        $info->getPayment()->setSagePayResult($objRs);

        return $rs;
    }

    public function postForMerchantKey()
    {

        $url = $this->getUrl("api") . "merchant-session-keys";

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "{\"vendorName\": \"" . $this->getConfigData('vendor') ."\"}");
        $sslversion = Mage::getStoreConfig('payment/sagepaysuite/curl_ssl_version');
        curl_setopt($curl, CURLOPT_SSLVERSION, $sslversion);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 8);

        curl_setopt(
            $curl, CURLOPT_SSL_VERIFYPEER,
            Mage::getStoreConfigFlag('payment/sagepaysuite/curl_verifypeer') == 1 ? true : false
        );

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        if (Mage::getStoreConfigFlag('payment/sagepaysuite/curl_proxy') == 1) {
            curl_setopt($curl, CURLOPT_PROXY, Mage::getStoreConfig('payment/sagepaysuite/curl_proxy_port'));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

        //auth
        $secret = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/sagepaynit/password'));
        curl_setopt($curl, CURLOPT_USERPWD, Mage::getStoreConfig('payment/sagepaynit/key') . ":" . $secret);

        $response = curl_exec($curl);

        if (curl_error($curl)) {
            Mage::throwException(curl_error($curl));
        } else {
            $responseData = json_decode($response);

            if ($responseData && array_key_exists("code", $responseData)) {
                Mage::throwException("ERROR " . $responseData->code . " " . $responseData->description);
            } elseif ($responseData && array_key_exists("merchantSessionKey", $responseData)) {
                return $responseData;
            } else {
                Mage::throwException("Unable to request merchant key: Invalid response from SagePay");
            }
        }
    }

    public function saveOrderAfter3dSecure($pares, $md) 
    {

        $this->getSageSuiteSession()->setSecure3d(true);
        $this->getSageSuiteSession()->setPares($pares);
        $this->getSageSuiteSession()->setMd($md);

        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $order = $this->nitCallBack3D($quote->getPayment(), $pares, $md);

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

    public function nitCallBack3D(Varien_Object $payment, $PARes, $MD)
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

            $onePage = Mage::getSingleton('checkout/type_onepage');
            $quote = $onePage->getQuote();
            $quote->collectTotals();

            Mage::helper('sagepaysuite')->ignoreAddressValidation($quote);

            $onePage->saveOrder();

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
                ->setBankAuthCode($result->getData('BankAuthCode'))
                ->setDeclineCode($result->getData('DeclineCode'))
                ->save();

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

    protected function _handleFailStatus($response)
    {

        $ret = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $params['order'] = Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
        $params['error'] = Mage::helper('sagepaysuite')->__($response['StatusDetail']);
        //$rc = $this->sendNotificationEmail('', '', $params);

        $ret->setResponseStatus($response['Status'])
            ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($response['StatusDetail']))
            ->setVPSTxID(1)
            ->setSecurityKey(1)
            ->setTxAuthNo(1)
            ->setAVSCV2(1)
            ->setAddressResult(1)
            ->setPostCodeResult(1)
            ->setCV2Result(1)
            ->setTrnSecuritykey(1);

        return $ret;

    }

    protected function _handle3DAuth($response)
    {

        $ret = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $ret->setResponseStatus($response['Status'])
            ->setResponseStatusDetail((isset($response['StatusDetail']) ? $response['StatusDetail'] : '')) //Fix for simulator
            ->set3DSecureStatus($response['3DSecureStatus'])    // to store
            ->setMD($response['MD']) // to store
            ->setACSURL($response['ACSURL'])
            ->setPAReq($response['PAReq']);

        return $ret;
    }

    protected function _handlePaypalRedirect($response)
    {

        $ret = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $ret->setResponseStatus($response['Status'])
            ->setResponseStatusDetail($response['StatusDetail'])
            ->setVpsTxId($response['VPSTxId'])
            ->setPayPalRedirectUrl($response['PayPalRedirectURL']);

        return $ret;

    }

    protected function _handleUnknownStatus($response)
    {

        $ret = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $ret->setResponseStatus($response['Status'])
            ->setResponseStatusDetail($response['StatusDetail'])  // to store
            ->setVpsTxId($response['VPSTxId'])    // to store
            ->setSecurityKey($response['SecurityKey']) // to store
            ->setTrnSecuritykey($response['SecurityKey']);

        if (isset($response['3DSecureStatus']))
            $ret->set3DSecureStatus($response['3DSecureStatus']);
        if (isset($response['CAVV']))
            $ret->setCAVV($response['CAVV']);
        if (isset($response['TxAuthNo']))
            $ret->setTxAuthNo($response['TxAuthNo']);
        if (isset($response['AVSCV2']))
            $ret->setAvscv2($response['AVSCV2']);
        if (isset($response['PostCodeResult']))
            $ret->setPostCodeResult($response['PostCodeResult']);
        if (isset($response['CV2Result']))
            $ret->setCv2result($response['CV2Result']);
        if (isset($response['AddressResult']))
            $ret->setAddressResult($response['AddressResult']);

        $ret->addData($response);

        return $ret;

    }

    protected function _sagePaySuiteResult($response)
    {

        $ret = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $status = $response['Status'];

        if ($status == 'FAIL_NOMAIL') {
            Mage::throwException($this->_SageHelper()->__($response['StatusDetail']));
        } elseif ($status == parent::RESPONSE_CODE_INVALID || $status == parent::RESPONSE_CODE_MALFORMED || $status == parent::RESPONSE_CODE_ERROR || $status == parent::RESPONSE_CODE_REJECTED) {
            Mage::throwException($this->_SageHelper()->__($status . '. %s', Mage::helper('sagepaysuite')->__($response['StatusDetail'])));
        } elseif ($status == 'FAIL') {
            $ret = $this->_handleFailStatus($response);
        } elseif ($status == parent::RESPONSE_CODE_3DAUTH) {
            $ret = $this->_handle3DAuth($response);
        } elseif ($status == parent::RESPONSE_CODE_PAYPAL_REDIRECT) {
            $ret = $this->_handlePaypalRedirect($response);
        } else {
            $ret = $this->_handleUnknownStatus($response);
        }

        return $ret;

    }

    public function _postRequest(Varien_Object $request, $callback3D = false)
    {

        $result = Mage::getModel('sagepaysuite/sagepaysuite_result');

        $mode = (($request->getMode()) ? $request->getMode() : null);

        $uri = $this->getUrl('post', $callback3D, null, $mode);

        $requestData = $request->getData();

        try {
            $response = $this->requestPost($uri, $requestData);
        } catch (Exception $e) {
            $result->setResponseCode(-1)
                ->setResponseReasonCode($e->getCode())
                ->setResponseReasonText($e->getMessage());

            Mage::throwException($this->_SageHelper()->__('Gateway request error: %s', $e->getMessage()));
        }

        $result->setRequest($request);

        try {
            if (empty($response) OR !isset($response['Status'])) {
                $msg = $this->_SageHelper()->__('Sage Pay is not available at this time. Please try again later.');
                Sage_Log::log($msg, 1);
                $result
                    ->setResponseStatus('ERROR')
                    ->setResponseStatusDetail($msg);
                return $result;
            }

            if (isset($r['VPSTxId'])) {
                $result->setVpsTxId($r['VPSTxId']);
            }

            if (isset($r['SecurityKey'])) {
                $result->setSecurityKey($r['SecurityKey']);
            }

            $result = $this->_sagePaySuiteResult($response);
        } catch (Exception $e) {
            Sage_Log::logException($e);

            $result
                ->setResponseStatus('ERROR')
                ->setResponseStatusDetail(Mage::helper('sagepaysuite')->__($e->getMessage()));

            //return $result;
        }

        return $result;
    }

    public function getConfigSafeFields() 
    {
        return array('active', 'mode', 'title');
    }
}