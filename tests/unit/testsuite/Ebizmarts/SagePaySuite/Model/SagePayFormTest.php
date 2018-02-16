<?php

class Ebizmarts_SagePaySuite_Model_SagePayFormTest extends PHPUnit_Framework_TestCase
{
    const FORM_TEST_ENCRYPTION_PASSWORD = "4BMxx5kDvDshzS6Q";

    public function testEncrypt()
    {
        $formMock = $this->makeFormMock();

        $payload = $this->getEncryptPayload();

        $encrypted = $this->getExpectedEncrypted();

        $this->assertEquals($encrypted, $formMock->encrypt($payload, self::FORM_TEST_ENCRYPTION_PASSWORD));
    }

    public function testDecrypt()
    {
        $formMock = $this->makeFormMock();
        $formMock->expects($this->once())->method("getEncryptionPass")->willReturn(self::FORM_TEST_ENCRYPTION_PASSWORD);

        $crypted = $this->getDecryptedPayload();

        $decrypted = $this->getDecryptExpectedResult();

        $this->assertEquals($decrypted, $formMock->decrypt($crypted));
    }

    /**
     * @return Ebizmarts_SagePaySuite_Model_SagePayForm|PHPUnit_Framework_MockObject_MockObject
     */
    private function makeFormMock()
    {
        /** @var Ebizmarts_SagePaySuite_Model_SagePayForm|PHPUnit_Framework_MockObject_MockObject $formMock */
        $formMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Model_SagePayForm::class)
            ->setMethods(array('getEncryptionPass'))
            ->getMock();

