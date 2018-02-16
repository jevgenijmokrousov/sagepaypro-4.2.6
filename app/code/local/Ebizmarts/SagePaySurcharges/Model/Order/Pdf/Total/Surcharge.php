<?php

class Ebizmarts_SagePaySurcharges_Model_Order_Pdf_Total_Surcharge extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    
    /**
     * Get Total amount from source
     *
     * @return float
     */
    public function getAmount() 
    {
        $amount = Mage::helper('sagepaysurcharges')->getAmount($this->getOrder()->getId());
        return $amount;
    }    
    
}