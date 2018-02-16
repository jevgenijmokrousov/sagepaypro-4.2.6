<?php

/**
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */
class Ebizmarts_SagePaySuite_Block_Checkout_Paypalcallback extends Mage_Core_Block_Template
{

    protected function _toHtml()
    {
        $html = '<html><head><title>PayPal - Processing payment...</title></head><body>';
        $html .= '<style>body {background-color: #F7F6F4;}' .
            ' .container {margin: 150px auto 350px; width: 50%; text-align: center;}' .
            ' .container p {font-family: pp-sans-big-light,Helvetica Neue,Arial,sans-serif; color: #444; margin-top: 30px; font-size: 14px;}' .
            ' .container img {width: 120px;}' .
            ' .loader {width: 20px !important; margin: 0px 10px 0 0; position: relative; top: 4px;}</style>';
        $html .= '<div class="container"><img src="' . $this->getSkinUrl('sagepaysuite/images/paypal_checkout.png') . '">';
        $html .= '<p><img class="loader" src="' . $this->getSkinUrl('sagepaysuite/images/ajax-loader.gif') . '">Processing payment, please wait...</p></div>';

        //form POST
        $postData = $this->_getSession()->getData("paypal_post");
        $html .= '<form id="paypal_post_form" method="POST" action="' . Mage::getUrl('sgps/paypalexpress/complete', array('_secure' => true)) . '">';
        $keys = array_keys($postData);
        for ($i = 0;$i < count($keys);$i++) {
            $html .= '<input type="hidden" name="' . $keys[$i] . '" value="' . $postData[$keys[$i]] . '">';
        }

        $html .= '</form>';
        $html .= '<script>document.getElementById("paypal_post_form").submit();</script>';
        $html .= '</body></html>';

        return $html;
    }

    private function _getSession()
    {
        return Mage::getSingleton('sagepaysuite/session');
    }

}