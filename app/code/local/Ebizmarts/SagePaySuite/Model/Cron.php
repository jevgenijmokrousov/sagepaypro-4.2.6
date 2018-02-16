<?php

/**
 * Cron processor object
 *
 */
class Ebizmarts_SagePaySuite_Model_Cron
{
    
    /**
     * Sync Data from API for each new transaction within the last 24 hours.
     * 
     * @param type $cron
     * @return \Ebizmarts_SagePaySuite_Model_Cron
     */
    public function syncFromApi($cron) 
    {        
        
        $syncMode = (string)Mage::getStoreConfig('payment/sagepaysuite/sync_mode');
        
        if ($syncMode === 'async') {
            $transactions = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')
                                ->getCollection()
                                ->getApproved();
            
            $transactions->addFieldToFilter('created_at', array('neq' => '0000-00-00 00:00:00'));            
            
            $ts = gmdate("Y-m-d H:i:s");
            $transactions->addFieldToFilter('created_at', array("from" => gmdate("Y-m-d H:i:s", strtotime("-1 day")), "to" => $ts));
            
            if ($transactions->getSize()) {
                foreach ($transactions as $trn) {
                    $trn->updateFromApi();
                }
            }
        }
        
        return $this;
        
    }
    
    public function processTransactionQueue($cron) 
    {

        $jobs = Mage::getModel('sagepaysuite2/sagepaysuite_queue')->getCollection()
                ->addFieldToFilter('processed', 0)
                ->load();

        if ($jobs->getSize() > 0) {
            foreach ($jobs as $_job) {
                $trn = Mage::getModel('sagepaysuite2/sagepaysuite_transaction')->load($_job->getTransactionId());

                if ($trn->getId()) {
                    $details = Mage::getModel('sagepayreporting/sagepayreporting')
                            ->getTransactionDetails(null, $trn->getVpsTxId());

                    if ((string) $details->getErrorcode() === '0000') {
                        $_job->setProcessed(1);

                        //If transaction state is not ok cancel order
                        if ($details->getTxStateId() != 1) {
                            //Load order and cancel it
                            $order = Mage::getModel('sales/order')->load($trn->getOrderId());
                            if ($order->getId()) {
                                $order->cancel();
                            }

                            $_job->setProcessedStatus($details->getTxStateId())
                                    ->setProcessedStatusDetail($details->getStatus());
                        }

                        $_job->setProcessedAt(Mage::getModel('core/date')->gmtDate())
                                ->save();
                    }
                }
            }
        }
    }

    public function cancelPendingPaymentOrders($cron)
    {

        if ((int)Mage::getStoreConfig('payment/sagepayserver/pre_save') === 1 &&
            (int)Mage::getStoreConfig('payment/sagepayserver/cancel_pending_payment') > 0) {
            $minutes = (int)Mage::getStoreConfig('payment/sagepayserver/cancel_pending_payment');

            $orders = Mage::getModel('sales/order')->getCollection()
                ->addAttributeToFilter('created_at', array("to" => gmdate("Y-m-d H:i:s", strtotime("-".$minutes." minutes"))))
                ->addAttributeToFilter('status', array('eq' => "sagepaysuite_pending_payment"));

            if ($orders->getSize()) {
                foreach ($orders as $order) {
                    if ($order->canCancel()) {
                        try {
                            $order->cancel();
                            $order->setStatus("sagepaysuite_pending_cancel");
                            $order->save();
                            Mage::dispatchEvent('sagepaysuite_order_cancel', array('order' => $order));
                        } catch (Exception $e) {
                            Mage::logException($e);
                        }
                    }
                }
            }
        }
    }

}