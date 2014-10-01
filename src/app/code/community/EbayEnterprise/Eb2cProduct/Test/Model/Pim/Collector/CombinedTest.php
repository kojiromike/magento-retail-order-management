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

class EbayEnterprise_Eb2cProduct_Test_Model_Pim_Collector_CombinedTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// stubbed product id collection
	protected $_collection;
	// stubbed stores
	protected $_store = 'default store';
	protected $_stores;
	// object to add batches to for processing.
	protected $_batchContainer;
	// @var EbayEnterprise_Eb2cCore_Helper_Languages
	protected $_langHelper;
	// @var EbayEnterprise_Eb2cProduct_Helper_Data
	protected $_prodHelper;

	public function setUp()
	{
		parent::setUp();
		$this->_store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()->setMethods(array('getId'))->getMock();
		$this->_store->expects($this->any())->method('getId')->will($this->returnValue(0));
		$this->_stores = array(0 => $this->_store);
		$this->_collection = $this->getResourceModelMock('catalog/product_collection', array('load', 'addFieldToFilter'));
		$this->_batchContainer = $this->getModelMock('eb2cproduct/pim_batch_container', array('addBatch'));
		$this->_langHelper = $this->getHelperMock('eb2ccore/languages', array('getStores'));
		$this->_langHelper->expects($this->any())->method('getStores')->will($this->returnValue($this->_stores));
		$this->_prodHelper = $this->getHelperMock('eb2cproduct/data', array('getDefaultStoreViewId'));
		$this->_prodHelper->expects($this->any())->method('getDefaultStoreViewId')->will($this->returnValue(0));
	}
	/**
	 * verify batches are generated for the pricing feed for each webiste
	 */
	public function testGatherAllAsOneBatch()
	{
		$config = array('feed type config');
		$cutoffDate = 'a date string';
		$cutoffFilter = array('gteq' => $cutoffDate);

		$eventObserver = $this->_buildEventObserver(array(
			'cutoff_date' => $cutoffDate, 'container' => $this->_batchContainer, 'feed_type_config' => $config)
		);
		$collector = Mage::getModel('eb2cproduct/pim_collector_combined');
		$this->replaceByMock('resource_model', 'catalog/product_collection', $this->_collection);
		$this->replaceByMock('helper', 'eb2ccore/languages', $this->_langHelper);
		$this->replaceByMock('helper', 'eb2cproduct', $this->_prodHelper);
		$this->_collection->expects($this->any())->method('load')->will($this->returnSelf());
		$this->_collection->expects($this->any())->method('addFieldToFilter')
			->with($this->identicalTo('updated_at'), $this->identicalTo($cutoffFilter))
			->will($this->returnSelf());

		$this->_batchContainer->expects($this->once())
			->method('addBatch')->with(
				$this->identicalTo($this->_collection),
				$this->identicalTo(array()),
				$this->identicalTo($config),
				$this->identicalTo($this->_store)
			)
			->will($this->returnSelf());
		$collector->gatherAllAsOneBatch($eventObserver);
	}
}
