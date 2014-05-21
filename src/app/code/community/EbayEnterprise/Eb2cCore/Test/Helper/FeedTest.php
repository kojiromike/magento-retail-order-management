<?php
class EbayEnterprise_Eb2cCore_Test_Helper_FeedTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const FEED_PATH = '/FeedTest/fixtures/';
	private function _getDoc($feed)
	{
		$d = Mage::helper('eb2ccore')->getNewDomDocument();
		$d->load(__DIR__ . self::FEED_PATH . $feed . '.xml');
		return $d;
	}
	public function providerValidateHeader()
	{
		return array(
			array('bad-event-feed'),
			array('good-feed'),
			array('no-event-feed'),
		);
	}

	/**
	 * @dataProvider dataProvider
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testGetMessageDate($feedDir)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('var/eb2c/' . $feedDir . '/feed.xml');
		$testModel = Mage::helper('eb2ccore/feed');
		$e = $this->expected($feedDir);
		$dateObj = $testModel->getMessageDate($url);
		$this->assertNotNull($dateObj);
		$this->assertSame(
			$e->getDate(),
			$dateObj->format('Y-m-d H:i:s')
		);
	}

	/**
	 * @dataProvider dataProvider
	 * @loadFixture
	 */
	public function testGetMessageDateFail($feedDir)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('var/eb2c/' . $feedDir . '/feed.xml');
		$testModel = Mage::helper('eb2ccore/feed');
		$dateObj = $testModel->getMessageDate($url);
		$this->assertNotNull($dateObj);
		$this->assertSame(filemtime($url), $dateObj->getTimeStamp());
	}

	/**
	 * validateHeader should be true when the header has the expected DestinationId and Event Type nodes.
	 * @test
	 * @dataProvider providerValidateHeader
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeader($feed)
	{
		$doc = $this->_getDoc($feed);
		$isValid = $this->expected($feed)->getIsValid();
		$this->assertSame($isValid, Mage::helper('eb2ccore/feed')->validateHeader($doc, 'GoodEvent'));
	}

	/**
	 * @test
	 */
	public function testFileTransferDefaultIsSftp()
	{
		$hlpr = Mage::helper('eb2ccore/feed');
		$configData = Mage::helper('filetransfer')
			->getInitData($hlpr::FILETRANSFER_CONFIG_PATH);
		$this->assertSame('sftp', $configData['protocol_code']);
	}

	/**
	 * Test getHeaderConfig method
	 * @loadExpectation
	 */
	public function testGetHeaderConfig()
	{
		$cfgData = array('ItemMaster' => 'eb2cproduct/item_master_feed/outbound/message_header');
		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_doConfigTranslation', '_getEventTypeToHeaderConfigPath'))
			->getMock();

		$configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
		foreach (array(0, 1) as $idx) {
			$at = $idx;
			$pccd = $this->expected('pccd_' . $at);
			$mData = $pccd->getData();
			$configRegistryMock->expects($this->at($idx))
				->method('getConfigData')
				->with($this->equalTo($mData['with']))
				->will($this->returnValue($mData['will']));
		}
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

		$dct = $this->expected('dct');
		$feedHelperMock->expects($this->any())
			->method('_doConfigTranslation')
			->with($this->isType('array'))
			->will($this->returnValue($dct['will']));
		$feedHelperMock->expects($this->any())
			->method('_getEventTypeToHeaderConfigPath')
			->will($this->returnValue($cfgData));

		$this->replaceByMock('helper', 'eb2ccore/feed', $feedHelperMock);
		$testData = array(
			array(
				'expect' => array(),
				'feedType' => 'unknown'
			),
			array(
				'expect' => $dct['will'],
				'feedType' => 'ItemMaster'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $feedHelperMock->getHeaderConfig($data['feedType']));
		}
	}

	/**
	 * Test getFileNameConfig method
	 * @loadExpectation
	 */
	public function testGetFileNameConfig()
	{
		$configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
		$pccd = $this->expected('pccd');
		$configRegistryMock->expects($this->once())
			->method('getConfigData')
			->with($this->equalTo($pccd['with']))
			->will($this->returnValue($pccd['will']));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('_doConfigTranslation'))
			->getMock();
		$dct = $this->expected('dct');
		$feedHelperMock->expects($this->once())
			->method('_doConfigTranslation')
			->with($this->isType('array'))
			->will($this->returnValue($dct['will']));

		$testData = array(
			array(
				'expect' => $dct['will'],
				'feedType' => 'ItemMaster'
			),
		);

		foreach ($testData as $data) {
			$this->assertSame($data['expect'], $feedHelperMock->getFileNameConfig($data['feedType']));
		}
	}

	/**
	 * Test _doConfigTranslation method with following expectations
	 * Testing when this test invoked doConfigTranslation it will call the configuration
	 * callback methods and build the key value array
	 */
	public function testDoConfigTranslation()
	{
		$return = array('standard' => 'GSI', 'source_id' => 'ABCD');
		// when the value is string return static value and when
		// the value is an array it will get pass to invoke callback
		$map = array('standard' => 'GSI', 'source_id' => array('type' => 'helper'));

		$feedHelperMock = $this->getHelperMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('invokeCallback'))
			->getMock();
		$feedHelperMock->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($map['source_id']))
			->will($this->returnValue('ABCD'));

		$this->assertSame(
			$return,
			$this->_reflectMethod($feedHelperMock, '_doConfigTranslation')->invoke($feedHelperMock, $map)
		);
	}
	/**
	 * Test invokeCallback method
	 * @loadExpectation
	 * @dataProvider dataProvider
	 */
	public function testInvokeCallback($provider)
	{
		$iteration = $provider['iteration'];
		$meta = $provider['meta'];
		$expect = $this->expected('expect')->getData($iteration['name']);
		if ($expect === '') {
			$expect = null;
		}
		$mockMethod = (trim($iteration['mock_method']) === '')? array() : array($iteration['mock_method']);

		if (!empty($mockMethod)) {
			switch ($iteration['mock_type']) {
				case 'helper':
					$mock = $this->getHelperMockBuilder($iteration['mock_class'])
						->disableOriginalConstructor()
						->setMethods($mockMethod)
						->getMock();
					break;
				default:
					$mock = $this->getModelMockBuilder($iteration['mock_class'])
						->disableOriginalConstructor()
						->setMethods($mockMethod)
						->getMock();
					break;
			}

			$mock->expects($this->once())
				->method($iteration['mock_method'])
				->will($this->returnValue($iteration['mock_return']));

			$this->replaceByMock($iteration['mock_type'], $iteration['mock_class'], $mock);
		}

		$helper = Mage::helper('eb2ccore/feed');
		$this->assertSame($expect, $this->_reflectMethod($helper, 'invokeCallback')->invoke($helper, $meta));
	}

	/**
	 * Test getStoreId method
	 * @test
	 */
	public function testGetStoreId()
	{
		$storeId = 'ABCD';
		$helperMock = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'storeId' => $storeId
			))));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$this->assertSame($storeId, Mage::helper('eb2ccore/feed')->getStoreId());
	}

	/**
	 * Test getClientId method
	 * @test
	 */
	public function testGetClientId()
	{
		$clientId = '1234';
		$helperMock = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$helperMock->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'clientId' => $clientId
			))));
		$this->replaceByMock('helper', 'eb2ccore', $helperMock);

		$this->assertSame($clientId, Mage::helper('eb2ccore/feed')->getClientId());
	}

	/**
	 * Test getMessageId method
	 * As per the XSD, the message id has to be <= 20 characters long
	 * @test
	 */
	public function testGetMessageId()
	{
		$this->assertLessThanOrEqual(20, strlen(Mage::helper('eb2ccore/feed')->getMessageId()));
	}

	/**
	 * Test getCorrelationId method
	 * As per the XSD, the correlation id has to be <= 20 characters long
	 * @test
	 */
	public function testGetCorrelationId()
	{
		$this->assertLessThanOrEqual(20, strlen(Mage::helper('eb2ccore/feed')->getCorrelationId()));
	}
	/**
	 * Test that EbayEnterprise_Eb2cCore_Helper_Feed::_getEventTypeToHeaderConfigPath
	 * will return an array of key eventtype map to the config path of a message
	 * header
	 * @test
	 */
	public function testGetEventTypeToHeaderConfigPath()
	{
		$outboundKey = EbayEnterprise_Eb2cCore_Helper_Feed::KEY_OUTBOUND;
		$eventKey = EbayEnterprise_Eb2cCore_Helper_Feed::KEY_EVENT_TYPE;
		$importPath = EbayEnterprise_Eb2cCore_Helper_Feed::IMPORT_CONFIG_PATH;
		$importKey = 'ItemMaster';
		$importData = array('item_master' => array(
			$outboundKey => '',
			$eventKey => $importKey
		));
		$exportPath = EbayEnterprise_Eb2cCore_Helper_Feed::EXPORT_CONFIG_PATH;
		$exportKey = 'ImageMaster';
		$exportData = array('image_master' => array(
			$outboundKey => '',
			$eventKey => $exportKey
		));
		$headerMap = array(
			$importKey => $importPath . '/item_master/outbound/message_header',
			$exportKey => $exportPath . '/image_master/outbound/message_header'
		);

		$configRegistryMock = $this->getModelMock('eb2ccore/config_registry', array('getConfigData'));
		$configRegistryMock->expects($this->exactly(2))
			->method('getConfigData')
			->will($this->returnValueMap(array(
				array($importPath, $importData),
				array($exportPath, $exportData)
			)));
		$this->replaceByMock('model', 'eb2ccore/config_registry', $configRegistryMock);

		$feed = Mage::helper('eb2ccore/feed');

		$this->assertSame($headerMap, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$feed, '_getEventTypeToHeaderConfigPath', array()
		));
	}
}
