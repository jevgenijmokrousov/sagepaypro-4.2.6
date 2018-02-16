<?php

/**
 * Paypal Express checkout link
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Block_Paypal_Express_Button extends Mage_Core_Block_Template
{

    /**
     * Whether the block should be eventually rendered
     *
     * @var bool
     */
    protected $_shouldRender = true;


    protected function _beforeToHtml() 
    {

        $frontend = (string)Mage::getStoreConfig('payment/sagepaypaypal/frontend_behaviour', Mage::app()->getStore()->getId());

        $this->_shouldRender = (Mage::getModel('sagepaysuite/api_payment')->isPayPalEnabled()
                               && ($frontend === 'button' or $frontend === 'both'));

        $total = $this->helper('checkout/cart')->getQuote()->getGrandTotal();
        $minTotal =     Mage::getStoreConfig('payment/sagepaypaypal/min_order_total');
        $maxTotal =     Mage::getStoreConfig('payment/sagepaypaypal/max_order_total');

        if (!empty($minTotal) && $total < $minTotal || !empty($maxTotal) && $total > $maxTotal) {
            $this->_shouldRender = false;
        }

        return parent::_beforeToHtml();
    }

    public function getCheckoutUrl() 
    {
        return $this->getUrl('sgps/paypalexpress/go', array('_secure' => true, 'quick'=>1));
    }

    /**
     * Render the block if needed
     *
     * @return string
     */
    protected function _toHtml() 
    {
        if (!$this->_shouldRender) {
            return '';
        }

        return parent::_toHtml();
    }
}
