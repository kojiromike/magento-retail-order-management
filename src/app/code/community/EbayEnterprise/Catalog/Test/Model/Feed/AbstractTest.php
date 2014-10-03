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

/**
 * Feed Abstract Test
 *
 */
class EbayEnterprise_Catalog_Test_Model_Feed_AbstractTest extends EbayEnterprise_Eb2cCore_Test_Base
{
	public function setUp()
	{
		parent::setUp();
		Mage::app()->disableEvents();
	}
	public function tearDown()
	{
		parent::tearDown();
		Mage::app()->enableEvents();
	}
	/**
	 * Provide sets of initial data for the abstract feed model to have while
	 * running _construct and whether that data set contains all necessary data
	 * for the abstract feed model
	 * @return array Args array for testConstructor
	 */
	public function provideConstructorInitialData()
	{
		return array(
			array(array('feed_config' => array('event_type' => 'SomeEvent')), true),
			array(array('not_feed_config' => 'owl'), false)
		);
	}
	/**
	 * Test the _construct method. When valid, should result in a model with
	 * a _coreFeed property. When invalid, should throw an exception.
	 * @param  array   $initialData
	 * @param  bool $isValid
	 * @dataProvider provideConstructorInitialData
	 */
	public function testConstructor($initialData, $isValid)
	{
		$coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core')
			->disableOriginalConstructor()->getMock();
		$this->replaceByMock('model', 'ebayenterprise_catalog/feed_core', $coreFeed);

		$feed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('processDom'))
			->getMock();
		$feed->setData($initialData);

		if (!$isValid) {
			$this->setExpectedException('EbayEnterprise_Eb2cCore_Exception_Feed_Configuration');
		}

		EcomDev_Utils_Reflection::invokeRestrictedMethod($feed, '_construct');

		if ($isValid) {
			$this->assertSame(
				$coreFeed,
				EcomDev_Utils_Reflection::getRestrictedPropertyValue($feed, '_coreFeed')
			);
		}
	}
	/**
	 * Process a single file - load the file into a new DOM document and validate
	 * the file header. If loading and validation are successful, process the dom.
	 */
	public function testProcessFile()
	{
		$coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core')
			->disableOriginalConstructor()
			->setMethods(array('mvToProcessingDirectory', 'mvToImportArchive', 'acknowledgeReceipt'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('_loadDom'))
			->getMock();
		$feedDom = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array())
			->getMock();

		$localFile = '/Mage/var/local/file.xml';
		$processingFile = '/Mage/var/processing/file.xml';
		$archiveFile = '/Mage/var/archive/file.xml';
		// initial file details - local_file in "local" directory
		$fileDetails = array('local_file' => $localFile, 'core_feed' => $coreFeed);

		$coreFeed->expects($this->once())
			->method('acknowledgeReceipt')
			->with($this->identicalTo($localFile))
			->will($this->returnSelf());
		$coreFeed->expects($this->once())
			->method('mvToProcessingDirectory')
			->with($this->identicalTo($localFile))
			->will($this->returnValue($processingFile));
		$coreFeed->expects($this->once())
			->method('mvToImportArchive')
			->with($this->identicalTo($processingFile))
			->will($this->returnValue($archiveFile));

		$abstractFeed->expects($this->once())
			->method('_loadDom')
			->with($this->identicalTo($fileDetails))
			->will($this->returnValue($feedDom));

		$this->assertSame($abstractFeed, $abstractFeed->processFile($fileDetails));
	}
	/**
	 * When a dom for the feed cannot be successfully loaded (DOM::load and validate)
	 * do not attempt to process the DOM but ensure the file is still archived.
	 */
	public function testProcessFileDomLoadFails()
	{
		$coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core')
			->disableOriginalConstructor()
			->setMethods(array('mvToProcessingDirectory', 'mvToImportArchive', 'acknowledgeReceipt'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('_loadDom', 'processDom'))
			->getMock();

		$localFile = '/Mage/var/local/file.xml';
		$processingFile = '/Mage/var/processing/file.xml';
		$archiveFile = '/Mage/var/archive/file.xml';
		$fileDetails = array('local_file' => $localFile, 'core_feed' => $coreFeed);

		$abstractFeed->expects($this->once())
			->method('_loadDom')
			->with($this->identicalTo($fileDetails))
			->will($this->returnValue(null));

		$coreFeed->expects($this->never())
			->method('mvToProcessingDirectory')
			->will($this->returnValue($processingFile));
		$coreFeed->expects($this->never())
			->method('acknowledgeReceipt')
			->will($this->returnSelf());
		$coreFeed->expects($this->once())
			->method('mvToImportArchive')
			->with($this->identicalTo($localFile))
			->will($this->returnValue($archiveFile));
		$abstractFeed->expects($this->never())
			->method('processDom');

		$this->assertSame($abstractFeed, $abstractFeed->processFile($fileDetails));
	}
	/**
	 * Test loading a validating a file as a DOM Document. When loaded and
	 * validated successfully, the loaded DOM Document should be returned.
	 */
	public function testLoadDomSuccess()
	{
		$coreFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_core')
			->disableOriginalConstructor()
			->setMethods(array('getEventType'))
			->getMock();
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getNewDomDocument'));
		$coreFeedHelper = $this->getHelperMock('ebayenterprise_catalog/feed', array('validateHeader'));
		$dom = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('ebayenterprise_catalog/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('processDom'))
			->getMock();

		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		$this->replaceByMock('helper', 'ebayenterprise_catalog/feed', $coreFeedHelper);

		$eventType = 'SomeEvent';
		$localFile = '/Mage/var/processing/file.xml';
		$fileDetails = array('local_file' => $localFile, 'core_feed' => $coreFeed);

		$coreFeed->expects($this->once())
			->method('getEventType')
			->will($this->returnValue($eventType));
		$coreHelper->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($dom));
		$dom->expects($this->once())
			->method('load')
			->with($this->identicalTo($localFile))
			->will($this->returnValue(true));
		$coreFeedHelper->expects($this->once())
			->method('validateHeader')
			->with($this->identicalTo($dom), $this->identicalTo($eventType))
			->will($this->returnValue(true));

		$this->assertSame(
			$dom,
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$abstractFeed,
				'_loadDom',
				array($fileDetails)
			)
		);
	}
	public function provideLoadDomFailures()
	{
		return array(
			array(true, false),
			array(false, true),
		);
	}
}
