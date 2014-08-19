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


class EbayEnterprise_Eb2cProduct_Test_Model_Feed_FileTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	/**
	 * Data provider for testing the constructor. Provides the array
	 * of file details and, if expected for the given set of details, the
	 * message for the error triggered.
	 * @return array
	 */
	public function provideConstructorDetailsAndErrors()
	{
		return array(
			array(array('doc' => Mage::helper('eb2ccore')->getNewDomDocument(), 'error_file' => 'error_file.xml'), null),
			array(array('doc' => "this isn't a DOMDocument", 'error_file' => 'error_file.xml'), 'User Error: EbayEnterprise_Eb2cProduct_Model_Feed_File::__construct called with invalid doc. Must be instance of EbayEnterprise_Dom_Document'),
			array(array('wat' => "There's aren't the arguments you're looking for"), 'User Error: EbayEnterprise_Eb2cProduct_Model_Feed_File::__construct called without required feed details: doc, error_file missing.'),
		);
	}
	/**
	 * Test constructing instances - should be invoked with an array of
	 * "feed details". The array of data the model is instantiated with
	 * must contain keys for 'error_file' and 'doc'. When instantiated with a
	 * proper set of file details, the array should be stored on the _feedDetails
	 * property. When given an invalid set of file details, an error should be triggered.
	 * @param array  $fileDetails  Argument to the constructor
	 * @param string $errorMessage Expected error message, if empty, no error is expected
	 * @dataProvider provideConstructorDetailsAndErrors
	 */
	public function testConstruction($fileDetails, $errorMessage)
	{
		if ($errorMessage) {
			// Errors should be getting converted to PHPUnit_Framework_Error but
			// they aren't...instead just getting plain ol' Exceptions...so at least the
			// messages are rather explicit so hopefully no miscaught exceptions by this test.
			$this->setExpectedException('Exception', $errorMessage);
		}

		$feedFile = Mage::getModel('eb2cproduct/feed_file', $fileDetails);

		if (!$errorMessage) {
			$this->assertSame($fileDetails, EcomDev_Utils_Reflection::getRestrictedPropertyValue($feedFile, '_feedDetails'));
		}
	}
	/**
	 * Test processing of the file, which consists for deleting any products
	 * marked for deletion in the feed, processing adds/updates for the default
	 * store view and then processing any translations in the feed.
	 */
	public function testProcess()
	{
		$cfgData = array(
			'feed_type' => 'product'
		);
		$siteFilter = array(array('foo'));

		$config = $this->getModelMock('eb2cproduct/feed_import_config', array('getImportConfigData'));
		$config->expects($this->any())
			->method('getImportConfigData')
			->will($this->returnValue($cfgData));
		$items = Mage::getModel('eb2cproduct/feed_import_items');

		$helper = $this->getHelperMock('eb2cproduct/data', array('loadWebsiteFilters'));
		$helper->expects($this->any())
			->method('loadWebsiteFilters')
			->will($this->returnValue($siteFilter));
		$this->replaceByMock('helper', 'eb2cproduct', $helper);

		// Disable constructor to avoid setting up complex $details argument.
		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_removeItemsFromWebsites', '_processWebsite', '_processTranslations'))
			->getMock();
		// Replace _log property because of the disabled constructor.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($file, '_log', Mage::helper('ebayenterprise_magelog'));
		$file->expects($this->once())
			->method('_removeItemsFromWebsites')
			->will($this->returnSelf());
		$file->expects($this->once())
			->method('_processWebsite')
			->will($this->returnSelf());
		$file->expects($this->once())
			->method('_processTranslations')
			->will($this->returnSelf());

		$this->assertSame($file, $file->process($config, $items));
	}
	/**
	 * Deleting a product should create a product collection of products marked
	 * for deletion in the feed and then call the delete method on the collection.
	 */
	public function testRemoveItemsFromWebsites()
	{
		$cfgData = array();

		$errorConfirmationMock = $this->getModelMockBuilder('eb2cproduct/error_confirmations')
			->disableOriginalConstructor()
			->setMethods(array('processByOperationType'))
			->getMock();
		$errorConfirmationMock->expects($this->once())
			->method('processByOperationType')
			->with($this->isInstanceOf('Varien_Event_Observer'))
			->will($this->returnSelf());
		$this->replaceByMock('model', 'eb2cproduct/error_confirmations', $errorConfirmationMock);

		$skus = array('45-4321', '45-9432');
		$dData = array(
			$skus[0] => array('gsi_client_id' => 'CLIENT1', 'catalog_id' => '45'),
			$skus[1] => array('gsi_client_id' => 'CLIENT1', 'catalog_id' => '45')
		);

		$collectionMock = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('count'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('count')
			->will($this->returnValue(2));

		$items = $this->getModelMock('eb2cproduct/feed_import_items', array('buildCollection'));
		$items->expects($this->once())
			->method('buildCollection')
			->with($this->identicalTo($skus))
			->will($this->returnValue($collectionMock));

		// Disable constructor to avoid setting up complex $details argument.
		$fileModelMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_getSkusToRemoveFromWebsites', '_removeFromWebsites'))
			->getMock();
		// Replace _log property because of the disabled constructor.
		EcomDev_Utils_Reflection::setRestrictedPropertyValue($fileModelMock, '_log', Mage::helper('ebayenterprise_magelog'));
		$fileModelMock->expects($this->once())
			->method('_getSkusToRemoveFromWebsites')
			->will($this->returnValue($dData));
		$fileModelMock->expects($this->once())
			->method('_removeFromWebsites')
			->with($this->identicalTo($collectionMock), $this->identicalTo($dData))
			->will($this->returnSelf());

		$this->assertSame($fileModelMock, EcomDev_Utils_Reflection::invokeRestrictedMethod($fileModelMock, '_removeItemsFromWebsites', array($cfgData, $items)));

		$this->assertEventDispatched('product_feed_process_operation_type_error_confirmation');
	}

	/**
	 * Test _getSkusToRemoveFromWebsites method with the following assumptions when invoked by this test
	 * Expectation 1: this set first set the class property EbayEnterprise_Eb2cProduct_Model_Feed_File::_feedDetails
	 *                to a known state of key value array
	 * Expectation 2: when the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_getSkusToRemoveFromWebsites get invoked by this
	 *                test the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_getDoc will be called once and it
	 *                will return the DOMDocument object, then the method EbayEnterprise_Eb2cProduct_Helper_Data::splitDomByXslt
	 *                will be called with the DOMDocument object and the xslt full path to the delete template file, this method
	 *                will return DOMDocument object with skus to be deleted
	 * Expectation 3: the DOMDocument object will be passed as parameter to the EbayEnterprise_Eb2cCore_Helper_Data::getNewDomXPath
	 *                method which will return the DOMXPath object, with this xpath object the method query the each sku node and
	 *                extract each sku to be deleted into an array of skus
	 * Expectation 4: this array of skus then get return
	 * @mock EbayEnterprise_Eb2cProduct_Model_Feed_File::_getDoc
	 * @mock EbayEnterprise_Eb2cProduct_Helper_Data::splitDomByXslt
	 * @mock EbayEnterprise_Eb2cCore_Helper_Data::getNewDomXPath
	 */
	public function testGetSkusToRemoveFromWebsites()
	{
		$cfgData = array(
			'xslt_deleted_sku' => 'delete-template.xsl',
			'xslt_module' => 'EbayEnterprise_Eb2cProduct',
			'deleted_base_xpath' => 'sku',
		);
		$skus = array('45-4321' , '45-9432');
		$dData = array(
			$skus[0] => array('gsi_client_id' => 'MAGTNA', 'catalog_id' => '45'),
			$skus[1] => array('gsi_client_id' => 'MAGTNA', 'catalog_id' => '45')
		);
		$doc = Mage::helper('eb2ccore')->getNewDomDocument();
		$doc->loadXML(
			'<ItemMaster>
				<Item operation_type="Add" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-1234</ClientItemId>
					</ItemId>
				</Item>
				<Item operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-4321</ClientItemId>
					</ItemId>
				</Item>
				<Item operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">
					<ItemId>
						<ClientItemId>45-9432</ClientItemId>
					</ItemId>
				</Item>
			</ItemMaster>'
		);

		$dlDoc = Mage::helper('eb2ccore')->getNewDomDocument();
		$dlDoc->loadXML(
			'<product_to_be_deleted>
				<sku operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">45-4321</sku>
				<sku operation_type="Delete" gsi_client_id="MAGTNA" catalog_id="45">45-9432</sku>
			</product_to_be_deleted>'
		);

		$catalogId = '45';

		$xpath = new DOMXPath($dlDoc);

		$xslt = 'path/to/delete/xslt.xsl';

		$productHelperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('splitDomByXslt'))
			->getMock();
		$productHelperMock->expects($this->once())
			->method('splitDomByXslt')
			->with($this->equalTo($doc), $this->equalTo($xslt))
			->will($this->returnValue($dlDoc));
		$this->replaceByMock('helper', 'eb2cproduct', $productHelperMock);

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('getNewDomXPath', 'normalizeSku'))
			->getMock();
		$coreHelperMock->expects($this->once())
			->method('getNewDomXPath')
			->with($this->equalTo($dlDoc))
			->will($this->returnValue($xpath));
		$coreHelperMock->expects($this->exactly(2))
			->method('normalizeSku')
			->will($this->returnValueMap(array(
				array($skus[0], $catalogId, $skus[0]),
				array($skus[1], $catalogId, $skus[1])
			)));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_getDoc', '_getXsltPath'))
			->getMock();
		$file->expects($this->once())
			->method('_getDoc')
			->will($this->returnValue($doc));
		$file->expects($this->once())
			->method('_getXsltPath')
			->with(
				$this->identicalTo($cfgData['xslt_deleted_sku']),
				$this->identicalTo($cfgData['xslt_module'])
			)
			->will($this->returnValue($xslt));

		EcomDev_Utils_Reflection::setRestrictedPropertyValue($file, '_feedDetails', array(
			'doc' => $doc,
			'local' => '/EbayEnterprise/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_Subset.xml',
			'remote' => '/ItemMaster/',
			'timestamp' => '2012-07-06 10:09:05',
			'type' => 'ItemMaster',
			'error_file' => '/EbayEnterprise/Eb2c/Feed/Product/ItemMaster/outbound/ItemMaster_20140107224605_12345_ABCD.xml'
		));

		$this->assertSame($dData, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$file, '_getSkusToRemoveFromWebsites', array($cfgData)
		));
	}
	/**
	 * Test getting an array of all SKUs contained in a split feed file. Can
	 * assume that any SKUs to delete have already been stripped out by the XSLT.
	 */
	public function testGetSkusToUpdate()
	{
		$cfgData = array('all_skus_xpath' => '/Items/Item/ItemId/ClientItemId|/Items/Item/UniqueID|/Items/Item/ClientItemId');
		$skus = array('45-12345', '45-23456', '45-34567');
		$doc = '<Items>
					<Item><ItemId><ClientItemId>45-12345</ClientItemId></ItemId></Item>
					<Item><ClientItemId>45-23456</ClientItemId></Item>
					<Item><UniqueID>45-34567</UniqueID></Item>
				</Items>';

		$dom = Mage::helper('eb2ccore')->getNewDomDocument();
		$dom->loadXML($doc);
		$xpath = new DOMXPath($dom);

		$catalogId = 45;

		$coreHelperMock = $this->getHelperMockBuilder('eb2ccore/data')
			->disableOriginalConstructor()
			->setMethods(array('normalizeSku', 'getConfigModel'))
			->getMock();
		$coreHelperMock->expects($this->exactly(3))
			->method('normalizeSku')
			->will($this->returnValueMap(array(
				array($skus[0], $catalogId, $skus[0]),
				array($skus[1], $catalogId, $skus[1]),
				array($skus[2], $catalogId, $skus[2])
			)));
		$coreHelperMock->expects($this->once())
			->method('getConfigModel')
			->will($this->returnValue($this->buildCoreConfigRegistry(array(
				'catalogId' => $catalogId
			))));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelperMock);

		$file = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$this->assertSame($skus, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$file, '_getSkusToUpdate', array($xpath, $cfgData)
		));
	}
	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Feed_File::_removeFromWebsites method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_removeFromWebsites given
	 *                a mocked Mage_Catalog_Model_Resource_Product_Collection object and an array of extracted data to be
	 *                remove per for any given website.
	 * Expectation 2: the method EbayEnterprise_Eb2cProduct_Helper_Data::loadWebsiteFilter will be called which will return
	 *                an array of websites with client id, catalog id and mage website id keys per webssites
	 * Expectation 3: the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_getSkusInWebsite will be invoked given the array
	 *                of skus data and an array of specific website data
	 * Expectation 4: the method Mage_Catalog_Model_Resource_Product_Collection::getItemById will then be invoked given
	 *                a sku if the return value is an Mage_Catalog_Model_Product object it will call the method
	 *                Mage_Catalog_Model_Product::getWebsites which will return an array of all website ids in this product
	 *                this array will be pass as the first parameter to the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_removeWebsiteId
	 *                and a second parameter of the website data mage website id which will return an array excluding the website id
	 *                that was pass it and this return array will be pass to the method Mage_Catalog_Model_Product::setWebsites method
	 * Expectation 5: last thing is the method Mage_Catalog_Model_Resource_Product_Collection::save will be inovked
	 */
	public function testRemoveFromWebsite()
	{
		$sku = '45-1334';
		$dData = array($sku => array());
		$websiteFilters = array(
			array('mage_website_id' => '1'),
			array('mage_website_id' => '2')
		);
		$wIds = array('1', '2', '3');
		$removedIds = array('2', '3');

		$productMock = $this->getModelMockBuilder('catalog/product')
			->disableOriginalConstructor()
			->setMethods(array('getWebsiteIds', 'setWebsiteIds'))
			->getMock();
		$productMock->expects($this->once())
			->method('getWebsiteIds')
			->will($this->returnValue($wIds));
		$productMock->expects($this->once())
			->method('setWebsiteIds')
			->with($this->identicalTo($removedIds))
			->will($this->returnSelf());

		$collectionMock = $this->getResourceModelMockBuilder('eb2cproduct/feed_product_collection')
			->disableOriginalConstructor()
			->setMethods(array('getItemById', 'save'))
			->getMock();
		$collectionMock->expects($this->once())
			->method('getItemById')
			->with($this->identicalTo($sku))
			->will($this->returnValue($productMock));
		$collectionMock->expects($this->once())
			->method('save')
			->will($this->returnSelf());

		$helperMock = $this->getHelperMockBuilder('eb2cproduct/data')
			->disableOriginalConstructor()
			->setMethods(array('loadWebsiteFilters'))
			->getMock();
		$helperMock->expects($this->once())
			->method('loadWebsiteFilters')
			->will($this->returnValue($websiteFilters));
		$this->replaceByMock('helper', 'eb2cproduct', $helperMock);

		$fileMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(array('_getSkusInWebsite', '_removeWebsiteId'))
			->getMock();
		$fileMock->expects($this->exactly(2))
			->method('_getSkusInWebsite')
			->will($this->returnValueMap(array(
				array($dData, $websiteFilters[0], array($sku)),
				array($dData, $websiteFilters[1], array())
			)));
		$fileMock->expects($this->once())
			->method('_removeWebsiteId')
			->with($this->identicalTo($wIds), $this->identicalTo($websiteFilters[0]['mage_website_id']))
			->will($this->returnValue($removedIds));

		$this->assertSame($fileMock, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$fileMock, '_removeFromWebsites', array($collectionMock, $dData)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Feed_File::_getSkusInWebsite method for the following expectations
	 * Expectation 1: this test will invoked the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_getSkusInWebsite given
	 *                an array of skus map to gsi_client_id/catalog_id and the second parameter pass is an array of
	 *                website containing config for client_id, and catalog_id
	 */
	public function testGetSkusInWebsite()
	{
		$skus = array('52-8842', '53-9448');
		$result = array($skus[0]);
		$dData = array(
			$skus[0] => array(
				'gsi_client_id' => 'MWS',
				'catalog_id' => '52'
			),
			$skus[1] => array(
				'gsi_client_id' => 'SWM',
				'catalog_id' => '53'
			),
		);
		$wData = array(
			'client_id' => 'MWS',
			'catalog_id' => '52'
		);

		$fileMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$fileMock, '_getSkusInWebsite', array($dData, $wData)
		));
	}

	/**
	 * Test EbayEnterprise_Eb2cProduct_Model_Feed_File::_removeWebsiteId method for the following expectations
	 * Expectation 1: the method EbayEnterprise_Eb2cProduct_Model_Feed_File::_removeWebsiteId will be invoked by this test
	 *                given an array of website ids and string of website id to exclude from the website ids
	 */
	public function testRemoveWebsiteId()
	{
		$websiteIds = array('1', '2', '3');
		$result = array('1' => '2', '2' => '3');
		$websiteId = '1';

		$fileMock = $this->getModelMockBuilder('eb2cproduct/feed_file')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod(
			$fileMock, '_removeWebsiteId', array($websiteIds, $websiteId)
		));
	}

}
