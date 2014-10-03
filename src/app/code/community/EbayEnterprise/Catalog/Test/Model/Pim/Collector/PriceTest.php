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

class EbayEnterprise_Catalog_Test_Model_Pim_Collector_PriceTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// product collections
	protected $_collectionDefault;
	protected $_collectionWeb1;
	// stubbed store lists
	protected $_defaultStores = array();
	protected $_web1Stores = array();
	protected $_feedConfig = array('feed type config');
	// @var Mage_Core_Model_Store
	protected $_store1;
	// @var Mage_Core_Model_Store
	protected $_store2;
	// @var Mage_Core_Model_Store
	protected $_defaultStore;
	// @var array store list
	// @var Varien_Event_Observer
	protected $_eventObserver;
	// @var Mage_Core_Model_Website stubbed default (admin) website
	protected $_defaultWebsite;
	// @var Mage_Core_Model_Website stubbed website 1
	protected $_website1;
	// object to add batches to for processing.
	protected $_batchContainer;
	// @var array map of website filter to the collection stubbed to return a set of product id's
	protected $_websiteFilterValueMap;
	// @var array $_oldWebsites original websites list to restore after the test
	private $_oldWebsites;

	public function setUp()
	{
		parent::setUp();
		// keep a backup copy of all websites
		$this->_oldWebsites = EcomDev_Utils_Reflection::getRestrictedPropertyValue(Mage::app(), '_websites');
		// mock up a store for the default website
		$this->_defaultStore = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()
			->setMethods(array('getId'))->getMock();
		$this->_defaultStore->expects($this->any())->method('getId')->will($this->returnValue(0));

		$this->_defaultStores = array(0 => $this->_defaultStore);
		// mock up a store for the non default website
		$this->_store1 = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()
			->setMethods(array('getId'))->getMock();
		$this->_store1->expects($this->any())->method('getId')->will($this->returnValue(1));

		$this->_store2 = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()
			->setMethods(array('getId'))->getMock();
		$this->_store2->expects($this->any())->method('getId')->will($this->returnValue(2));

		$this->_web1Stores = array(1 => $this->_store1, 2 => $this->_store2);
		// mock replacement websites with the store to use as the default.
		$this->_website1 = $this->_stubWebsite(1, 'web1', $this->_store1);
		$this->_defaultWebsite = $this->_stubWebsite(0, 'admin', $this->_defaultStore);
		$this->_defaultWebsite->addData(Mage::app()->getWebsite(0)->getData());
		$this->_batchContainer = $this->getModelMock('ebayenterprise_catalog/pim_batch_container', array('addBatch'));
		$cutoffDate = 'a date string';
		$this->_eventObserver = $this->_buildEventObserver(array(
			'cutoff_date' => $cutoffDate, 'container' => $this->_batchContainer, 'feed_type_config' => $this->_feedConfig)
		);
		$this->_collectionDefault = $this->getResourceModelMock('catalog/product_collection', array('load', 'addWebsiteFilter', 'getColumnValues', 'addFieldToFilter'));
		$this->_collectionWeb1 = $this->getResourceModelMock('catalog/product_collection', array('load', 'addWebsiteFilter', 'getColumnValues', 'addFieldToFilter'));
		// setup which website filters will return which set of product ids.
		$this->_websiteFilterValueMap = array(
			array(array(0), $this->_collectionDefault),
			array(array(1), $this->_collectionWeb1),
		);
		// stub the language helper to return stores for each website
		$this->_getWebsiteStoresValueMap = array(
			array($this->_defaultWebsite, null,  $this->_defaultStores),
			array($this->_website1, null, $this->_web1Stores),
		);
		$this->_languageHelper = $this->getHelperMock('eb2ccore/languages', array('getWebsiteStores'));
		$this->_languageHelper->expects($this->any())
			->method('getWebsiteStores') ->will($this->returnValueMap($this->_getWebsiteStoresValueMap));
		$cutoffFilter = array('gteq' => $cutoffDate);
		foreach (array($this->_collectionDefault, $this->_collectionWeb1) as $collectionMock) {
			$collectionMock->expects($this->any())->method('addWebsiteFilter')->will($this->returnValueMap($this->_websiteFilterValueMap));
			$collectionMock->expects($this->any())->method('load')->will($this->returnSelf());
			$collectionMock->expects($this->any())->method('addFieldToFilter')
				->with($this->identicalTo('updated_at'), $this->identicalTo($cutoffFilter))
				->will($this->returnSelf());
		}
	}
	/**
	 * stub a website
	 * @param  int    $id
	 * @param  string $code
	 * @param  Mage_Core_Model_Store  $store default store for the website
	 * @return Mage_Core_Model_Website mock website
	 */
	protected function _stubWebsite($id, $code, $store)
	{
		// disable the website constructors to prevent the 'this is not a controller test' error.
		$website = $this->getModelMockBuilder('core/website')->disableOriginalConstructor()
			->setMethods(array('getId', 'getDefaultStore'))->getMock();
		$website->addData(array('code' => $code));
		$website->expects($this->any())->method('getId')->will($this->returnValue($id));
		$website->expects($this->any())
			->method('getDefaultStore')->will($this->returnValue($store));
		return $website;
	}
	// restore the websites.
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(Mage::app(), array(
			'_websites' => $this->_oldWebsites
		));
		parent::tearDown();
	}
	/**
	 * verify batches are generated for the pricing feed for each webiste
	 */
	public function testGatherBatches()
	{
		$websites = array(0 => $this->_defaultWebsite, 1 => $this->_website1);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(Mage::app(), array('_websites' => $websites));

		$collection = $this->_collectionDefault;
		$this->replaceByMock('resource_model', 'catalog/product_collection', $collection);
		$this->replaceByMock('helper', 'eb2ccore/languages', $this->_languageHelper);

		$priceCollector = Mage::getModel('ebayenterprise_catalog/pim_collector_price');
		$this->_batchContainer->expects($this->once())
			->method('addBatch')->with(
				$this->identicalTo($collection),
				$this->identicalTo(array(2 => $this->_store2)),
				$this->identicalTo($this->_feedConfig),
				$this->identicalTo($this->_store1)
			)
			->will($this->returnSelf());
		$priceCollector->gatherBatches($this->_eventObserver);
	}
	/**
	 * verify the default website is use when no other websites are
	 * available.
	 */
	public function testGatherBatchesUsesDefaultWebsiteWhenNoOthersExist()
	{
		$websites = array(0 => $this->_defaultWebsite);
		EcomDev_Utils_Reflection::setRestrictedPropertyValues(Mage::app(), array('_websites' => $websites));
		$this->replaceByMock('helper', 'eb2ccore/languages', $this->_languageHelper);
		$priceCollector = Mage::getModel('ebayenterprise_catalog/pim_collector_price');
		$this->replaceByMock('resource_model', 'catalog/product_collection', $this->_collectionDefault);
		$this->_batchContainer->expects($this->once())
			->method('addBatch')->with(
				$this->identicalTo($this->_collectionDefault),
				$this->identicalTo(array()),
				$this->identicalTo($this->_feedConfig),
				$this->identicalTo($this->_defaultStore)
			)
			->will($this->returnSelf());
		$priceCollector->gatherBatches($this->_eventObserver);
	}
}
