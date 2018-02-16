<?php

class PaymentTest extends PHPUnit_Framework_TestCase
{
    public function testGetClientIp()
    {
        $paymentMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Api_Payment::class)
        ->setMethods(array('getRemoteAddress'))->disableOriginalConstructor()->getMock();
        $paymentMock->expects($this->once())->method('getRemoteAddress')->willReturn('168.0.1.12,127.0.0.1');

        $this->assertEquals('127.0.0.1', $paymentMock->getClientIp());
    }

    public function testGetUrlForEmptyIntegrationCode()
    {
        $apiPayment = $this->mageApiPaymentMock();
        $this->assertNotNull($apiPayment->getUrl("void", false, null, "test"));
    }

    public function testGetUrlForNullIntegrationCode()
    {
        $apiPayment = $this->mageApiPaymentMock();
        $this->assertNotNull($apiPayment->getUrl("void", false, "", "test"));
    }

    /**
     * @dataProvider getUrlProvider
     */
    public function testGetUrl($url, $operation, $threeDFlag, $integrationCode, $mode)
    {
        $apiPayment = $this->mageApiPaymentMock();
        $this->assertEquals($url, $apiPayment->getUrl($operation, $threeDFlag, $integrationCode, $mode));
    }

    public function getUrlProvider()
    {
        return array(
            array("https://live.sagepay.com/gateway/service/void.vsp", "void", false, "sagepaynit", "live"),
            array("https://live.sagepay.com/gateway/service/complete.vsp", "paypalcompletion", false, "sagepaynit", "live"),
            array("https://test.sagepay.com/gateway/service/direct3dcallback.vsp", "post3d", false, "sagepaydirectpro", "test"),
            array("https://test.sagepay.com/Simulator/VSPFormGateway.asp", "post", false, "sagepayform", "simulator"),
        );
    }

    /**
     * @return Ebizmarts_SagePaySuite_Model_SagePayNit|PHPUnit_Framework_MockObject_MockObject
     */
    private function mageApiPaymentMock()
    {
        /** @var Ebizmarts_SagePaySuite_Model_SagePayNit|PHPUnit_Framework_MockObject_MockObject $apiPayment */
        $apiPayment = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array("getUrl", "getCode"))
            ->getMock();

        return $apiPayment;
    }
}