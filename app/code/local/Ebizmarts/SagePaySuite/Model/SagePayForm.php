<?php

require BP . DS . "lib/phpseclib/Crypt/Rijndael.php";

/**
 * FORM main model
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Model_SagePayForm extends Ebizmarts_SagePaySuite_Model_Api_Payment
{

    protected $_code = 'sagepayform';
    protected $_formBlockType = 'sagepaysuite/form_sagePayForm';
    protected $_infoBlockType = 'sagepaysuite/info_sagePayForm';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;

    /** @var Crypt_AES */
    private $phpseclib;

    public function __construct()
    {
        $this->phpseclib = new Crypt_Rijndael(CRYPT_RIJNDAEL_MODE_CBC);
    }

    public function validate() 
    {
        Mage_Payment_Model_Method_Abstract::validate();
        return $this;
    }

    public function isAvailable($quote = null) 
    {
        return Mage_Payment_Model_Method_Abstract::isAvailable($quote);
    }

    /**
     * Return decrypted "encryption pass" from DB
     */
    public function getEncryptionPass() 
    {
        return Mage::helper('core')->decrypt($this->getConfigData('encryption_pass'));
    }

    public function base64Decode($scrambled) 
    {
        // Fix plus to space conversion issue
        $scrambled = str_replace(" ", "+", $scrambled);
        $output = base64_decode($scrambled);
        return $output;
    }

    public function decrypt($dataToDecrypt)
    {
        $cryptPass = $this->getEncryptionPass();
        $this->phpseclib->setKey($cryptPass);
        $this->phpseclib->setIV($cryptPass);

        //** remove the first char which is @ to flag this is AES encrypted
        $hex = substr($dataToDecrypt, 1);

        // Throw exception if string is malformed
        if (!preg_match('/^[0-9a-fA-F]+$/', $hex)) {
            throw new InvalidArgumentException(__('Invalid encryption string'));
        }

        //** HEX decoding
        $strIn = pack('H*', $hex);

        return $this->phpseclib->decrypt($strIn);
    }

    /**
     * @param $dataToSend
     * @param $cryptPass
     * @return string
     */
    public function encrypt($dataToSend, $cryptPass)
    {
        $this->phpseclib->setKey($cryptPass);
        $this->phpseclib->setIV($cryptPass);
        $binaryCipherText = $this->phpseclib->encrypt($dataToSend);
        $hexadecimalText   = bin2hex($binaryCipherText);
        $uppercaseHexadecimalText = strtoupper($hexadecimalText);

        return "@$uppercaseHexadecimalText";
    }

    public function makeCrypt()
    {

        $cryptPass = $this->getEncryptionPass();

        if (Zend_Validate::is($cryptPass, 'NotEmpty') === false) {
            Sage_Log::log('Encryption Pass is empty.', null);
            //Mage::throwException('Encryption Pass is empty.');
        }

        $quoteObj = $this->_getQuote();

        //@TODO: Dont collect totals if Amasty_Promo is present
        $quoteObj->setTotalsCollectedFlag(false)->collectTotals();

        $billing = $quoteObj->getBillingAddress();
        $shipping = $quoteObj->getShippingAddress();

        $customerEmail = $this->getCustomerEmail();

        $data = array();

        $data['CustomerEMail'] = ($customerEmail == null ? $billing->getEmail() : $customerEmail);
        $data['CustomerName'] = $billing->getFirstname() . ' ' . $billing->getLastname();
        $data['VendorTxCode'] = $this->_getTrnVendorTxCode();

        $data['Amount']   = $this->formatAmount($quoteObj->getBaseGrandTotal(), $quoteObj->getBaseCurrencyCode());
        $data['Currency'] = $quoteObj->getBaseCurrencyCode();
        if ((string)$this->getConfigData('trncurrency') == 'store') {
            $data['Amount'] = $this->formatAmount($quoteObj->getGrandTotal(), $quoteObj->getQuoteCurrencyCode());
            $data['Currency'] = $quoteObj->getQuoteCurrencyCode();
        } else if ((string)$this->getConfigData('trncurrency') == 'switcher') {
            $data['Amount'] = $this->formatAmount($quoteObj->getGrandTotal(), Mage::app()->getStore()->getCurrentCurrencyCode());
            $data['Currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        }

        $data['Description'] = $this->cleanInput('product purchase', 'Text');

        $data['SuccessURL'] = Mage::getUrl(
            'sgps/formPayment/success', array(
                '_secure' => true,
                '_nosid' => true,
                'vtxc' => $data['VendorTxCode'],
                'utm_nooverride' => 1
            )
        );
        $data['FailureURL'] = Mage::getUrl(
            'sgps/formPayment/failure', array(
                '_secure' => true,
                '_nosid' => true,
                'vtxc' => $data['VendorTxCode'],
                'utm_nooverride' => 1
            )
        );

        $data['BillingSurname'] = $this->ss($billing->getLastname(), 20);
        $data['ReferrerID'] = $this->getConfigData('referrer_id');
        $data['BillingFirstnames'] = $this->ss($billing->getFirstname(), 20);
        $data['BillingAddress1'] = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(1), 100);
        $data['BillingAddress2'] = ($this->getConfigData('mode') == 'test') ? 88 : $this->ss($billing->getStreet(2), 100);
        $data['BillingPostCode'] = ($this->getConfigData('mode') == 'test') ? 412 : $this->sanitizePostcode($this->ss($billing->getPostcode(), 10));
        $data['BillingCity'] = $this->ss($billing->getCity(), 40);
        $data['BillingCountry'] = $billing->getCountry();
        $data['BillingPhone'] = $this->ss($this->_cphone($billing->getTelephone()), 20);

        // Set delivery information for virtual products ONLY orders
        if ($quoteObj->getIsVirtual()) {
            $data['DeliverySurname'] = $this->ss($billing->getLastname(), 20);
            $data['DeliveryFirstnames'] = $this->ss($billing->getFirstname(), 20);
            $data['DeliveryAddress1'] = $this->ss($billing->getStreet(1), 100);
            $data['DeliveryAddress2'] = $this->ss($billing->getStreet(2), 100);
            $data['DeliveryCity'] = $this->ss($billing->getCity(), 40);
            $data['DeliveryPostCode'] = $this->sanitizePostcode($this->ss($billing->getPostcode(), 10));
            $data['DeliveryCountry'] = $billing->getCountry();
            $data['DeliveryPhone'] = $this->ss($this->_cphone($billing->getTelephone()), 20);
        } else {
            $data['DeliveryPhone']      = $this->ss($this->_cphone($shipping->getTelephone()), 20);
            $data['DeliverySurname']    = $this->ss($shipping->getLastname(), 20);
            $data['DeliveryFirstnames'] = $this->ss($shipping->getFirstname(), 20);
            $data['DeliveryAddress1']   = $this->ss($shipping->getStreet(1), 100);
            $data['DeliveryAddress2']   = $this->ss($shipping->getStreet(2), 100);
            $data['DeliveryCity']       = $this->ss($shipping->getCity(), 40);
            $data['DeliveryPostCode']   = $this->sanitizePostcode($this->ss($shipping->getPostcode(), 10));
            $data['DeliveryCountry']    = $shipping->getCountry();
        }

        if ($data['DeliveryCountry'] == 'US') {
            if ($quoteObj->getIsVirtual()) {
                $data['DeliveryState'] = $billing->getRegionCode();
            } else {
                $data['DeliveryState'] = $shipping->getRegionCode();
            }
        }

        if ($data['BillingCountry'] == 'US') {
            $data['BillingState'] = $billing->getRegionCode();
        }

        $basket = Mage::helper('sagepaysuite')->getSagePayBasket($this->_getQuote(), false);
        if (!empty($basket)) {
            if ($basket[0] == "<") {
                $data['BasketXML'] = $basket;
            } else {
                $data['Basket'] = $basket;
            }
        }

        $data['AllowGiftAid'] = (int)$this->getConfigData('allow_gift_aid');
        $data['ApplyAVSCV2']  = $this->getConfigData('avscv2');

        //Skip PostCode and Address Validation for overseas orders
        if ((int)Mage::getStoreConfig('payment/sagepaysuite/apply_AVSCV2') === 1) {
            if ($this->_SageHelper()->isOverseasOrder($billing->getCountry())) {
                $data['ApplyAVSCV2'] = 2;
            }
        }

        $data['SendEmail']    = (string)$this->getConfigData('send_email');

        $vendorEmail = (string) $this->getConfigData('vendor_email');
        if ($vendorEmail) {
            $data['VendorEMail'] = $vendorEmail;
        }

        $data['Website'] = substr(Mage::app()->getStore()->getWebsite()->getName(), 0, 100);

        $eMessage = $this->getConfigData('email_message');
        if ($eMessage) {
           $data['eMailMessage'] = substr($eMessage, 0, 7500);
        }

        $customerXML = $this->getCustomerXml($quoteObj);
        if (!is_null($customerXML)) {
            $data['CustomerXML'] = $customerXML;
        }

        if (empty($data['DeliveryPostCode'])) {
            $data['DeliveryPostCode'] = '000';
        }

        if (empty($data['BillingPostCode'])) {
            $data['BillingPostCode'] = '000';
        }

        $dataToSend = $this->_getDataToSend($data);

        ksort($data);

        Sage_Log::log("User-Agent: " . Mage::helper('core/http')->getHttpUserAgent(false), null, 'SagePaySuite_REQUEST.log');
        Sage_Log::log(Mage::helper('sagepaysuite')->getUserAgent(), null, 'SagePaySuite_REQUEST.log');
        Sage_Log::log($data, null, 'SagePaySuite_REQUEST.log');

        Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                ->loadByVendorTxCode($data['VendorTxCode'])
                ->setVendorTxCode($data['VendorTxCode'])
                ->setVpsProtocol($this->getVpsProtocolVersion())
                ->setVendorname($this->getConfigData('vendor'))
                ->setMode($this->getConfigData('mode'))
                ->setTxType(strtoupper($this->getConfigData('payment_action')))
                ->setTrnCurrency($data['Currency'])
                ->setIntegration('form')
                ->setTrndate($this->getDate())
                ->setTrnAmount($data['Amount'])
                ->save();

        Mage::getSingleton('sagepaysuite/session')->setLastVendorTxCode($data['VendorTxCode']);
        $strCrypt = $this->encrypt($dataToSend, $cryptPass);

        return $strCrypt;
    }

    protected function _getDataToSend($data)
    {

        $dataToSend = '';

        foreach ($data as $field => $value) {
            if ($value != '') {
                $dataToSend .= ($dataToSend == '') ? "$field=$value" : "&$field=$value";
            }
        }

        return $dataToSend;

    }

    public function capture(Varien_Object $payment, $amount) 
    {
        #Process invoice
        if (!$payment->getRealCapture()) {
            return $this->captureInvoice($payment, $amount);
        }
    }
}
