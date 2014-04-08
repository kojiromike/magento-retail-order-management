<?php
class EbayEnterprise_Eb2cProduct_Test_Model_Image_ExportTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Image_Export::process method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Model_Image_Export::process given a
	 *                a null value as the sourceStoreId parameter and expect the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Data::getStores to be called and run an array of stores
	 *                entity_id key values then expect the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_buildExport
	 *                to be invoked given the initial call and the store id
	 */
	public function testProccess()
	{
		$storeId = 5;
		$stores = array($storeId => Mage::getModel('core/store'));

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getStores'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getStores')
			->will($this->returnValue($stores));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$exportMock = $this->getModelMockBuilder('eb2cproduct/image_export')
			->disableOriginalConstructor()
			->setMethods(array('_buildExport'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_buildExport')
			->with($this->identicalTo($storeId))
			->will($this->returnSelf());

		$exportMock->process();
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Image_Export::_buildExport method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_buildExport
	 *                an value of zero for the processed parameter and an integer value for the store id parameter
	 *                expects the following things to happen: the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_getImageData
	 *                is expected to be invoked a given the given store id which will return an array of product image data
	 *                then the method EbayEnterprise_Eb2cProduct_Helper_Data::setCurrentStore given the stored id, next
	 *                the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_loadDom is invoked given the storeId returned
	 *                an EbayEnterprise_Dom_Document object, then the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_buildItemImages
	 *                is called given the return value from the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_loadDom
	 *                as first parameter, the given store id as second parameter and last the product image data, then
	 *                the method EbayEnterprise_Eb2cProduct_Helper_Data::setCurrentStore get invoked a second time given
	 *                the constant value of class Mage_Core_Model_App::ADMIN_STORE_ID
	 */
	public function testBuildExport()
	{
		$storeId = 5;
		$imageData = array(array());
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('setCurrentStore'))
			->getMock();
		$helperMock->expects($this->exactly(2))
			->method('setCurrentStore')
			->will($this->returnValueMap(array(
				array($storeId, $helperMock),
				array(Mage_Core_Model_App::ADMIN_STORE_ID)
			)));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$exportMock = $this->getModelMockBuilder('eb2cproduct/image_export')
			->disableOriginalConstructor()
			->setMethods(array('_loadDom', '_getImageData', '_buildItemImages'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_loadDom')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($doc));
		$exportMock->expects($this->once())
			->method('_getImageData')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($imageData));
		$exportMock->expects($this->once())
			->method('_buildItemImages')
			->with($this->identicalTo($doc), $this->identicalTo($storeId), $imageData)
			->will($this->returnValue(count($imageData)));

		$this->assertSame($exportMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_buildExport', array($storeId)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Image_Export::_loadDom method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_loadDom will be invoked by this test
	 *                given a known processed value and a known store id value in which the method
	 *                EbayEnterprise_Eb2cProduct_Helper_Data::getConfigModel will be called and return a mocked
	 *                EbayEnterprise_Eb2cCore_Model_Config_Registry object, then the method
	 *                EbayEnterprise_Eb2cCore_Helper_Data::getNewDomDocument will be invoked and return mocked
	 *                EbayEnterprise_Dom_Document object, then the method EbayEnterprise_Dom_Document::loadXml get invoked
	 *                the value generated by the sprintf method in which take value from class constant, return value from
	 *                the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_getCurrentHostName given the store id
	 *                then value from EbayEnterprise_Eb2cProduct_Helper_Data::generateMessageHeader given the event type
	 *                value from the EbayEnterprise_Eb2cCore_Model_Config_Registry object, last the method
	 *                EbayEnterprise_Eb2cCore_Helper_Data::getBaseUrl get called given the Mage_Core_Model_Store::URL_TYPE_MEDIA
	 *                class constant
	 */
	public function testLoadDom()
	{
		$storeId = 3;
		$cfg = array('clientId' => 'WNS', 'imageFeedEventType' => 'ImageMaster');
		$messageHeader = '<MessageHeader>...</MessageHeader>';
		$host = 'example.com';
		$dateTime = '2014-04-07T09:09:14+00:00';
		$mediaUrl = '';
		$initialXml = sprintf(
			EbayEnterprise_Eb2cProduct_Model_Image_Export::XML_TEMPLATE,
			EbayEnterprise_Eb2cProduct_Model_Image_Export::ROOT_NODE,
			$host,
			$cfg['clientId'],
			$dateTime,
			$messageHeader,
			$mediaUrl
		);

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'generateMessageHeader'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry($cfg)));
		$helperMock->expects($this->once())
			->method('generateMessageHeader')
			->with($this->identicalTo($cfg['imageFeedEventType']))
			->will($this->returnValue($messageHeader));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('loadXml'))
			->getMock();
		$docMock->expects($this->once())
			->method('loadXml')
			->with($this->identicalTo($initialXml))
			->will($this->returnValue(true)); // successfully loaded

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomDocument', 'getBaseUrl'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($docMock));
		$coreHelperMock->expects($this->once())
			->method('getBaseUrl')
			->with($this->identicalTo(Mage_Core_Model_Store::URL_TYPE_MEDIA))
			->will($this->returnValue($mediaUrl));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$dateMock = $this->getModelMockBuilder('core/date')
			->disableOriginalConstructor()
			->setMethods(array('date'))
			->getMock();
		$dateMock->expects($this->once())
			->method('date')
			->with($this->identicalTo('c'))
			->will($this->returnValue($dateTime));
		$this->replaceByMock('model', 'core/date', $dateMock);

		$exportMock = $this->getModelMockBuilder('eb2cproduct/image_export')
			->disableOriginalConstructor()
			->setMethods(array('_getCurrentHostName'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_getCurrentHostName')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($host));

		$this->assertSame($docMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_loadDom', array($storeId)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Image_Export::_getCurrentHostName method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_getCurrentHostName will be invoked by
	 *                this test given a store id and expect the method EbayEnterprise_Eb2cProduct_Helper_Data::getStoreUrl
	 *                to be invoked given the store id and will return a known URL which will be passed to the built-in
	 *                parse_url method which will return an array contain the key 'host' which will be the return value
	 *                the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_getCurrentHostName
	 */
	public function testGetCurrentHostName()
	{
		$storeId = 8;
		$host = 'example.com';
		$storeUrl = "http://$host";

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getStoreUrl'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getStoreUrl')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($storeUrl));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$exportMock = $this->getModelMockBuilder('eb2cproduct/image_export')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($host, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_getCurrentHostName', array($storeId)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Image_Export::_createFileFromDom method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_createFileFromDom will be invoked by this
	 *                test given a mock EbayEnterprise_Dom_Document object and a store id in which expect the method
	 *                EbayEnterprise_Eb2cProduct_Model_Image_Export::_generateFilePath to be called given the store id
	 *                which will return a known string, this know string value will be passed to the method
	 *                EbayEnterprise_Dom_Document::save method and the string then be returned
	 */
	public function testCreateFileFromDom()
	{
		$storeId = 9;
		$filename = 'path/to/out-box/some-file-name.xml';
		$nBytes = 10;

		$docMock = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('save'))
			->getMock();
		$docMock->expects($this->once())
			->method('save')
			->with($this->identicalTo($filename))
			->will($this->returnValue($nBytes)); // the number of bytes written the save xml file

		$exportMock = $this->getModelMockBuilder('eb2cproduct/image_export')
			->disableOriginalConstructor()
			->setMethods(array('_generateFilePath'))
			->getMock();
		$exportMock->expects($this->once())
			->method('_generateFilePath')
			->with($this->identicalTo($storeId))
			->will($this->returnValue($filename));

		$this->assertSame($filename, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_createFileFromDom', array($docMock, $storeId)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Image_Export::_generateFilePath method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Image_Export::_generateFilePath will be invoked by this
	 *                test given a store id and expect the method EbayEnterprise_Eb2cProduct_Model_Image_Export::getConfigModel
	 *                to be called a return a mock EbayEnterprise_Eb2cCore_Model_Config_Registry object in which the magic
	 *                configuration properties will be called to instantiate EbayEnterprise_Eb2cCore_Model_Feed object and to
	 *                pass as parameters to the method EbayEnterprise_Eb2cProduct_Helper_Data::generateFileName
	 */
	public function testGenerateFilePath()
	{
		$storeId = 7;
		$idPlaceHolder = EbayEnterprise_Eb2cProduct_Model_Image_Export::ID_PLACE_HOLDER;
		$cfg = array(
			'imageFeed' => 'path/to/image/configuration',
			'imageFeedEventType' => 'ImageMaster',
			'imageExportFilenameFormat' => "{event_type}_{client_id}_$idPlaceHolder.xml"
		);

		$localDir = 'path/to/out-box';

		$filename = $cfg['imageFeedEventType'] . "_ABCD_$idPlaceHolder.xml";

		$result = $localDir . DS . $cfg['imageFeedEventType'] . "_ABCD_$storeId.xml";

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('getConfigModel', 'generateFileName'))
			->getMock();
		$helperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry($cfg)));
		$helperMock->expects($this->once())
			->method('generateFileName')
			->with($this->identicalTo($cfg['imageFeedEventType']), $this->identicalTo($cfg['imageExportFilenameFormat']))
			->will($this->returnValue($filename));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$feedMock = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getLocalDirectory'))
			->getMock();
		$feedMock->expects($this->once())
			->method('getLocalDirectory')
			->will($this->returnValue($localDir));
		$this->replaceByMock('model', 'eb2ccore/feed', $feedMock);

		$exportMock = $this->getModelMockBuilder('eb2cproduct/image_export')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$exportMock, '_generateFilePath', array($storeId)
		));
	}

}