        return $formMock;
    }

    /**
     * @return string
     */
    private function getEncryptPayload()
    {
        $payload = "VendorTxCode=000000015-2017-08-15-1618181502813898&Description=Online transaction.&";
        $payload .= "ReferrerID=01bf51f9-0dcd-49dd-a07a-3b1f918c77d7&Basket=3:[MSH02-33-Black] ApolloRunningShort:1:42.22:0.000:42.22:42.22:";
        $payload .= "[24-WB07] OvernightDuffle:1:58.46:0.000:58.46:58.46:FlatRate-Fixed:1:0:0:0:0&";
        $payload .= "SuccessURL=http://2.1.7-ee.local/sagepaysuite/form/success/_store/1/quoteid/14/&FailureURL=http://2.1.7-ee";
        $payload .= ".local/sagepaysuite/form/failure/_store/1/quoteid/14/&SendEMail=1&Amount=100.68&Currency=USD&";
        $payload .= "CustomerEMail=roni_cost@example.com&BillingSurname=Costello&BillingFirstnames=Veronica&";
        $payload .= "BillingAddress1=Street 1234&BillingAddress2=NE 20900&BillingCity=Miami&BillingPostCode=33143&";
        $payload .= "BillingCountry=US&BillingState=FL&BillingPhone=3057864938&DeliverySurname=Costello&";
        $payload .= "DeliveryFirstnames=Veronica&DeliveryAddress1=Street 1234&DeliveryAddress2=NE 20900&DeliveryCity=Miami&";
        $payload .= "DeliveryPostCode=33143&DeliveryCountry=US&DeliveryState=FL&";
        $payload .= "DeliveryPhone=3057864938&CardHolder=Veronica Costello&Apply3DSecure=0&ApplyAVSCV2=0";

        return $payload;
    }

    /**
     * @return string
     */
    private function getExpectedEncrypted()
    {
        $encrypted = "@77A9F5FB9CBFC11C6F3D5D6B424C7E8411EF2AC42C3EF9B639D38A1932D24A7D9E0A6F3818C338503359FDB86DA427555C6A5CE30";
        $encrypted .= "54EADE54B6B82EE7364F4E579BDBA5BF905FA142E9EE12DA32D29EA87BD42212DA53BC12886F7557AF5FD78F42D7311732C07F7EFFD714";
        $encrypted .= "ACEE278DBE1BDBECFA0B6DEA5777795A5F0BB2EDEE974CBED6AC4B7C4576B10A8FE2762C96ECED7FE5DDB49847000E3B985DF51C08027D28";
        $encrypted .= "3DD6C210FCC824FC2A200E9115A0D9AF4F0046D10B80B4A972256CEC94785D70852AF9B93CF16675E48A3B1103D189CB3B3BBC3104174067";
        $encrypted .= "166F132B8FD111A518403AD746AE507C3D9E30F83D77C26D3FFF771236A2C07BBA067E4368A30E64DE9E46BEC80DFF4A4068CED3C14E60";
        $encrypted .= "8E353F1E22DA7365C011085FED6D3C7114E7980171EEFFCCA82DFCE1795F9890CCBEE6F4CD18EBCD97DCA291E39B32F9BC90345410D04";
        $encrypted .= "1B20E330DABC1EB1E7639B69CDBC64F6656CFF332F4684FD0AF803426175D8021366D3AA6E1FE7DA0ADE76DA73A8E05F148FAF24A87CC09";
        $encrypted .= "5EDBF0DC3B98C7A89B15919DE54693A6EEC0A7FFB748F42C6A710E463DD95F360CB433DCE2FFB57A2EF44EC98DC5E93800814A5F17D9BCA";
        $encrypted .= "F7D8699F403E6DBD5C01B435D7DE373A2FCC5A91B27B43EB981C73EAF7ABB7EF611888D53047676DCFC04A6680B5A89CD05AD0A71A";
        $encrypted .= "FF09CF58C406272C440D1400A4EB325F2DD98225ABDD40B9E3B9AACA32CBEB0112F359656BDF2B2CABB9DBB2FF01F477944BEBFD";
        $encrypted .= "EC68B095450BE691F4EB66CDCF12FFD4CFC70B79E38642F836328459B42D4B106F453B9735BCEBE593EE3094252DED1E2FD8";
        $encrypted .= "1E9CEB89E72250A30088663D90D6879BAEE456CF641BB7127A18DF544E1CC74540E681221140B9E61A5CABC5964863747CBAAD";
        $encrypted .= "96A96D8AAD1C91FECFD8E812180B67FDCE3AEAD3CA140EA61B577DD2A919935237CCB47B369C26C47249A630850B2E252364E009FFA7A";
        $encrypted .= "AD53EB12985D4D3DDAE4575CFE7A5C72355D375427505F75315EE8874BDF501D0EB40C46B52270F6A4E90FFF65FD1A9AE1C3C8C9629EFD";
        $encrypted .= "E3EB44B9B46141CAB2227DDCDFBA8573A81F0F780C738DF7BD51AEFBB966EE8F2780D10A9816D7ED0DB690749C096DCECC256FCA7883D34";
        $encrypted .= "CBFAEF389F8305477C87F8C33BDAC80F5016D3D5F2FDC292D2FC87E90CA31C7BBC460D5ED24C38E443C06E75028B2FC38A34CD9833D92065";
        $encrypted .= "814109AD3B0F1A8B442D00459FF449767CB99CB2CDDF356DAA3233838A7697F4BD66E029EB74647255EFBE5A684D27D92F845437B415099";
        $encrypted .= "01564467B7C0FB596A400EAB79D3E456DBB8E815242F04D3F57133ABFD99394FE8671282630CE15D68934F13D4FB0087D508F043E4278D13";
        $encrypted .= "07DA01C1EAA9D2E03AAA916D989";

        return $encrypted;
    }

    /**
     * @return string
     */
    private function getDecryptedPayload()
    {
        $crypted = "@77a9f5fb9cbfc11c6f3d5d6b424c7e84fcbb229205131a505b37a45babe660b8aaf0cbc8dde33a8c0fc5376703d97ec796278";
        $crypted .= "c95a2a866731a40b506d3fd8908be48341598ff5430e2239914dc25e6c229d54d5795d294ad94aa9c14274db";
        $crypted .= "83f951c6f6d1d5a602220b462b2cab76490bbea229860b5f0ceb90823c6bdc5e1025505762f353a3841ca4126e1d416e2";
        $crypted .= "f00675267a67b3d606e5de42832dc1bd9a628e521084f4c7cd69442bfd2b16d7cca19d8ab953ac6965ccba3d31f20751595f2";
        $crypted .= "6f6e151bf53e2047d7a5a37066fe48217f41fc688b4a92743e7f644d391bc08b4b0d94219eb069f20db85cd855f43d806697fac24ca8";
        $crypted .= "ea32584cd7b3a733f3d7db0623cc511be904c979c37075f6b21ab35393d2bbfcf9b6d762acfb3816f615093ea3a1f61111787718aa";
        $crypted .= "f82181d495a67d74d7f2851f3c753fd8f9392e316f8e041c24498bfe0bfb54e1240feb0ae8626ade24b9f958f52ab2ead3ff652919";
        $crypted .= "02375a173a2b723fb35d9ac58dcce8f6a58f11e84d2a2cd4d691d810a33535a0b53f6b457f5b3a191b4ad60cd5b089fb14137d398035";
        $crypted .= "da6cd45fb08a7467852768e80e8ab476f8e34cfbd698966592f642675089e8dec5da71722884870f8";

        return $crypted;
    }

    /**
     * @return string
     */
    private function getDecryptExpectedResult()
    {
        $decrypted = "VendorTxCode=000000013-2017-08-15-1544041502811844&VPSTxId={3FB9711D-EED0-44FA-2727-869C38A5D832}&";
        $decrypted .= "Status=OK&StatusDetail=0000 : The Authorisation was Successful.&";
        $decrypted .= "TxAuthNo=15779007&AVSCV2=SECURITY CODE MATCH ONLY&AddressResult=NOTMATCHED&";
        $decrypted .= "PostCodeResult=NOTMATCHED&CV2Result=MATCHED&GiftAid=0&3DSecureStatus=OK&";
        $decrypted .= "CAVV=AAABARR5kwAAAAAAAAAAAAAAAAA=&CardType=VISA&Last4Digits=0006&DeclineCode=00&";
        $decrypted .= "ExpiryDate=1219&Amount=100.68&BankAuthCode=999777";

        return $decrypted;
    }
}