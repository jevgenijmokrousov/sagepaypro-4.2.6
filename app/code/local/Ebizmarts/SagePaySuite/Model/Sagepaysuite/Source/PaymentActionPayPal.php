<?php

/**
 *
 * Sagepay Payment Action for PayPal Dropdown source
 *
 */
class Ebizmarts_SagePaySuite_Model_Sagepaysuite_Source_PaymentActionPayPal
{

    public function toOptionArray() 
    {
        $options = Mage::getModel('sagepaysuite/sagepaysuite_source_paymentAction')->toOptionArray();

        /*
            Pay Pal support both Deferred and Authenticate payment types with us.
            Please note that we recommend vendors use Authenticate with Pay Pal, as this option allows multiple Authorise attempts, including the normal +/-15% amount adjustment.
            If an attempt to Release a Deferred payment is declined by Pay Pal for any reason, the entire transaction is then marked as failed and no further Release attempts can be made.
         */

        return $options;
    }

}