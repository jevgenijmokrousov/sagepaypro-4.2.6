<?php

class Ebizmarts_SagePaySuite_Model_SagePayNitTest extends PHPUnit_Framework_TestCase
{

    public function testPostRequestSagePayNotAvailable()
    {
        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->once())->method('getData')->willReturn($postRequest);


        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn("");

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals("ERROR", $result->getResponseStatus());
        $this->assertEquals("Sage Pay is not available at this time. Please try again later.", $result->getResponseStatusDetail());

    }

    public function testPostRequestFail()
    {
        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'FAIL',
            'StatusDetail' => '0000 : The Authorisation was Successful.' //TODO: cambiar por uno mejor
        );
        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals('FAIL', $result->getResponseStatus());
        $this->assertEquals($requestPost['StatusDetail'], $result->getResponseStatusDetail());

    }


    public function testPostRequestFailNomail()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'FAIL_NOMAIL',
            'StatusDetail' => 'The Authorisation was NOT Successful.' //TODO: cambiar por uno mejor
        );
        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals("ERROR", $result->getResponseStatus());
        $this->assertEquals("The Authorisation was NOT Successful.", $result->getResponseStatusDetail());

    }

    public function testPostInvalid()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();

        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

         $requestPost = array(
             'Status' => 'INVALID',
             'StatusDetail' => 'The Authorisation was NOT Successful.' //TODO: cambiar por uno mejor
             );

        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals("ERROR", $result->getResponseStatus());
        $this->assertEquals("INVALID. The Authorisation was NOT Successful.", $result->getResponseStatusDetail());

    }

    public function testPostMalformed()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'MALFORMED',
            'StatusDetail' => 'The Authorisation was NOT Successful.' //TODO: cambiar por uno mejor
        );
        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals("ERROR", $result->getResponseStatus());
        $this->assertEquals("MALFORMED. The Authorisation was NOT Successful.", $result->getResponseStatusDetail());

    }

    public function testPostError()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'ERROR',
            'StatusDetail' => 'The Authorisation was NOT Successful.' //TODO: cambiar por uno mejor
        );
        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals("ERROR", $result->getResponseStatus());
        $this->assertEquals("ERROR. The Authorisation was NOT Successful.", $result->getResponseStatusDetail());

    }

    public function testPostRejected()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'REJECTED',
            'StatusDetail' => 'The Authorisation was NOT Successful.' //TODO: cambiar por uno mejor
        );
        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals("ERROR", $result->getResponseStatus());
        $this->assertEquals("REJECTED. The Authorisation was NOT Successful.", $result->getResponseStatusDetail());

    }

    public function testPost3dAuth()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => '3DAUTH',
            'StatusDetail' => 'The Authorisation was NOT Successful.', //TODO: cambiar por uno mejor
            '3DSecureStatus' => '??',
            'ACSURL' => '??',
            'PAReq' => '??',
            'MD' => '20147161549830410466'
        );

        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals($requestPost['Status'], $result->getResponseStatus());
        $this->assertEquals($requestPost['StatusDetail'], $result->getResponseStatusDetail());
        $this->assertEquals($requestPost['3DSecureStatus'], $result->get3DSecureStatus());
        $this->assertEquals($requestPost['ACSURL'], $result->getACSURL());
        $this->assertEquals($requestPost['PAReq'], $result->getPAReq());
        $this->assertEquals($requestPost['MD'], $result->getMD());

    }

    public function testPostPPRedirect()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'PPREDIRECT',
            'StatusDetail' => 'The Authorisation was NOT Successful.', //TODO: cambiar por uno mejor
            'VPSTxId' => '??',
            'PayPalRedirectURL' => '??',
        );

        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals($requestPost['Status'], $result->getResponseStatus());
        $this->assertEquals($requestPost['StatusDetail'], $result->getResponseStatusDetail());
        $this->assertEquals($requestPost['VPSTxId'], $result->getVpsTxId());
        $this->assertEquals($requestPost['PayPalRedirectURL'], $result->getPayPalRedirectUrl());

    }

    public function testPostDefault()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->any())->method('getData')->willReturn($postRequest);

        $requestPost = array(
            'Status' => 'DEFAULT',
            'StatusDetail' => 'The Authorisation was NOT Successful.', //TODO: cambiar por uno mejor
            'VPSTxId' => '??',
            'SecurityKey' => '??',
            '3DSecureStatus' => '??',
            'CAVV' => '??',
            'TxAuthNo' => '??',
            'AVSCV2' => '??',
            'PostCodeResult' => '??',
            'CV2Result' => '??',
            'AddressResult' => '??',
        );

        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();
        $sagePayNitMock->expects($this->once())->method('requestPost')->willReturn($requestPost);

        $result = $sagePayNitMock->_postRequest($requestMock);

        $this->assertEquals($requestPost['Status'], $result->getResponseStatus());
        $this->assertEquals($requestPost['StatusDetail'], $result->getResponseStatusDetail());
        $this->assertEquals($requestPost['VPSTxId'], $result->getVpsTxId());
        $this->assertEquals($requestPost['SecurityKey'], $result->getTrnSecuritykey());
        $this->assertEquals($requestPost['3DSecureStatus'], $result->get3DSecureStatus());
        $this->assertEquals($requestPost['CAVV'], $result->getCAVV());
        $this->assertEquals($requestPost['TxAuthNo'], $result->getTxAuthNo());
        $this->assertEquals($requestPost['AVSCV2'], $result->getAvscv2());
        $this->assertEquals($requestPost['PostCodeResult'], $result->getPostCodeResult());
        $this->assertEquals($requestPost['CV2Result'], $result->getCv2result());
        $this->assertEquals($requestPost['AddressResult'], $result->getAddressResult());

    }

    /**
     * @expectedException        Mage_Core_Exception
     * @expectedExceptionMessage Gateway request error: msg
     */
    public function testGatewayRequestError()
    {

        $postRequest = array(
            'MD'  => '20147161549830410466',
            'PAReq' => 'eJxVUdFuwjAMfN9XVH1FIglNS0EmiA22IWCqBtrGY9ZaUIm2kLQr7OuXQNlGnnwX63w+w/CY7ZwvVDot8oHL2tQdijtYbRXieIlxpVDAArWWG3TSZOB2KONdFjCf90KPckZ5ELgCotErHgQ0QsLotBmQKzQKKt7KvBQg48P99EVwGvpeB0gDIUM1HQufU+4FPr28EMiFhlxmKFaoS2fymX5nUpUayJmEuKjyUp1E2AmAXAFUaie2ZbnXfULqum4DsQyQPx9RZSttFI5pIp4+Wq1HPlH1vCoitY7Gb2vyfpjN5mwzAGI7IJElCrN9QEPWc5jfp7zvG4tnHmRmRwuvG/rGR4Ngb4eMbr7+U2DiVZjHxj2nxv0VAR73RY6mwyT0W0OCOhZLe4hInpxFNDXDLQXkb5mHZxtxXJrUmE33XFm91MTSYfQiaAEQ20uaw5Hmxqa6uf0Ph7uwAA=='
        );
        $requestMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_Sagepaysuite_Request::class)
            ->setMethods(array('getMode', 'getData'))->disableOriginalConstructor()->getMock();
        $requestMock->expects($this->once())->method('getMode')->willReturn(null);
        $requestMock->expects($this->once())->method('getData')->willReturn($postRequest);

        $sagePayNitMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayNit::class)
            ->setMethodsExcept(array('_postRequest'))->disableOriginalConstructor()->getMock();

        $sagePayNitMock->expects($this->once())->method('requestPost')->willThrowException(new Exception("msg", 1));

        $sagePayNitMock->_postRequest($requestMock);

    }
}
