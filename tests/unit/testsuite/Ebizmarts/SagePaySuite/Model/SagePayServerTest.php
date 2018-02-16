<?php

class SagePayServerTest extends PHPUnit_Framework_TestCase
{

    public function testBuildRequestDefault() {

        $addressMock = $this->getMockBuilder(Mage_Sales_Model_Quote_Address::class)
            ->setMethods(
                array(
                    'getEmail',
                    'getLastname',
                    'getFirstname',
                    'getStreet',
                    'getPostcode',
                    'getCity',
                    'getCountry',
                    'getTelephone',
                    'getRegionCode'
                ))->disableOriginalConstructor()->getMock();

        $addressMock->expects($this->any())->method('getEmail')->willReturn('info@ebizmarts.com');
        $addressMock->expects($this->exactly(2))->method('getLastname')->willReturn('Doe');
        $addressMock->expects($this->exactly(2))->method('getFirstname')->willReturn('John');
        $addressMock->expects($this->exactly(4))->method('getStreet')->willReturn('Street line');
        $addressMock->expects($this->exactly(2))->method('getPostcode')->willReturn('AB12 3CD');
        $addressMock->expects($this->exactly(2))->method('getCity')->willReturn('London');
        $addressMock->expects($this->exactly(2))->method('getCountry')->willReturn('GB');
        $addressMock->expects($this->exactly(3))->method('getTelephone')->willReturn('123456');
        $addressMock->expects($this->any())->method('getRegionCode')->willReturn('8D');


        $quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->setMethods(
                array(
                    'getBillingAddress',
                    'getShippingAddress',
                    'getGrandTotal',
                    'getQuoteCurrencyCode',
                    'getBaseGrandTotal',
                    'getBaseCurrencyCode',
                    'getIsVirtual',
                ))->disableOriginalConstructor()->getMock();

        $quoteMock->expects($this->exactly(1))->method('getBillingAddress')->willReturn($addressMock);
        $quoteMock->expects($this->exactly(1))->method('getShippingAddress')->willReturn($addressMock);
        $quoteMock->expects($this->any())->method('getGrandTotal')->willReturn(100);
        $quoteMock->expects($this->any())->method('getQuoteCurrencyCode')->willReturn('GBP');
        $quoteMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(100);
        $quoteMock->expects($this->any())->method('getBaseCurrencyCode')->willReturn('GBP');
        $quoteMock->expects($this->any())->method('getIsVirtual')->willReturn(false);


        $sagePayServerMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayServer::class)
            ->setMethods(
                array(
                    '_getQuote',
                    'getConfigData',
                    '_getTrnVendorTxCode',
                    'getCustomerQuoteId',
                    'getCustomerEmail',
                    'getVpsProtocolVersion',
                    '_getIsAdmin',
                    '_getSessionUserName',
                    'getNotificationUrl',
                    'getSuccessUrl',
                    'getRedirectUrl',
                    'getFailureUrl',
                    '_getApplyAvsCv2',
                    'getCustomerXml',
                    '_createToken',
                    'rewardPointsBuildRequest',
                    '_getSagePayBasket',
                    '_getCurrentCurrencyCode',
                    '_getLocaleCode',
                    '_getWebsiteName'
                ))->disableOriginalConstructor()->getMock();

        $sagePayServerMock->expects($this->any())->method('_getQuote')->willReturn($quoteMock);

        $configs = array(
            array('vendor', null, 'testebizmarts'),
            array('payment_action', null, 'PAYMENT'),
            array('mode', null, 'live'),
            array('secure3d', null, 2),
            array('referrer_id', null, '01bf51f9-0dcd-49dd-a07a-3b1f918c77d7'),
            array('trncurrency', null, 'store'),
            array('purchase_description', null, 'Purchase of products ECOMMERCE'),
            array('template_profile', null, 'LOW'),
            array('payment_iframe_position', null, '0'),
            array('allow_gift_aid', null, '0'),
        );
        $sagePayServerMock->expects($this->any())->method('getConfigData')
            ->will($this->returnValueMap($configs));

