<?php

class Ebizmarts_SagePaySuite_Helper_DataTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Ebizmarts_SagePaySuite_Helper_Data
     */
    private $_helper;

    public function setUp()
    {
        Mage::app('default');
        $this->_helper = new Ebizmarts_SagePaySuite_Helper_Data;
    }

    /**
     * @param string $encodedParameter
     * @param string $decodedParameter
     * @dataProvider encodedProvider
     */
    public function testDecodeParameter($encodedParameter, $decodedParameter)
    {
        $decoded = $this->_helper->decodeParamFromQuery($encodedParameter);

        $this->assertEquals($decoded, $decodedParameter);
    }

    public function encodedProvider()
    {
        return array(
            array("SJUK%2F09%2F16%2F164738", "SJUK/09/16/164738"),
        );
    }

    /**
     * @param array $inputParams
     * @param array $expected
     * @dataProvider parametersProvider
     */
    public function testSanitizeParamsForQuery($inputParams, $expected)
    {

        $cleaned = $this->_helper->sanitizeParamsForQuery($inputParams);

        foreach ($expected as $key => $value) {
            $this->assertEquals($expected[$key], $cleaned[$key]);
        }
    }

    public function parametersProvider()
    {
        return array(
            'prueba 1' => array(
                array('inv' => 1,
                  'cusid' => 5,
                  'qide' => "23-4342",
                  'incide' => "SJUK/09/16/164738",
                  'oide' => 455
                ),
                array('inv' => 1,
                  'cusid' => 5,
                  'qide' => "23-4342",
                  'incide' => "SJUK%2F09%2F16%2F164738",
                  'oide' => 455
                )
            ),
            'prueba 2' => array (
                array('_secure' => true,
                  'oide' => 4,
                  'qide' => 657,
                  'incide' => "hola-/a213",
                  'inv' => 0
                ),
                array('_secure' => true,
                    'oide' => 4,
                    'qide' => 657,
                    'incide' => "hola-%2Fa213",
                    'inv' => 0
                )
            )
        );
    }

    /**
     * @param array $str
     * @param array $expected
     * @dataProvider provider
     */
    public function testUnderToCamel($str, $expected)
    {

        $pieces = explode("_", $str);

        for ($i=0;$i<count($pieces);$i++) {
            $pieces[$i][0] = strtoupper($pieces[$i][0]);
        }

        $str = implode($pieces);

        $this->assertEquals($str, $expected);


    }

    public function provider()
    {

        return array(
            array('v_ps_protocol', 'VPsProtocol'),
            array('referrer_id', 'ReferrerId'),
            array('vendor', 'Vendor'),
            array('vendor_tx_code', 'VendorTxCode'),
            array('client_ip_address', 'ClientIpAddress'),
            array('amount', 'Amount'),
            array('currency', 'Currency'),
            array('billing_address', 'BillingAddress'),
            array('billing_surname', 'BillingSurname'),
            array('billing_firstnames', 'BillingFirstnames'),
            array('billing_post_code', 'BillingPostCode'),
            array('billing_address1', 'BillingAddress1'),
            array('billing_address2', 'BillingAddress2'),
            array('billing_city', 'BillingCity'),
            array('billing_country', 'BillingCountry'),
            array('contact_number', 'ContactNumber'),
            array('customer_email', 'CustomerEmail'),
            array('description', 'Description'),
            array('delivery_address', 'DeliveryAddress'),
            array('delivery_surname', 'DeliverySurname'),
            array('delivery_firstnames', 'DeliveryFirstnames'),
            array('delivery_post_code', 'DeliveryPostCode'),
            array('delivery_address1', 'DeliveryAddress1'),
            array('delivery_address2', 'DeliveryAddress2'),
            array('delivery_city', 'DeliveryCity'),
            array('delivery_country', 'DeliveryCountry'),
            array('delivery_phone', 'DeliveryPhone'),
            array('basket', 'Basket'),
            array('VendorTxCode', 'VendorTxCode'),
            array('Txtype', 'Txtype'),
            array('InternalTxtype', 'InternalTxtype'),
            array('Token', 'Token'),
            array('ECDType', 'ECDType'),
            array('Description', 'Description'),
            array('Vendor', 'Vendor')

    );

    }

}
