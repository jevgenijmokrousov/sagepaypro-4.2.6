<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Form_Field_Chargetype extends Mage_Core_Block_Html_Select
{

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml() 
    {
        if (!$this->getOptions()) {
            $this->addOption('fixed', $this->__('Fixed Amount'));
            $this->addOption('percentage', $this->__('Percent'));
        }

        return parent::_toHtml();
    }

    public function setInputName($value) 
    {
        return $this->setName($value);
    }

    public function setColumnName($value) 
    {
        return $this->setExtraParams('rel="#{chargetype}" style="width:120px"');
    }

}