        $sagePayServerMock->expects($this->exactly(1))->method('getVpsProtocolVersion')->willReturn(3.00);
        $sagePayServerMock->expects($this->exactly(1))->method('_getTrnVendorTxCode')->willReturn('100036742-2018-01-15-20-43-47');
        $sagePayServerMock->expects($this->exactly(1))->method('getCustomerQuoteId')->willReturn('6epa8qt54gd54jcbqj1238jmt3');
        $sagePayServerMock->expects($this->exactly(1))->method('getCustomerEmail')->willReturn('info@ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(2))->method('_getIsAdmin')->willReturn(false);
        $sagePayServerMock->expects($this->any())->method('_getSessionUserName')->willReturn('ebizmarts');
        $sagePayServerMock->expects($this->exactly(1))->method('getNotificationUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('getSuccessUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('getRedirectUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('getFailureUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('_getApplyAvsCv2')->willReturn(0);
        $sagePayServerMock->expects($this->exactly(1))->method('getCustomerXml')->willReturn('<?xml version="1.0"?><customer><customerMobilePhone><![CDATA[123456]]></customerMobilePhone><previousCust>0</previousCust></customer>');
        $sagePayServerMock->expects($this->exactly(1))->method('_createToken')->willReturn(null);
        $sagePayServerMock->expects($this->exactly(1))->method('rewardPointsBuildRequest')->willReturn(null);
        $sagePayServerMock->expects($this->exactly(1))->method('_getSagePayBasket')->willReturn('2:[test] test:1:1.56:0.000:1.56:1.56:FlatRate-Fixed:1:5:0:5:5');
        $sagePayServerMock->expects($this->exactly(1))->method('_getLocaleCode')->willReturn('en');
        $sagePayServerMock->expects($this->exactly(1))->method('_getWebsiteName')->willReturn('Ebizmarts');
        $sagePayServerMock->expects($this->any())->method('_getCurrentCurrencyCode')->willReturn('GBP');


        $expected = new Varien_Object;

        $data = array();
        $data['Apply3DSecure'] = 2;
        $data['VPSProtocol'] = 3.00;
        $data['TxType'] = 'PAYMENT';
        $data['ReferrerID'] = '01bf51f9-0dcd-49dd-a07a-3b1f918c77d7';
        $data['CustomerEMail'] = 'info@ebizmarts.com';
        $data['Vendor'] = 'testebizmarts';
        $data['VendorTxCode'] = '100036742-2018-01-15-20-43-47';
        $data['User'] = 'info@ebizmarts.com';
        $data['Amount'] = '100.00';
        $data['Currency'] = 'GBP';
        $data['Description'] = 'Purchase of products ECOMMERCE User: info@ebizmarts.com';
        $data['NotificationURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3';
        $data['SuccessURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3';
        $data['RedirectURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3';
        $data['FailureURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3';
        $data['BillingSurname'] = 'Doe';
        $data['BillingFirstnames'] = 'John';
        $data['BillingAddress1'] = 'Street line';
        $data['BillingAddress2'] = 'Street line';
        $data['BillingPostCode'] = 'AB12 3CD';
        $data['BillingCity'] = 'London';
        $data['BillingCountry'] = 'GB';
        $data['BillingPhone'] = '123456';
        $data['DeliveryPhone'] = '123456';
        $data['DeliverySurname'] = 'Doe';
        $data['DeliveryFirstnames'] = 'John';
        $data['DeliveryAddress1'] = 'Street line';
        $data['DeliveryAddress2'] = 'Street line';
        $data['DeliveryCity'] = 'London';
        $data['DeliveryPostCode'] = 'AB12 3CD';
        $data['DeliveryCountry'] = 'GB';
        $data['ContactNumber'] = '123456';
        $data['Basket'] = '2:[test] test:1:1.56:0.000:1.56:1.56:FlatRate-Fixed:1:5:0:5:5';
        $data['Language'] = 'en';
        $data['Website'] = 'Ebizmarts';
        $data['Profile'] = 'LOW';
        $data['AllowGiftAid'] = 0;
        $data['ApplyAVSCV2'] = 0;
        $data['CustomerXML'] = '<?xml version="1.0"?><customer><customerMobilePhone><![CDATA[123456]]></customerMobilePhone><previousCust>0</previousCust></customer>';

        $expected->setData($data);

        $this->assertEquals($expected, $sagePayServerMock->_buildRequest());

    }

    public function testBuildRequestAlternateConfigs() {

        $addressMock = $this->getMockBuilder(Mage_Sales_Model_Quote_Address::class)
            ->setMethods(
                array(
                    'getEmail',
                    'getLastname',
                    'getFirstname',
                    'getStreet',
                    'getPostcode',
                    'getCity',
                    'getCountry',
                    'getTelephone',
                    'getRegionCode'
                ))->disableOriginalConstructor()->getMock();

        $addressMock->expects($this->any())->method('getEmail')->willReturn('info@ebizmarts.com');
        $addressMock->expects($this->exactly(2))->method('getLastname')->willReturn('Doe');
        $addressMock->expects($this->exactly(2))->method('getFirstname')->willReturn('John');
        $addressMock->expects($this->exactly(4))->method('getStreet')->willReturn('Street line');
        $addressMock->expects($this->exactly(2))->method('getPostcode')->willReturn('AB12 3CD');
        $addressMock->expects($this->exactly(2))->method('getCity')->willReturn('London');
        $addressMock->expects($this->exactly(2))->method('getCountry')->willReturn('GB');
        $addressMock->expects($this->exactly(3))->method('getTelephone')->willReturn('123456');
        $addressMock->expects($this->any())->method('getRegionCode')->willReturn('8D');


        $quoteMock = $this->getMockBuilder(Mage_Sales_Model_Quote::class)
            ->setMethods(
                array(
                    'getBillingAddress',
                    'getShippingAddress',
                    'getGrandTotal',
                    'getQuoteCurrencyCode',
                    'getBaseGrandTotal',
                    'getBaseCurrencyCode',
                    'getIsVirtual',
                ))->disableOriginalConstructor()->getMock();

        $quoteMock->expects($this->exactly(1))->method('getBillingAddress')->willReturn($addressMock);
        $quoteMock->expects($this->exactly(1))->method('getShippingAddress')->willReturn($addressMock);
        $quoteMock->expects($this->any())->method('getGrandTotal')->willReturn(100);
        $quoteMock->expects($this->any())->method('getQuoteCurrencyCode')->willReturn('GBP');
        $quoteMock->expects($this->any())->method('getBaseGrandTotal')->willReturn(100);
        $quoteMock->expects($this->any())->method('getBaseCurrencyCode')->willReturn('GBP');
        $quoteMock->expects($this->any())->method('getIsVirtual')->willReturn(true);


        $sagePayServerMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayServer::class)
            ->setMethods(
                array(
                    '_getQuote',
                    'getConfigData',
                    '_getTrnVendorTxCode',
                    'getCustomerQuoteId',
                    'getCustomerEmail',
                    'getVpsProtocolVersion',
                    '_getIsAdmin',
                    '_getSessionUserName',
                    'getNotificationUrl',
                    'getSuccessUrl',
                    'getRedirectUrl',
                    'getFailureUrl',
                    '_getApplyAvsCv2',
                    'getCustomerXml',
                    '_createToken',
                    'rewardPointsBuildRequest',
                    '_getSagePayBasket',
                    '_getCurrentCurrencyCode',
                    '_getLocaleCode',
                    '_getWebsiteName'
                ))->disableOriginalConstructor()->getMock();

        $sagePayServerMock->expects($this->any())->method('_getQuote')->willReturn($quoteMock);

        $configs = array(
            array('vendor', null, 'testebizmarts'),
            array('payment_action', null, 'PAYMENT'),
            array('mode', null, 'live'),
            array('secure3d', null, 2),
            array('referrer_id', null, '01bf51f9-0dcd-49dd-a07a-3b1f918c77d7'),
            array('trncurrency', null, 'switcher'),
            array('purchase_description', null, 'Purchase of products ECOMMERCE'),
            array('template_profile', null, 'LOW'),
            array('payment_iframe_position', null, '0'),
            array('allow_gift_aid', null, '0'),
        );
        $sagePayServerMock->expects($this->any())->method('getConfigData')
            ->will($this->returnValueMap($configs));

        $sagePayServerMock->expects($this->exactly(1))->method('getVpsProtocolVersion')->willReturn(3.00);
        $sagePayServerMock->expects($this->exactly(1))->method('_getTrnVendorTxCode')->willReturn('100036742-2018-01-15-20-43-47');
        $sagePayServerMock->expects($this->exactly(1))->method('getCustomerQuoteId')->willReturn('6epa8qt54gd54jcbqj1238jmt3');
        $sagePayServerMock->expects($this->exactly(1))->method('getCustomerEmail')->willReturn('info@ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(2))->method('_getIsAdmin')->willReturn(true);
        $sagePayServerMock->expects($this->any())->method('_getSessionUserName')->willReturn('ebizmarts');
        $sagePayServerMock->expects($this->exactly(1))->method('getNotificationUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('getSuccessUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('getRedirectUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('getFailureUrl')->willReturn('http://ebizmarts.com');
        $sagePayServerMock->expects($this->exactly(1))->method('_getApplyAvsCv2')->willReturn(0);
        $sagePayServerMock->expects($this->exactly(1))->method('getCustomerXml')->willReturn('<?xml version="1.0"?><customer><customerMobilePhone><![CDATA[123456]]></customerMobilePhone><previousCust>0</previousCust></customer>');
        $sagePayServerMock->expects($this->exactly(1))->method('_createToken')->willReturn('1234567890');
        $sagePayServerMock->expects($this->exactly(1))->method('rewardPointsBuildRequest')->willReturn(null);
        $sagePayServerMock->expects($this->exactly(1))->method('_getSagePayBasket')->willReturn('2:[test] test:1:1.56:0.000:1.56:1.56:FlatRate-Fixed:1:5:0:5:5');
        $sagePayServerMock->expects($this->exactly(1))->method('_getLocaleCode')->willReturn('en');
        $sagePayServerMock->expects($this->exactly(1))->method('_getWebsiteName')->willReturn('Ebizmarts');
        $sagePayServerMock->expects($this->any())->method('_getCurrentCurrencyCode')->willReturn('GBP');


        $expected = new Varien_Object;

        $data = array();
        $data['Apply3DSecure'] = '2';
        $data['VPSProtocol'] = 3.00;
        $data['TxType'] = 'PAYMENT';
        $data['ReferrerID'] = '01bf51f9-0dcd-49dd-a07a-3b1f918c77d7';
        $data['CustomerEMail'] = 'info@ebizmarts.com';
        $data['Vendor'] = 'testebizmarts';
        $data['VendorTxCode'] = '100036742-2018-01-15-20-43-47';
        $data['User'] = 'ebizmarts';
        $data['Amount'] = '100.00';
        $data['Currency'] = 'GBP';
        $data['Description'] = 'Purchase of products ECOMMERCE User: ebizmarts';
        $data['NotificationURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3&g=123&n=1';
        $data['SuccessURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3&g=123&n=1';
        $data['RedirectURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3&g=123&n=1';
        $data['FailureURL'] = 'http://ebizmarts.com&c=6epa8qt54gd54jcbqj1238jmt3&g=123&n=1';
        $data['BillingSurname'] = 'Doe';
        $data['BillingFirstnames'] = 'John';
        $data['BillingAddress1'] = 'Street line';
        $data['BillingAddress2'] = 'Street line';
        $data['BillingPostCode'] = 'AB12 3CD';
        $data['BillingCity'] = 'London';
        $data['BillingCountry'] = 'GB';
        $data['BillingPhone'] = '123456';
        $data['DeliveryPhone'] = '123456';
        $data['DeliverySurname'] = 'Doe';
        $data['DeliveryFirstnames'] = 'John';
        $data['DeliveryAddress1'] = 'Street line';
        $data['DeliveryAddress2'] = 'Street line';
        $data['DeliveryCity'] = 'London';
        $data['DeliveryPostCode'] = 'AB12 3CD';
        $data['DeliveryCountry'] = 'GB';
        $data['ContactNumber'] = '123456';
        $data['Basket'] = '2:[test] test:1:1.56:0.000:1.56:1.56:FlatRate-Fixed:1:5:0:5:5';
        $data['Language'] = 'en';
        $data['Website'] = 'Ebizmarts';
        $data['Profile'] = 'LOW';
        $data['AllowGiftAid'] = 0;
        $data['ApplyAVSCV2'] = 0;
        $data['CustomerXML'] = '<?xml version="1.0"?><customer><customerMobilePhone><![CDATA[123456]]></customerMobilePhone><previousCust>0</previousCust></customer>';
        $data['AccountType'] = 'M';
        $data['CreateToken'] = '1';

        $expected->setData($data);

        $adminParams = array();
        $adminParams['order']['account']['group_id'] = '123';
        $adminParams['order']['comment']['customer_note_notify'] = '1';

        $this->assertEquals($expected, $sagePayServerMock->_buildRequest($adminParams));

    }

}