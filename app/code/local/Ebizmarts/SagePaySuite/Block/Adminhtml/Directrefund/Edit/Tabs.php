<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Directrefund_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct() 
    {
        parent::__construct();
        $this->setId('directrefund_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('sagepaysuite')->__('Refund Information'));
    }

    protected function _beforeToHtml() 
    {
        $this->addTab(
            'main_section', array(
            'label'     => Mage::helper('sagepaysuite')->__('Refund data'),
            'title'     => Mage::helper('sagepaysuite')->__('Refund information'),
            'content'   => $this->getLayout()->createBlock('sagepaysuite/adminhtml_directrefund_edit_tab_main')->toHtml(),
            'active'    => true
            )
        );
        return parent::_beforeToHtml();
    }

}