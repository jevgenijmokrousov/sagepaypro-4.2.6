<?php

$installer = $this;
/**
 * Prepare database for install
 */
$installer->startSetup();

$status = Mage::getModel('sales/order_status');
$status->setStatus('sagepaysuite_pending_payment')->setLabel('Sage Pay Pending Payment')
    ->assignState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
    ->save();

$status2 = Mage::getModel('sales/order_status');
$status2->setStatus('sagepaysuite_pending_cancel')->setLabel('Sage Pay Canceled')
    ->assignState(Mage_Sales_Model_Order::STATE_CANCELED)
    ->save();

/**
 * Prepare database after install
 */
$installer->endSetup();