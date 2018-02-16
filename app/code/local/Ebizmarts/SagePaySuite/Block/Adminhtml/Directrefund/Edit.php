<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Directrefund_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct() 
    {
        $this->_objectId   = 'refund_id';
        $this->_controller = 'adminhtml_directrefund';
        $this->_blockGroup = 'sagepaysuite';

        parent::__construct();
    }

    public function getHeaderText() 
    {
        return Mage::helper('sagepaysuite')->__('New Refund');
    }

}