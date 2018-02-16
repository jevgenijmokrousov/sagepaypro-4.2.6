<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Directrefund extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Block constructor
     */
    public function __construct() 
    {
        $this->_blockGroup = 'sagepaysuite';
        $this->_controller = 'adminhtml_directrefund';
        $this->_headerText = Mage::helper('sagepaysuite')->__('Sage Pay Direct Refunds');

        parent::__construct();
    }

}