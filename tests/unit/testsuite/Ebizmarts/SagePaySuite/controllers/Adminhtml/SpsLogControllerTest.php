<?php

require_once BP . DS . 'app/code/local/Ebizmarts/SagePaySuite/controllers/Adminhtml/SpsLogController.php';

class Ebizmarts_SagePaySuite_controllers_Adminhtml_SpsLogControllerTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        Mage::app('default');
    }

    /**
     * @param array $directoryIteratorContentDataArray
     * @param array $receives
     * @param array $expected
     * @dataProvider dataProvider
     */
    public function testIsValidLogFile($directoryIteratorContentDataArray, $receives, $expected)
    {

        $itemArray = array();
        foreach ($directoryIteratorContentDataArray as $fileDataArray) {
            $splFileInfoMock = $this->getMockBuilder(SplFileInfo::class)
                ->setMethods(array('isFile', 'getFilename'))->disableOriginalConstructor()->getMock();
            $splFileInfoMock->expects($this->any())->method('isFile')->willReturn($fileDataArray[0]);
            $splFileInfoMock->expects($this->any())->method('getFilename')->willReturn($fileDataArray[1]);

            $itemArray[] = $splFileInfoMock;
        }

        $files = new \ArrayIterator($itemArray);

        $spsLogControllerMock = $this->getMockBuilder(Ebizmarts_SagePaySuite_Adminhtml_SpsLogController::class)
            ->setMethods(array('getLogDirectoryIterator'))->disableOriginalConstructor()->getMock();
        $spsLogControllerMock->expects($this->any())->method('getLogDirectoryIterator')->willReturn($files);

        $this->assertEquals($spsLogControllerMock->isValidLogFile($receives), $expected);

    }

    public function dataProvider()
    {
        return array(
            array(
                array(
                    //array(isFile, fileName),
                    array(true, 'testFile.log'),
                    array(true, 'testFile.txt'),
                    array(false, 'testDir')
                ),
                'testFile.log',
                true),
            array(
                array(
                    array(false, 'testDir')
                ),
                'testDir',
                false),
            array(
                array(
                    array(true, 'testFile.log')
                ),
                'fail.txt',
                false)
        );
    }
}