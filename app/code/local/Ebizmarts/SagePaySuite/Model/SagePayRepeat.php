<?php

/**
 * Sagepay REPEAT payments
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Model_SagePayRepeat extends Ebizmarts_SagePaySuite_Model_Api_Payment
{

    /**
     * unique internal payment method identifier
     *
     * @var string [a-z0-9_]
     */
    protected $_code                      = 'sagepayrepeat';
    protected $_formBlockType             = 'sagepaysuite/form_sagePayRepeat';
    protected $_infoBlockType             = 'sagepaysuite/info_sagePayRepeat';
    protected $_isGateway                 = true;
    protected $_canAuthorize              = true;
    protected $_canCapture                = true;
    protected $_canCapturePartial         = true;
    protected $_canRefund                 = true;
    protected $_canRefundInvoicePartial   = true;
    protected $_canVoid                   = true;
    protected $_canUseInternal            = true;
    protected $_canUseCheckout            = false;
    protected $_canUseForMultishipping    = false;
    protected $_isInitializeNeeded        = false;
    protected $_canFetchTransactionInfo   = false;
    protected $_canReviewPayment          = false;
    protected $_canCreateBillingAgreement = false;

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data) 
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setRepeatCode($data->getRepeatCode());

        return $this;
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
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @param float $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {

        $quote = $this->_getQuote();

        $vpstxid = trim($quote->getPayment()->getRepeatCode());

        $transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
            ->loadByVpsTxId($vpstxid);

        if ($transaction->getId()) {
            //Retrieve Quote's currency code, it may differ with original transaction
            $currencyCode = $this->getConfigCurrencyCode($quote);

            //REPEAT payment
            $rs = $this->repeat($transaction, $amount, $currencyCode);

            Mage::getSingleton('sagepaysuite/session')->setLastVendorTxCode($rs['_requestvendor_']);

            if ($rs['Status'] == 'OK') {
                //Allow different currency than the original transaction
                $transactionCurrency = (!is_null($currencyCode) ? $currencyCode : $transaction->getTrnCurrency());

                $repeatTransaction = clone $transaction;
                $repeatTransaction->setId(null)
                    ->setOrderId(null)
                    ->setReleased(null)
                    ->setStatus($rs['Status'])
                    ->setStatusDetail($rs['StatusDetail'])
                    ->setVpsTxId($rs['VPSTxId'])
                    ->setTxAuthNo($rs['TxAuthNo'])
                    ->setSecurityKey($rs['SecurityKey'])
                    ->setIntegration($transaction->getIntegration())
                    ->setVendorTxCode($rs['_requestvendor_'])
                    ->setVpsProtocol($transaction->getVpsProtocol())
                    ->setVendorname($transaction->getVendorname())
                    ->setMode($transaction->getMode())
                    ->setTxType(strtoupper($rs['_requesttxtype_']))
                    ->setTrnCurrency($transactionCurrency)
                    ->setTrndate($this->getDate())
                    ->save();

                if (isset($rs['_requesttxtype_']) && ($rs['_requesttxtype_'] == parent::REQUEST_TYPE_REPEATDEFERRED)) {
                    $this->release($repeatTransaction, $amount);
                }

                //If we don't do this, refund online is not available.
                $payment->setLastTransId($rs['VPSTxId']);
            } else {
                Mage::throwException(Mage::helper('sagepaysuite')->__($rs['StatusDetail']));
            }
        } else {
            Mage::throwException(Mage::helper('sagepaysuite')->__('Transaction was not found.'));
        }

        return $this;
    }

    public function getConfigPaymentAction() 
    {
        return Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE;
    }

    public function getTitle() 
    {
        return Mage_Payment_Model_Method_Cc::getTitle();
    }

}
