<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Directrefund_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    protected $_massactionBlockName = 'sagepayreporting/adminhtml_widget_grid_massaction';

    public function __construct() 
    {
        parent::__construct();
        $this->setId('direct_refunds_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() 
    {
        $collection = Mage::getModel('sagepaysuite2/sagepaysuite_action')->getCollection()->setDirectRefundFilter();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() 
    {

        $this->addColumn(
            'id', array(
            'header' => Mage::helper('sagepaysuite')->__('Internal #'),
            'width' => '50px',
            'index' => 'id',
            'type' => 'number',
            )
        );

        $this->addColumn(
            'status', array(
            'header' => Mage::helper('sagepaysuite')->__('Status'),
            'index' => 'status',
            'type' => 'text',
            )
        );

        $this->addColumn(
            'vendor_tx_code', array(
            'header' => Mage::helper('sagepaysuite')->__('Vendor Tx Code'),
            'index' => 'vendor_tx_code',
            'type' => 'text',
            )
        );

        $this->addColumn(
            'amount', array(
            'header' => Mage::helper('sagepaysuite')->__('Amount'),
            'index' => 'amount',
            'type' => 'number',
            )
        );

        $currencies = Mage::helper('sagepaysuite')->currenciesToOptions();
        $this->addColumn(
            'currency', array(
            'header' => Mage::helper('sagepaysuite')->__('Currency'),
            'index' => 'currency',
            'width' => '100px',
            'type' => 'options',
            'options' => $currencies
            )
        );

        $this->addColumn(
            'vps_tx_id', array(
            'header' => Mage::helper('sagepaysuite')->__('Sage Pay unique ID'),
            'index' => 'vps_tx_id',
            'type' => 'text',
            )
        );

        $this->addColumn(
            'action_date', array(
            'header' => Mage::helper('sagepaysuite')->__('Date'),
            'index' => 'action_date',
            'type' => 'date',
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction() 
    {
        $this->setMassactionIdField('vendortxcode');
        $this->getMassactionBlock()->setFormFieldName('transaction_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem(
            'dlete', array(
            'label' => Mage::helper('sagepaysuite')->__('Delete'),
            'url'   => $this->getUrl('adminhtml/spsDirectrefund/delete'),
            )
        );
        return $this;
    }

    public function getGridUrl() 
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row) 
    {
        return $this->getUrl('adminhtml/sagepayreporting/transactionDetailModal', array('vendortxcode' => $row->getVendorTxCode()));
    }

}