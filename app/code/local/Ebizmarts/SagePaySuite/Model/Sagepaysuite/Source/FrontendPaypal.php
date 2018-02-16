<?php


/**
 *
 * Sagepay Payment Mode Dropdown source
 *
 */
class Ebizmarts_SagePaySuite_Model_Sagepaysuite_Source_FrontendPaypal
{

    public function toOptionArray() 
    {
        return array(
            array(
                'value' => 'button',
                'label' => Mage::helper('sagepaysuite')->__('Enable Quick Checkout Button')
            ),
            array(
                'value' => 'checkout',
                'label' => Mage::helper('sagepaysuite')->__('Enable on One Page Checkout')
            ),
            array(
                'value' => 'both',
                'label' => Mage::helper('sagepaysuite')->__('Enable both')
            )
        );
    }

}