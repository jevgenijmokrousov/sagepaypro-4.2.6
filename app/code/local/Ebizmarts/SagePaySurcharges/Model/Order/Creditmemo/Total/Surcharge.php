<?php

class Ebizmarts_SagePaySurcharges_Model_Order_Creditmemo_Total_Surcharge extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo) 
    {

        $creditmemo->setSurchargeAmount(0);
        $creditmemo->setBaseSurchargeAmount(0);

        $orderSurchargeAmount = Mage::helper('sagepaysurcharges')->getAmount($creditmemo->getOrder()->getId());
        
        if (!$orderSurchargeAmount) {
            $orderSurchargeAmount = Mage::getSingleton('core/session')->getData('surchargeamount');
        }
        
        if ($orderSurchargeAmount) {
            $creditmemo->setSurchargeAmount($orderSurchargeAmount);
            $creditmemo->setBaseSurchargeAmount($orderSurchargeAmount);

            $creditmemo->setGrandTotal(($creditmemo->getGrandTotal()+$orderSurchargeAmount));
            $creditmemo->setBaseGrandTotal(($creditmemo->getBaseGrandTotal()+$orderSurchargeAmount));
        }

        return $this;
    }

}