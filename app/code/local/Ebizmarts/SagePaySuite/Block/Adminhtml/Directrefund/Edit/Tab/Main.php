<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Directrefund_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm() 
    {
        $model = Mage::registry('sagepaysuite_directrefund');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('directrefund_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('sagepaysuite')->__('Mandatory fields')));
        $fieldset->addField(
            'amount', 'text', array(
            'name'     => 'refund[amount]',
            'label'    => Mage::helper('sagepaysuite')->__('Amount'),
            'id'       => 'amount',
            'title'    => Mage::helper('sagepaysuite')->__('Amount'),
            'required' => true,
            'note'     => 'The amount to refund, from 0.01 to 100,000.00.',
            )
        );

        $fieldset->addField(
            'currency', 'select', array(
            'name'     => 'refund[currency]',
            'label'    => Mage::helper('sagepaysuite')->__('Currency'),
            'id'       => 'currency',
            'title'    => Mage::helper('sagepaysuite')->__('Currency'),
            'required' => true,
            'options'  => Mage::helper('sagepaysuite')->currenciesToOptions(),
            'note'     => 'The currency must be supported by one of your Sage Pay merchant accounts or the transaction will be rejected.',
            )
        );

        $fieldset->addField(
            'description', 'text', array(
            'name'     => 'refund[description]',
            'label'    => Mage::helper('sagepaysuite')->__('Description'),
            'id'       => 'description',
            'title'    => Mage::helper('sagepaysuite')->__('Description'),
            'required' => true,
            'note'     => 'Maximum characters: 100.',
            )
        );

        $fieldset->addField(
            'vendor', 'text', array(
            'name'     => 'refund[vendor]',
            'label'    => Mage::helper('sagepaysuite')->__('Vendor'),
            'id'       => 'vendor',
            'title'    => Mage::helper('sagepaysuite')->__('Sage Pay Vendor'),
            'required' => true,
            'note'     => 'Maximum characters: 15.',
            )
        );

        $fieldset->addField(
            'mode', 'select', array(
            'name'     => 'refund[mode]',
            'label'    => Mage::helper('sagepaysuite')->__('Mode'),
            'id'       => 'mode',
            'title'    => Mage::helper('sagepaysuite')->__('Sage Pay Mode'),
            'required' => true,
            'options'  => Mage::getModel('sagepaysuite/sagepaysuite_source_paymentMode')->toOptions(),
            )
        );

        $fieldset->addField(
            'accounttype', 'hidden', array(
            'name' => 'refund[accounttype]',
            )
        );


        $fieldsetToken = $form->addFieldset('cc_token_fieldset', array('legend' => Mage::helper('sagepaysuite')->__('Token information')));
        $fieldsetToken->addField(
            'token', 'text', array(
            'name'     => 'refund[token]',
            'label'    => Mage::helper('sagepaysuite')->__('Token Card'),
            'id'       => 'token',
            'title'    => Mage::helper('sagepaysuite')->__('Token Card'),
            'note'     => 'Use either Token Card or enter all Credit Card data below.',
            )
        );


        $fieldsetCc = $form->addFieldset('cc_fieldset', array('legend' => Mage::helper('sagepaysuite')->__('Credit card information')));
        $fieldsetCc->addField(
            'cardholder', 'text', array(
            'name'     => 'refund[cardholder]',
            'label'    => Mage::helper('sagepaysuite')->__('Card Holder'),
            'id'       => 'cardholder',
            'title'    => Mage::helper('sagepaysuite')->__('Card Holder'),
            'note'     => 'This should be the name displayed on the card',
            )
        );

        $fieldsetCc->addField(
            'cardtype', 'select', array(
            'name'     => 'refund[cardtype]',
            'label'    => Mage::helper('sagepaysuite')->__('Card Type'),
            'id'       => 'cardtype',
            'title'    => Mage::helper('sagepaysuite')->__('Card Type'),
            'options'  => $this->_getCardTypes(),
            )
        );

        $fieldsetCc->addField(
            'cardnumber', 'text', array(
            'name'     => 'refund[cardnumber]',
            'label'    => Mage::helper('sagepaysuite')->__('Card Number'),
            'id'       => 'cardnumber',
            'title'    => Mage::helper('sagepaysuite')->__('Card Number'),
            'note'     => 'The full card number is required.',
            )
        );

        $fieldsetCc->addField(
            'expirydate', 'text', array(
            'name'     => 'refund[expirydate]',
            'label'    => Mage::helper('sagepaysuite')->__('Expiry Date'),
            'id'       => 'expirydate',
            'title'    => Mage::helper('sagepaysuite')->__('Expiry Date'),
            'note'     => 'The expiry date MUST be in MMYY format i.e. 1206 for December 2006. No / or – characters should be included..',
            )
        );

        $fieldsetCc->addField(
            'startdate', 'text', array(
            'name'     => 'refund[startdate]',
            'label'    => Mage::helper('sagepaysuite')->__('Start Date'),
            'id'       => 'startdate',
            'title'    => Mage::helper('sagepaysuite')->__('Start Date'),
            'note'     => 'The start date MUST be in MMYY format i.e. 0699 for June 1999. No / or – characters should be included.',
            )
        );

        $fieldsetCc->addField(
            'issuenumber', 'text', array(
            'name'     => 'refund[issuenumber]',
            'label'    => Mage::helper('sagepaysuite')->__('Issue Number'),
            'id'       => 'issuenumber',
            'title'    => Mage::helper('sagepaysuite')->__('Issue Number'),
            'note'     => 'The issue number MUST be entered EXACTLY as it appears on the card. e.g. some cards have Issue Number "4", others have "04".',
            )
        );

        $data = $model->getData();

        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getCardTypes() 
    {
        $all = Mage::getModel('sagepaysuite/sagepaysuite_source_creditCards')->toOption();

        if (isset($all['PAYPAL'])) {
            unset($all['PAYPAL']);
        }

        if (isset($all['SWITCH'])) {
            unset($all['SWITCH']);
        }

        if (isset($all['LASER'])) {
            unset($all['LASER']);
        }

        return $all;
    }
}