<?php

/**
 * SUITE payment controller
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_PaymentController extends Mage_Core_Controller_Front_Action
{

    protected function _expireAjax() 
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            return;
        }
    }

    public function getSageSuiteSession() 
    {
        return Mage::getSingleton('sagepaysuite/session');
    }

    protected function _getQuote() 
    {
        return Mage::getSingleton('sagepaysuite/api_payment')->getQuote();
    }

    /**
     * Return all customer cards list for onepagecheckout use.
     */
    public function getTokenCardsHtmlAction() 
    {
        $html = '';

        $_code = $this->getRequest()->getPost('payment_method', 'sagepaydirectpro');

        try {
            $html .= $this->getLayout()->createBlock('sagepaysuite/form_tokenList', 'token.cards.li')
                    ->setCanUseToken(true)
                    ->setPaymentMethodCode($_code)
                    ->toHtml();
        } catch (Exception $e) {
            Ebizmarts_SagePaySuite_Log :: we($e);
        }

        return $this->getResponse()->setBody(
            str_replace(
                array(
                            '<div id="tokencards-payment-' . $_code . '">',
                            '</div>'
                ), array(), $html
            )
        );
    }

    public function getOnepage() 
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    private function _OSCSaveBilling() 
    {
        $helper = Mage::helper('onestepcheckout/checkout');

        $billingData       = $this->getRequest()->getPost('billing', array());
        $shippingData      = $this->getRequest()->getPost('shipping', array());
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        $shippingAddressId = $this->getRequest()->getPost('shipping_address_id', false);

        $billingData = $helper->load_exclude_data($billingData);
        $shippingData = $helper->load_exclude_data($shippingData);

        if (!Mage::helper('customer')->isLoggedIn()) {
            $emailExists = Mage::helper('sagepaysuite')->existsCustomerForEmail($billingData['email']);

            $regWithoutPassword = (int)Mage::getStoreConfig('onestepcheckout/registration/registration_order_without_password');
            if (1 === $regWithoutPassword && $emailExists) {
                // Place order on the emails account without the password
                $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($billingData['email']);
                Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
            } else {
                $registrationMode = Mage::getStoreConfig('onestepcheckout/registration/registration_mode');
                if ($registrationMode == 'auto_generate_account') {
                    // Modify billing data to contain password also
                    $password = Mage::helper('onestepcheckout/checkout')->generatePassword();
                    $billingData['customer_password'] = $password;
                    $billingData['confirm_password'] = $password;
                    $this->getOnepage()->getQuote()->getCustomer()->setData('password', $password);
                    $this->getOnepage()->getQuote()->setData('password_hash', Mage::getModel('customer/customer')->encryptPassword($password));

                    $this->getOnepage()->getQuote()->setData('customer_email', $billingData['email']);
                    $this->getOnepage()->getQuote()->setData('customer_firstname', $billingData['firstname']);
                    $this->getOnepage()->getQuote()->setData('customer_lastname', $billingData['lastname']);
                }


                if ($registrationMode == 'require_registration' || $registrationMode == 'allow_guest') {
                    if (!empty($billingData['customer_password']) && !empty($billingData['confirm_password']) && ($billingData['customer_password'] == $billingData['confirm_password'])) {
                        $password = $billingData['customer_password'];
                        $this->getOnepage()->getQuote()->setCheckoutMethod('register');
                        $this->getOnepage()->getQuote()->getCustomer()->setData('password', $password);

                        $this->getOnepage()->getQuote()->setData('customer_email', $billingData['email']);
                        $this->getOnepage()->getQuote()->setData('customer_firstname', $billingData['firstname']);
                        $this->getOnepage()->getQuote()->setData('customer_lastname', $billingData['lastname']);

                        $this->getOnepage()->getQuote()->setData('password_hash', Mage::getModel('customer/customer')->encryptPassword($password));
                    }
                }

                if (!empty($billingData['customer_password']) && !empty($billingData['confirm_password'])) {
                    // Trick to allow saving of
                    Mage::getSingleton('checkout/type_onepage')->saveCheckoutMethod('register');
                }
            }
        }//Create Account hook

        //Thanks Dan Norris for his input about this code.
        //if (Mage::helper('customer')->isLoggedIn() && $helper->differentShippingAvailable()) {
        if (Mage::helper('customer')->isLoggedIn()) {
            if (!empty($customerAddressId)) {
                $billingAddress = Mage::getModel('customer/address')->load($customerAddressId);
                if (is_object($billingAddress) && $billingAddress->getCustomerId() == Mage::helper('customer')->getCustomer()->getId()) {
                    $billingData = array_merge($billingData, $billingAddress->getData());
                }
            }

            //if (!empty($shippingAddressId)) {
            if (!empty($shippingAddressId) && $helper->differentShippingAvailable()) {
                $shippingAddress = Mage::getModel('customer/address')->load($shippingAddressId);
                if (is_object($shippingAddress) && $shippingAddress->getCustomerId() == Mage::helper('customer')->getCustomer()->getId()) {
                    $shippingData = array_merge($shippingData, $shippingAddress->getData());
                }
            }
        }

        if (!empty($billingData['use_for_shipping'])) {
            $shippingData = $billingData;
        }

        $this->getOnepage()->getQuote()->getBillingAddress()->addData($billingData)->implodeStreetAddress()->setCollectShippingRates(true);

        $this->oneStepCheckoutSetPaymentMethod($helper);

        if ($helper->differentShippingAvailable()) {
            if (empty($billingData['use_for_shipping'])) {
               $helper->saveShipping($shippingData, $shippingAddressId);
            } else {
                $helper->saveShipping($billingData, $customerAddressId);
            }
        }

        //save addresses
        if (Mage::helper('customer')->isLoggedIn()) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setSaveInAddressBook(empty($billingData['save_in_address_book']) ? 0 : 1);
            $this->getOnepage()->getQuote()->getShippingAddress()->setSaveInAddressBook(empty($shippingData['save_in_address_book']) ? 0 : 1);
        }

        $shippingMethod = $this->getRequest()->getPost('shipping_method', false);

        if (!empty($shippingMethod)) {
            $helper->saveShippingMethod($shippingMethod);
        }

        $requestParams = $this->getRequest()->getParams();
        $this->oneStepCheckoutSaveComments($requestParams);

        $this->oneStepCheckoutFeedback();

        $this->giftMessageEvent();

        $this->oneStepCheckoutSubscribeToNewsletter($requestParams);

        $this->getOnepage()->getQuote()->save();

        //GiftCard
        Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
    }

    public function _IWD_OPCSaveBilling()
    {

        $billingData = $this->getRequest()->getPost('billing', array());

        if (!$this->getOnepage()->getQuote()->getBillingAddress()->getTelephone() || $this->getOnepage()->getQuote()->getBillingAddress()->getTelephone() == "") {
            $this->getOnepage()->getQuote()->getBillingAddress()->setTelephone($billingData['telephone']);
        }

        if (!$this->getOnepage()->getQuote()->getShippingAddress()->getTelephone() || $this->getOnepage()->getQuote()->getShippingAddress()->getTelephone() == "") {
            $this->getOnepage()->getQuote()->getShippingAddress()->setTelephone($billingData['telephone']);
        }

        if (!$this->getOnepage()->getQuote()->getShippingAddress()->getCity() || $this->getOnepage()->getQuote()->getShippingAddress()->getCity() == "") {
            $this->getOnepage()->getQuote()->getShippingAddress()->setCity($billingData['city']);
        }

        if (!$this->getOnepage()->getQuote()->getShippingAddress()->getStreet(1) || $this->getOnepage()->getQuote()->getShippingAddress()->getStreet(1) == "") {
            $this->getOnepage()->getQuote()->getShippingAddress()->setStreet($billingData['street'][0]. "\n" . $billingData['street'][1]);
        }

        $this->getOnepage()->getQuote()->setData('customer_email', $billingData['email']);
        $this->getOnepage()->getQuote()->setData('customer_firstname', $billingData['firstname']);
        $this->getOnepage()->getQuote()->setData('customer_lastname', $billingData['lastname']);

        if (!Mage::helper('customer')->isLoggedIn()) {
            //$emailExists = Mage::helper('sagepaysuite')->existsCustomerForEmail($billing_data['email']);

            if (!empty($billingData['customer_password']) && !empty($billingData['confirm_password']) && ($billingData['customer_password'] == $billingData['confirm_password'])) {
                $password = $billingData['customer_password'];
                $this->getOnepage()->getQuote()->setCheckoutMethod('register');
                $this->getOnepage()->getQuote()->getCustomer()->setData('password', $password);
                $this->getOnepage()->getQuote()->setData('password_hash', Mage::getModel('customer/customer')->encryptPassword($password));
            }
        }
    }

    public function sanitize_string(&$val) 
    {
        $val = filter_var($val, FILTER_SANITIZE_STRING);
    }

    /**
     * Create order action
     */
    public function onepageSaveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        Mage::dispatchEvent('sagepaysuite_place_order', array('post' => $this->getRequest()->getPost()));

        $paymentData = $this->getRequest()->getPost('payment', array());
        if ($paymentData) {
            //Sanitize payment data
            array_walk($paymentData, array($this, "sanitize_string"));

            $this->getOnepage()->getQuote()->getPayment()->importData($paymentData);
        }

        $paymentMethod = $this->getOnepage()->getQuote()->getPayment()->getMethod();

        //check if $0 order (might be using store credit)
        $grandTotal = (float)$this->getOnepage()->getQuote()->getGrandTotal();
        if (empty($grandTotal)) {
            //redirect to default controller
            $this->_forward('saveOrder', 'onepage', 'checkout', $this->getRequest()->getParams());
            return;
        }

        //check if require shipping method
        if (!$this->getOnepage()->getQuote()->isVirtual() &&
            !$this->getOnepage()->getQuote()->getShippingAddress()->getShippingMethod()
        ) {
            $result['success'] = false;
            $result['response_status'] = 'ERROR';
            $result['response_status_detail'] = $this->__('Please choose a shipping method');
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return;
        }

        if ((FALSE === strstr(parse_url($this->_getRefererUrl(), PHP_URL_PATH), 'onestepcheckout')) && is_null($this->getRequest()->getPost('billing'))) { // Not OSC, OSC validates T&C with JS and has it own T&C
            # Validate checkout Terms and Conditions
            $result = array();
            if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                    $result['success'] = false;
                    $result['response_status'] = 'ERROR';
                    $result['response_status_detail'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                    $this->getResponse()->setBody(Zend_Json::encode($result));
                    return;
                }
            }

            # Validate checkout Terms and Conditions

            //Fix issue #9595957091315
            if (!empty($paymentData) && !isset($paymentData['sagepay_token_cc_id'])) {
                $this->getSageSuiteSession()->setLastSavedTokenccid(null);
            }
        } else {
            //reset token session
            if (!empty($paymentData) && !isset($paymentData['sagepay_token_cc_id'])) {
                $this->getSageSuiteSession()->setLastSavedTokenccid(null);
            }

            /**
             * OSC
             */
            if (FALSE !== Mage::getConfig()->getNode('modules/Idev_OneStepCheckout')) {
                $this->_OSCSaveBilling();
            }

            /**
             * OSC
             */

            /**
             * IWD OPC
             */
            if (FALSE !== Mage::getConfig()->getNode('modules/IWD_OnepageCheckout')) {
                # Validate checkout Terms and Conditions
                $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();

                if ($requiredAgreements) {
                    $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                    if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                        $result['success'] = false;
                        $result['response_status'] = 'ERROR';
                        $result['response_status_detail'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                        $this->getResponse()->setBody(Zend_Json::encode($result));
                        return;
                    }
                }

                $this->_IWD_OPCSaveBilling();
            }

            /**
             * IWD OPC
             */
        }

        if ($dataM = $this->getRequest()->getPost('shipping_method', '')) {
            //$this->getOnepage()->saveShippingMethod($this->sanitize_string($dataM));
            $this->getOnepage()->saveShippingMethod($dataM);
        }

        //Magemaven Order comments
        $orderComment = $this->getRequest()->getPost('ordercomment');
        if (is_array($orderComment) && isset($orderComment['comment'])) {
            $comment = trim($orderComment['comment']);
            if (!empty($comment)) {
                $this->getSageSuiteSession()->setOrderComments($comment);
            }
        }

        if ($paymentMethod == 'sagepaypaypal') {
            $resultData = array(
                'success' => 'true',
                'response_status' => 'paypal_redirect',
                'redirect' => Mage::getModel('core/url')->addSessionParam()->getUrl('sgps/paypalexpress/go', array('_secure' => true))
            );

            return $this->getResponse()->setBody(Zend_Json :: encode($resultData));
        }

        if ($paymentMethod == 'sagepayserver') {
            $this->_forward('saveOrder', 'serverPayment', null, $this->getRequest()->getParams());
            return;
        } else if ($paymentMethod == 'sagepaydirectpro') {
            $this->_forward('saveOrder', 'directPayment', null, $this->getRequest()->getParams());
            return;
        } else if ($paymentMethod == 'sagepayform') {
            $this->_forward('saveOrder', 'formPayment', null, $this->getRequest()->getParams());
            return;
        } else if ($paymentMethod == 'sagepaynit') {
            $this->_forward('saveOrder', 'nitPayment', null, $this->getRequest()->getParams());
            return;
        } else {
            //As of release 1.1.18. Left for history purposes, if is not sagepay, post should not reach to this controller
            $this->_forward('saveOrder', 'onepage', 'checkout', $this->getRequest()->getParams());
            return;
        }
    }

    private function oneStepCheckoutFeedback()
    {
        if (Mage::getStoreConfig('onestepcheckout/feedback/enable_feedback')) {
            $feedbackValues        = unserialize(Mage::getStoreConfig('onestepcheckout/feedback/feedback_values'));
            $feedbackValue         = $this->getRequest()->getPost('onestepcheckout-feedback');
            $feedbackValueFreetext = $this->getRequest()->getPost('onestepcheckout-feedback-freetext');
            if (!empty($feedbackValue)) {
                if ($feedbackValue != 'freetext') {
                    $feedbackValue = $feedbackValues[$feedbackValue]['value'];
                } else {
                    $feedbackValue = $feedbackValueFreetext;
                }

                $this->getSageSuiteSession()->setOscCustomerFeedback(Mage::helper('core')->escapeHtml($feedbackValue));
            }
        }
    }

    private function giftMessageEvent()
    {
        //GiftMessage
        $event = new Varien_Object;
        $event->setEvent(new Varien_Object);
        $event->getEvent()->setRequest($this->getRequest());
        $event->getEvent()->setQuote($this->getOnepage()->getQuote());
        Mage::getModel('giftmessage/observer')->checkoutEventCreateGiftMessage($event);
    }

    /**
     * @param $requestParams
     * @return mixed
     */
    private function oneStepCheckoutSaveComments($requestParams)
    {
        if (array_key_exists('onestepcheckout_comments', $requestParams) && !empty($requestParams['onestepcheckout_comments'])) {
            $this->getSageSuiteSession()->setOscOrderComments($requestParams['onestepcheckout_comments']);
        }
    }

    /**
     * @param $requestParams
     */
    private function oneStepCheckoutSubscribeToNewsletter($requestParams)
    {
        if (array_key_exists('subscribe_newsletter', $requestParams) && (int)$requestParams['subscribe_newsletter'] === 1) {
            $this->getSageSuiteSession()->setOscNewsletterEmail($this->getOnepage()->getQuote()->getBillingAddress()->getEmail());
        }
    }

    /**
     * @param $helper
     */
    private function oneStepCheckoutSetPaymentMethod($helper)
    {
        $paymentMethod  = $this->getRequest()->getPost('payment_method', false);
        $selectedMethod = $this->getOnepage()->getQuote()->getPayment()->getMethod();

        $store   = $this->getOnepage()->getQuote() ? $this->getOnepage()->getQuote()->getStoreId() : null;
        $methods = $helper->getActiveStoreMethods($store, $this->getOnepage()->getQuote());

        if ($paymentMethod && !empty($methods) && !in_array($paymentMethod, $methods)) {
            $paymentMethod = false;
        }

        if (!$paymentMethod && $selectedMethod && in_array($selectedMethod, $methods)) {
            $paymentMethod = $selectedMethod;
        }

        if ($this->getOnepage()->getQuote()->isVirtual()) {
            $this->getOnepage()->getQuote()->getBillingAddress()->setPaymentMethod(!empty($paymentMethod) ? $paymentMethod : null);
        } else {
            $this->getOnepage()->getQuote()->getShippingAddress()->setPaymentMethod(!empty($paymentMethod) ? $paymentMethod : null);
        }

        try {
            if ($paymentMethod) {
                $this->getOnepage()->getQuote()->getPayment()->getMethodInstance();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

}
