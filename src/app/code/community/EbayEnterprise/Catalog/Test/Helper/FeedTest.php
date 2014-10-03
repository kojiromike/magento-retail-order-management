<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Catalog_Test_Helper_FeedTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	const FEED_PATH = '/FeedTest/fixtures/';
	// Time zone tests can expect to be the default timezone while running
	const EXPECTED_TIME_ZONE = 'GMT';
	/**
	 * @var string time zone configured by the environment
	 */
	protected $_initTimeZone;
	/**
	 * Stash the initial default timezone and reset the default timezone
	 * to the one expected in tests.
	 */
	public function setUp()
	{
		$this->_initTimeZone = date_default_timezone_get();
		date_default_timezone_set(self::EXPECTED_TIME_ZONE);
	}
	/**
	 * Reset the default timezone to the initial timezone.
	 */
	public function tearDown()
	{
		date_default_timezone_set($this->_initTimeZone);
	}

	/**
	 * Get an EbayEnterprise_DomDocument for the given feed relative
	 * to a constant fixture path.
	 * @param  string $feed
	 * @return EbayEnterprise_DomDocument
	 */
	private function _getDoc($feed)
	{
		$d = Mage::helper('eb2ccore')->getNewDomDocument();
		$d->load(__DIR__ . self::FEED_PATH . $feed . '.xml');
		return $d;
	}
	/**
	 * Provide XML fixture file names for testing validating message headers.
	 * @return array
	 */
	public function providerValidateHeader()
	{
		return array(
			array('bad-event-feed'),
			array('good-feed'),
			array('no-event-feed'),
		);
	}

	/**
	 * Test getting the date time of the XML message via the
	 * CreateDateAndTime element in the XML.
	 * @param string $feedDir vfs directory setup in the fixture
	 * @dataProvider dataProvider
	 * @loadFixture
	 * @loadExpectation
	 */
	public function testGetMessageDate($feedDir)
	{
		$vfs = $this->getFixture()->getVfs();
		$url = $vfs->url('var/eb2c/' . $feedDir . '/feed.xml');
		$testModel = Mage::helper('ebayenterprise_catalog/feed');
		$e = $this->expected($feedDir);
		$dateObj = $testModel->getMessageDate($url);
		$this->assertNotNull($dateObj);
		$this->assertSame(
			$e->getDate(),
			$dateObj->format('Y-m-d H:i:s')
		);
	}

	/**
	 * Test getting a DateTime object for a CreateDateAndTime from an import feed
	 * @param string|null $feedTime  Create date and time in feed
	 * @param string $dateTimeStamp Expected timestamp
	 * @dataProvider dataProvider
	 */
	public function testGetDateTimeForFeedTime($feedTime, $dateTimeDate)
	{
		$feedHelper = Mage::helper('ebayenterprise_catalog/feed');
		$dateTime = EcomDev_Utils_Reflection::invokeRestrictedMethod($feedHelper, '_getDateTimeForMessage', array($feedTime));
		$this->assertInstanceOf('DateTime', $dateTime, 'Must always return a DateTime object');
		$this->assertSame(
			$dateTimeDate,
			$dateTime->getTimeStamp()
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
		$testModel = Mage::helper('ebayenterprise_catalog/feed');
		$dateObj = $testModel->getMessageDate($url);
		$this->assertNotNull($dateObj);
		$this->assertSame(filemtime($url), $dateObj->getTimeStamp());
	}

	/**
	 * validateHeader should be true when the header has the expected DestinationId and Event Type nodes.
	 * @dataProvider providerValidateHeader
	 * @loadFixture configData.yaml
	 */
	public function testValidateHeader($feed)
	{
		$doc = $this->_getDoc($feed);
		$isValid = $this->expected($feed)->getIsValid();
		$this->assertSame($isValid, Mage::helper('ebayenterprise_catalog/feed')->validateHeader($doc, 'GoodEvent'));
	}

	/**
	 */
	public function testFileTransferDefaultIsSftp()
	{
		$hlpr = Mage::helper('ebayenterprise_catalog/feed');
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
		$cfgData = array('ItemMaster' => 'ebayenterprise_catalog/item_master_feed/outbound/message_header');
		$feedHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/feed')
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

		$this->replaceByMock('helper', 'ebayenterprise_catalog/feed', $feedHelperMock);
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

		$feedHelperMock = $this->getHelperMockBuilder('ebayenterprise_catalog/feed')
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

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore')
			->disableOriginalConstructor()
			->setMethods(array('invokeCallback'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('invokeCallback')
			->with($this->identicalTo($map['source_id']))
			->will($this->returnValue('ABCD'));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);
		$feedHelper = Mage::helper('ebayenterprise_catalog/feed');
		$this->assertSame(
			$return,
			EcomDev_Utils_Reflection::invokeRestrictedMethod($feedHelper, '_doConfigTranslation', array($map))
		);
	}

	/**
	 * Test getStoreId method
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

		$this->assertSame($storeId, Mage::helper('ebayenterprise_catalog/feed')->getStoreId());
	}

	/**
	 * Test getClientId method
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

		$this->assertSame($clientId, Mage::helper('ebayenterprise_catalog/feed')->getClientId());
	}

	/**
	 * Test getMessageId method
	 * As per the XSD, the message id has to be <= 20 characters long
	 */
	public function testGetMessageId()
	{
		$this->assertLessThanOrEqual(20, strlen(Mage::helper('ebayenterprise_catalog/feed')->getMessageId()));
	}

	/**
	 * Test getCorrelationId method
	 * As per the XSD, the correlation id has to be <= 20 characters long
	 */
	public function testGetCorrelationId()
	{
		$this->assertLessThanOrEqual(20, strlen(Mage::helper('ebayenterprise_catalog/feed')->getCorrelationId()));
	}
	/**
	 * Test that EbayEnterprise_Catalog_Helper_Feed::_getEventTypeToHeaderConfigPath
	 * will return an array of key eventtype map to the config path of a message
	 * header
	 */
	public function testGetEventTypeToHeaderConfigPath()
	{
		$outboundKey = EbayEnterprise_Catalog_Helper_Feed::KEY_OUTBOUND;
		$eventKey = EbayEnterprise_Catalog_Helper_Feed::KEY_EVENT_TYPE;
		$importPath = EbayEnterprise_Catalog_Helper_Feed::IMPORT_CONFIG_PATH;
		$importKey = 'ItemMaster';
		$importData = array('item_master' => array(
			$outboundKey => '',
			$eventKey => $importKey
		));
		$exportPath = EbayEnterprise_Catalog_Helper_Feed::EXPORT_CONFIG_PATH;
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

		$feed = Mage::helper('ebayenterprise_catalog/feed');

		$this->assertSame($headerMap, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$feed, '_getEventTypeToHeaderConfigPath', array()
		));
	}
}
