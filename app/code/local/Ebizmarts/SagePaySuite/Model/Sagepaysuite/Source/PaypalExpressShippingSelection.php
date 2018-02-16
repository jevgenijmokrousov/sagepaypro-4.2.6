<?php

/**
 *
 * Sagepay Payment Action Dropdown source
 *
 */
class Ebizmarts_SagePaySuite_Model_Sagepaysuite_Source_PaypalExpressShippingSelection
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'estimate',
                'label' => Mage::helper('sagepaysuite')->__('Force shipping estimate on cart')
            ),
            array(
                'value' => 'review',
                'label' => Mage::helper('sagepaysuite')->__('Second review step after paypal')
            )
        );
    }
}
