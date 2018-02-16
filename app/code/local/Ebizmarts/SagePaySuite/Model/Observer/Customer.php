<?php

/**
 * Customer events observer model
 *
 * @category   Ebizmarts
 * @package    Ebizmarts_SagePaySuite
 * @author     Ebizmarts <info@ebizmarts.com>
 */

class Ebizmarts_SagePaySuite_Model_Observer_Customer
{
    /**
     * Set cards for customer on CHECKOUT REGISTER
     */
    public function addRegisterCheckoutTokenCards($e)
    {
        $customer = $e->getEvent()->getCustomer();

        $sessionCards = Mage::helper('sagepaysuite/token')->getSessionTokens();

        if ($sessionCards->getSize() > 0) {
            foreach ($sessionCards as $_c) {
                if ($_c->getCustomerId() == 0) {
                    $_c->setCustomerId($customer->getId())
                            ->setVisitorSessionId(null)
                        ->save();
                }
            }
        }

        //remove old tokens
        $customerTokens = Mage::getModel('sagepaysuite2/sagepaysuite_tokencard')
            ->getCollection()
            ->addFieldToFilter("customer_id", $customer->getId());

        foreach ($customerTokens as $token) {
            $expiryDate = $token->getExpiryDate();

            if (!empty($expiryDate) && strlen($expiryDate) == 4) {
                $expiryMonth = substr($expiryDate, 0, 2);
                $expiryYear = substr($expiryDate, 2);
                $currentMonth = date("m");
                $currentYear = date("y");

                $delete = false;

                if ((int)$expiryYear < (int)$currentYear) {
                    $delete = true;
                } elseif ((int)$expiryYear == (int)$currentYear) {
                    if ((int)$expiryMonth <= (int)$currentMonth) {
                        $delete = true;
                    }
                }

                if ($delete == true) {
                    //delete token
                    $token->delete();
                }
            }
        }
    }
}