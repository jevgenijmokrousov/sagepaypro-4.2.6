<?php

/**
 * Surcharge Total Row Renderer
 *
 * @author Ebizmarts Team <info@ebizmarts.com>
 */

class Ebizmarts_SagePaySurcharges_Block_Checkout_Surcharge extends Mage_Checkout_Block_Total_Default
{

    protected $_template = 'sagepaysurcharges/surcharge/checkout/surcharge.phtml';

    /**
     * Get amount
     *
     * @return float
     */
    public function getSurchargetotal() 
    {
        return $this->getTotal()->getAddress()->getSurchargeAmount();
    }
}