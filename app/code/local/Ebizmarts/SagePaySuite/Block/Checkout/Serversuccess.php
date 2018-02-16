<?php

/**
 * Server success block
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Block_Checkout_Serversuccess extends Mage_Core_Block_Template
{

    protected function _getSess()
    {
        return Mage::getSingleton('sagepaysuite/api_payment')->getSageSuiteSession();
    }

    protected function _toHtml()
    {

        // If the order is and Admin order
        $orderId = $this->_getSess()->getDummyId();
        if (Mage::getSingleton('admin/session')->isLoggedIn() && $orderId) {
            $successUrl = Mage::getModel('adminhtml/url')->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId, '_secure' => true));
        } else {
            if (!is_null($this->getRequest()->getParam('qide'))
                && !is_null($this->getRequest()->getParam('incide'))
                && !is_null($this->getRequest()->getParam('oide'))) {
                $transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                    ->loadByParent($this->getRequest()->getParam('oide'));
                $firstArrive = $transaction->getData("server_success_arrived") == false;
                Mage::getSingleton('core/session')->setData("sagepay_server_first_arrive", $firstArrive);

                if (!$this->isPreSaveEnabled()) {
                    //relogin user if just registered
                    $quote = Mage::getModel('sales/quote')->load($this->getRequest()->getParam('qide'));
                    $isRegister = ($quote->getData('checkout_method') == 'register');
                    $quoteCustomerId = $quote->getData('customer_id');
                    $transaction = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                        ->loadByParent($this->getRequest()->getParam('oide'));
                    if ($isRegister && $quoteCustomerId == $this->getRequest()->getParam('cusid')) {
                        //check transaction flag
                        if ($firstArrive) {
                            Mage::getSingleton('customer/session')->loginById($this->getRequest()->getParam('cusid'));
                        }
                    }

                    //make sure quote is deactivated
                    if ((bool)$quote->getIsActive() == true) {
                        $quote->setIsActive(false)->save();
                    }

                    //saved core messages

                    //@codingStandardsIgnoreStart
                    try {
                        if (!is_null($transaction->getData("server_session"))) {
                            $serverSession = json_decode($transaction->getData("server_session"));
                            if (!is_null($serverSession) && array_key_exists("core_messages", $serverSession)) {
                                $messages = $serverSession->core_messages;
                                if (array_key_exists("success", $messages)) {
                                    foreach ($messages->success as $message) {
                                        Mage::getSingleton('core/session')->addSuccess($message);
                                    }
                                }
                                if (array_key_exists("error", $messages)) {
                                    foreach ($messages->error as $message) {
                                        Mage::getSingleton('core/session')->addError($message);
                                    }
                                }
                            }
                        }
                    }catch (Exception $e){
                        Mage::logException($e);
                    }
                    //@codingStandardsIgnoreEnd
                }

                if ($firstArrive) {
                    $transaction->setData("server_success_arrived", true)->save();
                }

                Mage::getSingleton('checkout/session')
                    ->setLastSuccessQuoteId($this->getRequest()->getParam('qide'))
                    ->setLastQuoteId($this->getRequest()->getParam('qide'))
                    ->setLastOrderId($this->getRequest()->getParam('oide'))
                    ->setLastRealOrderId(Mage::helper('sagepaysuite')->decodeParamFromQuery($this->getRequest()->getParam('incide')));

                //set invoice flag
                $autoInvoice = (int)$this->getRequest()->getParam('inv');
                $preventInvoice = ((int)Mage::getStoreConfig('payment/sagepaysuite/prevent_invoicing') === 1);
                Mage::getSingleton('sagepaysuite/session')->setCreateInvoicePayment($autoInvoice && !$preventInvoice);

                if ($this->isPreSaveEnabled() && $firstArrive) {
                    $order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('oide'));

                    //change status
                    $order->setStatus((string)Mage::getModel('sagepaysuite/sagePayServer')->getConfigData('order_status'))->save();

                    //send new order email
                    $order->sendNewOrderEmail();

                    //remove order pre-saved flag from checkout
                    Mage::getSingleton('sagepaysuite/session')->setData("sagepaysuite_presaved_order_pending_payment", null);
                }
            }

            if ((int)Mage::getStoreConfig('payment/sagepayserver/parameters_in_success_url') === 1) {
                $_succuessParams = Mage::helper('sagepaysuite')->sanitizeParamsForQuery(
                    array('_secure' => true,
                    'oide' => $this->getRequest()->getParam('oide'),
                    'qide' => $this->getRequest()->getParam('qide'),
                    'incide' => $this->getRequest()->getParam('incide'),
                    'inv' => $this->getRequest()->getParam('inv'))
                );

                $successUrl = Mage::getModel('core/url')->getUrl('checkout/onepage/success', $_succuessParams);
            } else {
                $successUrl = Mage::getModel('core/url')->getUrl('checkout/onepage/success', array('_secure' => true));
            }



            //recover multishipping data
            if ($this->getRequest()->getParam('multishipping')) {
                //get multishipping ids data
                $msorderids = $this->getRequest()->getParam('msorderids');
                $msorderids = explode(",", $msorderids);
                $msorderidsArray = array();
                for ($i = 0;$i<count($msorderids);$i++) {
                    $aux = explode(":", $msorderids[$i]);
                    $msorderidsArray[$aux[0]] = $aux[1];
                }

                Mage::getSingleton('core/session')->setOrderIds($msorderidsArray);
                Mage::getSingleton('checkout/type_multishipping')->setOrderIds($msorderidsArray);
                Mage::getSingleton('checkout/type_multishipping')->getCheckoutSession()->setDisplaySuccess(true);

                $successUrl = Mage::getUrl('checkout/multishipping/success', array('_secure' => true));

                $state = Mage::getSingleton('checkout/type_multishipping_state');
                $state->setCompleteStep(Mage_Checkout_Model_Type_Multishipping_State::STEP_OVERVIEW);
            }
        }

        $html = '<html><body>';
        $html.= '<script type="text/javascript">(parent.location == window.location)? window.location.href="' . $successUrl . '" : window.parent.setLocation("' . $successUrl . '");</script>';
        //$html.= '<script type="text/javascript">window.parent.setLocation("' . $successUrl . '");</script>';
        $html.= '</body></html>';

        return $html;
    }

    private function isPreSaveEnabled($isMOTO=false)
    {
        if ($isMOTO) {
            return (int)Mage::getStoreConfig('payment/sagepayserver_moto/pre_save') === 1;
        } else {
            return (int)Mage::getStoreConfig('payment/sagepayserver/pre_save') === 1;
        }
    }
}