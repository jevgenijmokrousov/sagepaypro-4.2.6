<?php

class Ebizmarts_SagePaySuite_Model_Sagepaysuite_Source_Tokenintegration
{
    public function toOptionArray()
    {
        return array(
                       'server' => Mage::helper('sagepaysuite')->__('Server'),
                       'direct' => Mage::helper('sagepaysuite')->__('Direct')
                    );
    }
}