<?php

class Ebizmarts_SagePayReporting_Model_Cron
{

    /**
     * Retrieve fraud score (3rd man) for transactions that do not have score.
     * @param  $cron Cron object
     * @return Ebizmarts_SagePayReporting_Model_Cron
     */
    public function getThirdmanScores($cron) 
    {

        $fraudTblName = Mage::getSingleton('core/resource')->getTableName('sagepayreporting_fraud');
        $transactions = Mage::getResourceModel('sagepaysuite2/sagepaysuite_transaction_collection');
        $transactions->addFieldToSelect(array('order_id', 'vendor_tx_code', 'vps_tx_id', 'tx_type'));

        $transactions
        ->getSelect()
        ->where("`main_table`.`order_id` IS NOT NULL AND `main_table`.`trndate` IS NOT NULL AND `main_table`.`trndate` > (CURDATE() - INTERVAL 2 DAY) AND (`main_table`.`order_id` NOT IN (SELECT `order_id` FROM ". $fraudTblName ."))")
        ->order("main_table.created_at DESC")
        ->limit(30);

        foreach ($transactions as $_trn) {
            $update = $_trn->updateFromApi();
        }

        $this->_autofullfillTransactions();
    }

    protected function _autofullfillTransactions()
    {
        if ($this->_getCanAutofullfill()) {
            $logPrefix = "[CRON] ";

            $fraudTblName = Mage::getSingleton('core/resource')->getTableName('sagepayreporting_fraud');
            $transactions = Mage::getResourceModel('sagepaysuite2/sagepaysuite_transaction_collection');
            $transactions->addFieldToSelect(array('order_id', 'vendor_tx_code', 'vps_tx_id', 'tx_state_id', 'tx_type'));

            $query = $transactions
                ->getSelect()
                ->where("`main_table`.`order_id` IS NOT NULL AND `main_table`.`created_at` > (CURDATE() - INTERVAL 2 DAY) AND ((`main_table`.`tx_state_id` = 14 AND `main_table`.`released` <> 1) OR (`main_table`.`tx_state_id` = 15 AND `main_table`.`authorised` <> 1))")
                ->join(array('fraud'=> $fraudTblName), '`fraud`.`order_id` = `main_table`.`order_id`', array('fraud.thirdman_score'));

            //Mage::log($query->__toString());

            foreach ($transactions as $_trn) {
                try {
                    if ((int)$_trn->getThirdmanScore() <=  $this->_getAutofullfillScore()) {
                        Sage_Log::log($logPrefix . "Auto invoicing " . $_trn->getVendorTxCode() . ": " . (int)$_trn->getThirdmanScore(), null, 'SagePaySuite_Thirdman.log');
                        Mage::getModel('sagepaysuite/api_payment')->invoiceOrder($_trn->getOrderId(), Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    } else {
                        Sage_Log::log($logPrefix . "High fraud score for " . $_trn->getVendorTxCode() . ": " . (int)$_trn->getThirdmanScore(), null, 'SagePaySuite_Thirdman.log');
                    }
                } catch (Exception $e) {
                    Sage_Log::logException($e);
                }
            }
        }
    }

    protected function _getCanAutofullfill()
    {
        return Mage::getStoreConfigFlag('payment/sagepaysuite/auto_fulfill_low_risk_trn');
    }

    protected function _getAutofullfillScore()
    {
        return (int)Mage::getStoreConfig('payment/sagepaysuite/auto_fulfill_low_risk_trn_value');
    }

}