<?php

class Ebizmarts_SagePaySurcharges_Block_Adminhtml_System_Config_Form_Field_Surchargecc extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_ccRenderer;
    protected $_chargeRenderer;

    protected function _getCcRenderer() 
    {
        if (!$this->_ccRenderer) {
            $this->_ccRenderer = Mage::getSingleton('core/layout')->createBlock(
                'sagepaysuite/adminhtml_form_field_creditcard', '',
                array('is_render_to_js_template' => false)
            );
            $this->_ccRenderer->setInputName('creditcard')
                              ->setClass('rel-to-selected')
                              ->showPaypal(false);
        }

        return $this->_ccRenderer;
    }

    protected function _getChargeRenderer() 
    {
        if (!$this->_chargeRenderer) {
            $this->_chargeRenderer = Mage::getSingleton('core/layout')->createBlock(
                'sagepaysuite/adminhtml_form_field_chargetype', '',
                array('is_render_to_js_template' => true)
            );
            $this->_chargeRenderer->setInputName('chargetype')
                                  ->setClass('rel-to-selected');
        }

        return $this->_chargeRenderer;
    }

    public function __construct() 
    {
        $this->addColumn(
            'creditcard', array(
            'label' => Mage::helper('sagepaysuite')->__('Credit Card'),
            'style' => 'width:120px',
            'renderer' => $this->_getCcRenderer(),
            )
        );
        $this->addColumn(
            'chargetype', array(
            'label' => Mage::helper('sagepaysuite')->__('Type'),
            'style' => 'width:120px',
            'renderer' => $this->_getChargeRenderer(),
            )
        );
        $this->addColumn(
            'amount', array(
            'label' => Mage::helper('sagepaysuite')->__('Value'),
            'style' => 'width:120px',
            )
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('sagepaysuite')->__('Add card');
        parent::__construct();
    }
}