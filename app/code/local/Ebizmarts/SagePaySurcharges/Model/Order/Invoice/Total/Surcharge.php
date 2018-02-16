<?php

class Ebizmarts_SagePaySurcharges_Model_Order_Invoice_Total_Surcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    
    public function collect(Mage_Sales_Model_Order_Invoice $invoice) 
    {

        $invoice->setSurchargeAmount(0);
        $invoice->setBaseSurchargeAmount(0);

        $orderSurchargeAmount = Mage::helper('sagepaysurcharges')->getAmount($invoice->getOrder()->getId());
        
        if (!$orderSurchargeAmount) {
            $orderSurchargeAmount = Mage::getSingleton('core/session')->getData('surchargeamount');
        }
        
        if ($orderSurchargeAmount) {
            $invoice->setSurchargeAmount($orderSurchargeAmount);
            $invoice->setBaseSurchargeAmount($orderSurchargeAmount);

            $invoice->setGrandTotal(($invoice->getGrandTotal()+$orderSurchargeAmount));
            $invoice->setBaseGrandTotal(($invoice->getBaseGrandTotal()+$orderSurchargeAmount));
        }

        return $this;
    }

}