<?php

/**
 * Direct refunds controller
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Adminhtml_SpsDirectrefundController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction() 
    {
        $this->_title($this->__('Sales'))
             ->_title($this->__('Sage Pay'))
             ->_title($this->__('Direct Refunds'));
    }

    public function indexAction() 
    {
        $this->_initAction();

        $this->loadLayout();
        $this->_setActiveMenu('sales');
        $this->renderLayout();
    }

    public function gridAction() 
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('sagepaysuite/adminhtml_directrefund_grid')->toHtml()
        );
    }

    public function newAction() 
    {
        $this->_forward('edit');
    }

    public function editAction() 
    {
        $this->_initAction();

        $transactionId = $this->getRequest()->getParam('trn_id');
        $transaction   = Mage::getModel('sagepaysuite2/sagepaysuite_transaction');

        if ($transactionId) {
            $transaction->load($transactionId);
            if (! $transaction->getId()) {
                $this->_getSession()->addError($this->__('This transaction no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($this->__('New Refund'));

        // Restore previously entered form data from session
        $data = $this->_getSession()->getUserData(true);
        if (!empty($data)) {
            $transaction->setData($data);
        }

        $transaction->setAccounttype('M');//@ToDo: Add account type as form field

        if ($transaction->getId()) {
            $transaction->setVendor($transaction->getVendorname());
            $transaction->setCurrency($transaction->getTrnCurrency());
            $transaction->setCardtype($transaction->getCardType());
            $transaction->setCardnumber("*********" . $transaction->getLastFourDigits());
        }

        Mage::register('sagepaysuite_directrefund', $transaction);

        $this->loadLayout();
        $this->_setActiveMenu('sales');

        $this->renderLayout();
    }

    /**
     * POST new Expenditure to API
     *
     * @return Ebizmarts_SageOne_Adminhtml_ExpendituresController
     */
    public function saveAction() 
    {

        if ($this->getRequest()->isPost()) {
            try {
                $postData = $this->getRequest()->getPost('refund');

                $refundObject = new Varien_Object($postData);

                $amount = $postData['amount'];

                $result = Mage::getModel('sagepaysuite/api_payment')->directRefund($refundObject, $amount);

                if ($result['response']['Status'] == 'OK') {
                    $this->_getSession()->addSuccess($this->__('The refund was created correctly.'));

                    $action = Mage::getModel('sagepaysuite2/sagepaysuite_action');
                        $action->setParentId(0);
                        $action->setStatus($result['response']['Status']);
                        $action->setStatusDetail($result['response']['StatusDetail']);
                        $action->setVpsTxId($result['response']['VPSTxId']);
                        $action->setTxAuthNo($result['response']['TxAuthNo']);
                        $action->setActionCode('directrefund');
                        $action->setSecurityKey($result['response']['SecurityKey']);
                        $action->setAmount($result['request']['Amount']);
                        $action->setCurrency($result['request']['Currency']);
                        $action->setVendorTxCode($result['request']['VendorTxCode']);
                        $action->setActionDate(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
                    $action->save();

                    $this->_getSession()->setUserData(false);
                }

                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setUserData($postData);

                $this->_redirect('*/*/edit/');
                return;
            }
        }

        $this->_redirect('*/*/');
    }

    public function deleteAction() 
    {
        //@ToDo
        $DA = Null;
    }

    protected function _isAllowed() 
    {
            switch($this->getRequest()->getActionName()){
                case 'index':
                    $acl = 'sales/sagepay/payments/direct_refund';
                    break;
                case 'grid':
                    $acl = 'sales/sagepay/payments/direct_refund';
                    break;
                case 'new':
                    $acl = 'sales/sagepay/payments/direct_refund_list';
                    break;
                case 'edit':
                    $acl = 'sales/sagepay/payments/direct_refund_list';
                    break;
                case 'save':
                    $acl = 'sales/sagepay/payments/direct_refund_list';
                    break;
                default:
                    $acl = 'sales/sagepay/payments/direct_refund';
            }

        return Mage::getSingleton('admin/session')->isAllowed($acl);
    }

}