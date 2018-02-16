<?php

class Ebizmarts_SagePaySurcharges_Block_Sales_Order_Surcharge extends Mage_Sales_Block_Order_Totals
{

    public function initTotals() 
    {

        $parent = $this->getParentBlock();
        $amount = Mage::helper('sagepaysurcharges')->getAmount($parent->getOrder()->getId());

        if (!$amount) {
            $reg = Mage::registry('sageserverpost');
            if (is_object($reg)) {
                $amount = $reg->getData('Surcharge');
            }

            if (!$amount) {
                return $this;
            }
        }

        $total = new Varien_Object(
            array(
            'code'      => 'surcharge',
            'value'     => $amount,
            'base_value'=> $amount,
            'label'     => $this->helper('sagepaysurcharges')->__('Credit Card Surcharge'),
            )
        );

        $parent->addTotal($total);

        return $this;

    }

}