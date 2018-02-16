<?php

class Ebizmarts_SagePaySuite_Block_Adminhtml_Sales_Order_Grid_Renderer_StateTest extends PHPUnit_Framework_TestCase
{

    private $_block;

    public function setUp()
    {
        /* You'll have to load Magento app in any test classes in this method */
        $app = Mage::app('default');
        /* You will need a layout for block tests */
        $this->_layout = $app->getLayout();
        /* Let's create the block instance for further tests */
        $this->_block = new Ebizmarts_SagePaySuite_Block_Adminhtml_Sales_Order_Grid_Renderer_State;
        /* We are required to set layouts before we can do anything with blocks */
        $this->_block->setLayout($this->_layout);
    }

    /**
     * @dataProvider provider
     */
    public function testIconReturnsCorrectImage($stateId, $name)
    {
        $icon = $this->_block->icon($stateId);

        $this->assertStringEndsWith($name, $icon);
    }

    public function provider()
    {
        return array(
            array(1, "icon-shield-cross.png"),
            array(8, "icon-shield-cross.png"),
            array(9, "icon-shield-cross.png"),
            array(10, "icon-shield-cross.png"),
            array(11, "icon-shield-cross.png"),
            array(12, "icon-shield-cross.png"),
            array(13, "icon-shield-cross.png"),
            array(17, "icon-shield-cross.png"),
            array(18, "icon-shield-cross.png"),
            array(19, "icon-shield-cross.png"),
            array(20, "icon-shield-cross.png"),
            array(22, "icon-shield-cross.png"),
            array(23, "icon-shield-cross.png"),
            array(27, "icon-shield-cross.png"),
            array(1236, "icon-shield-outline.png"),
            array(14, "icon-shield-check.png"),
            array(15, "icon-shield-check.png"),
            array(16, "icon-shield-check.png"),
            array(26, "icon-shield-check.png"),
            array(2, "icon-shield-outline.png"),
            array(3, "icon-shield-outline.png"),
            array(4, "icon-shield-outline.png"),
            array(5, "icon-shield-outline.png"),
            array(6, "icon-shield-outline.png"),
            array(7, "icon-shield-outline.png"),
            array(24, "icon-shield-outline.png"),
            array(25, "icon-shield-outline.png"),
            array(21, "icon-shield-outline.png"),
            array(777, "icon-shield-outline.png"),
            array(237, "icon-shield-outline.png"),
            array(037, "icon-shield-outline.png"),

        );
    }
}