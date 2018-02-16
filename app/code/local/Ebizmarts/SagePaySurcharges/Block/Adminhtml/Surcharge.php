<?php

class Ebizmarts_SagePaySurcharges_Block_Adminhtml_Surcharge extends Mage_Adminhtml_Block_Sales_Totals
{

    public function initTotals() 
    {

        $parent = $this->getParentBlock();
        $amount = Mage::helper('sagepaysurcharges')->getAmount($this->getOrder()->getId());

        if (!$amount) {
            return $this;
        }

        $parent->addTotal(
            new Varien_Object(
                array(
                'code'      => 'surcharge',
                'value'     => $amount,
                'base_value'=> $amount,
                'label'     => $this->helper('sagepaysurcharges')->__('Credit Card Surcharge'),
                )
            )
        );

        return $this;

    }
}