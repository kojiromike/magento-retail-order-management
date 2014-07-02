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
class EbayEnterprise_Eb2cCore_Test_Model_Feed_AbstractTest extends EbayEnterprise_Eb2cCore_Test_Base
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
	 * @param  boolean $isValid
	 * @dataProvider provideConstructorInitialData
	 */
	public function testConstructor($initialData, $isValid)
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()->getMock();
		$this->replaceByMock('model', 'eb2ccore/feed', $coreFeed);

		$feed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
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
	 * Processing feeds should consist of getting all imported files and for each
	 * file, ack the file, move it to a processing directory, pass an array of
	 * file details (local path, etc.) along to processFile, and move the file
	 * to an archive directory.
	 * @test
	 */
	public function testProcessFeeds()
	{
		$localFile = '/Mage/var/local/file.xml';

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('lsLocalDirectory'))
			->getMock();
		// create a mock of the abstract, including an explicit mock of any abstract
		// methods (processDom) so the abstract class can be instantiated
		$abstractFeed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('processDom', 'processFile'))
			->getMock();

		$abstractFeed->expects($this->once())
			->method('processFile')
			->with($this->identicalTo(
				array('local_file' => $localFile, 'core_feed' => $coreFeed)
			))
			->will($this->returnSelf());

		$coreFeed->expects($this->once())
			->method('lsLocalDirectory')
			->will($this->returnValue(array($localFile)));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($abstractFeed, '_coreFeed', $coreFeed);
		$this->assertSame(1, $abstractFeed->processFeeds());
	}
	/**
	 * When an exception occurs while processing a file, log a warning.
	 * @test
	 */
	public function testProcessFeedsFailure()
	{
		$localFile = '/Mage/var/local/file.xml';

		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('lsLocalDirectory'))
			->getMock();
		// create a mock of the abstract, including an explicit mock of any abstract
		// methods (processDom) so the abstract class can be instantiated
		$abstractFeed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('processDom', 'processFile'))
			->getMock();

		$abstractFeed->expects($this->once())
			->method('processFile')
			->with($this->identicalTo(
				array('local_file' => $localFile, 'core_feed' => $coreFeed)
			))
			->will($this->returnSelf());

		$coreFeed->expects($this->once())
			->method('lsLocalDirectory')
			->will($this->returnValue(array($localFile)));

		$abstractFeed->expects($this->once())
			->method('processFile')
			->with($this->identicalTo(
				array('local_file' => $localFile, 'core_feed' => $coreFeed)
			))
			->will($this->throwException(new Mage_Core_Exception('Fail.')));

		$logger = $this->getHelperMock('ebayenterprise_magelog/data', array('logWarn'));
		$logger->expects($this->once())
			->method('logWarn')
			->with(
				$this->identicalTo('[%s] Failed to process file, %s. %s'),
				$this->identicalTo(array('EbayEnterprise_Eb2cCore_Model_Feed_Abstract', 'file.xml', 'Fail.'))
			)
			->will($this->returnSelf());
		$this->replaceByMock('helper', 'ebayenterprise_magelog', $logger);

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($abstractFeed, '_coreFeed', $coreFeed);
		$this->assertSame(0, $abstractFeed->processFeeds());
	}
	/**
	 * Process a single file - load the file into a new DOM document and validate
	 * the file header. If loading and validation are successful, process the dom.
	 * @test
	 */
	public function testProcessFile()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('mvToProcessingDirectory', 'mvToImportArchive', 'acknowledgeReceipt'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
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
	 * @test
	 */
	public function testProcessFileDomLoadFails()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('mvToProcessingDirectory', 'mvToImportArchive', 'acknowledgeReceipt'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
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
	 * @test
	 */
	public function testLoadDomSuccess()
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getEventType'))
			->getMock();
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getNewDomDocument'));
		$coreFeedHelper = $this->getHelperMock('eb2ccore/feed', array('validateHeader'));
		$dom = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('processDom'))
			->getMock();

		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelper);

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
	/**
	 * Test _loadDom failure scenarios. When either the DOM load or header
	 * validation fails, the _loadDom method should return null.
	 * @param  boolean $loadResult
	 * @param  boolean $validateResult
	 * @test
	 * @dataProvider provideLoadDomFailures
	 */
	public function testLoadDomFailure($loadResult, $validateResult)
	{
		$coreFeed = $this->getModelMockBuilder('eb2ccore/feed')
			->disableOriginalConstructor()
			->setMethods(array('getEventType'))
			->getMock();
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getNewDomDocument'));
		$coreFeedHelper = $this->getHelperMock('eb2ccore/feed', array('validateHeader'));
		$dom = $this->getMockBuilder('EbayEnterprise_Dom_Document')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$abstractFeed = $this->getModelMockBuilder('eb2ccore/feed_abstract')
			->disableOriginalConstructor()
			->setMethods(array('processDom'))
			->getMock();

		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		$this->replaceByMock('helper', 'eb2ccore/feed', $coreFeedHelper);

		$eventType = 'SomeEvent';
		$localFile = '/Mage/var/processing/file.xml';
		$fileDetails = array('local_file' => $localFile, 'core_feed' => $coreFeed);

		$coreHelper->expects($this->once())
			->method('getNewDomDocument')
			->will($this->returnValue($dom));
		$dom->expects($this->any())
			->method('load')
			->with($this->identicalTo($localFile))
			->will($this->returnValue($loadResult));
		$coreFeedHelper->expects($this->any())
			->method('validateHeader')
			->with($this->identicalTo($dom), $this->identicalTo($eventType))
			->will($this->returnValue($validateResult));
		$coreFeed->expects($this->any())
			->method('getEventType')
			->will($this->returnValue($eventType));

		$this->assertNull(
			EcomDev_Utils_Reflection::invokeRestrictedMethod(
				$abstractFeed,
				'_loadDom',
				array($fileDetails)
			)
		);
	}
}
