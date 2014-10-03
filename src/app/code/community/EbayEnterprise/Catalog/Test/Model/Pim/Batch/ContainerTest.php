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
 * test the container and batch class.
 */
class EbayEnterprise_Catalog_Test_Model_Pim_Batch_ContainerTest
	extends EbayEnterprise_Eb2cCore_Test_Base
{
	// @var Varien_Data_Collection
	protected $_collection;
	// @var array stub list of stores
	protected $_stores;
	// @var array stub feed config
	protected $_config;
	// @var array list of product ids
	protected $_idList;
	// @var Mage_Core_Model_Store
	protected $_store;

	public function setUp()
	{
		$this->_store = $this->getModelMockBuilder('core/store')->disableOriginalConstructor()->getMock();
		$this->_collection = $this->getMock('Varien_Data_Collection', array('getColumnValues'));
		$this->_stores = array('list of store views');
		$this->_config = array('this is config data');
		$this->_idList = array('list of product ids');
	}

	/**
	 * Verify the addBatch method will create a batch object with the given
	 * collection, store view list, and configuration.
	 *
	 * Verify the list of batches can be retrieved from the container.
	 *
	 * Verify the batch object will return a list of product id's from the
	 * collection.
	 */
	public function testContainer()
	{

		$this->_collection->expects($this->any())
			->method('getColumnValues')->will($this->returnValue($this->_idList));

		$container = Mage::getModel('ebayenterprise_catalog/pim_batch_container');
		$container->addBatch($this->_collection, $this->_stores, $this->_config, $this->_store);
		$batches = $container->getBatches();
		$this->assertCount(1, $batches);
		$batch = $batches[0];
		$this->assertSame($this->_idList, $batch->getProductIds());
		$this->assertSame($this->_stores, $batch->getStores());
		$this->assertSame($this->_config, $batch->getFeedTypeConfig());
		$this->assertSame($this->_store, $batch->getDefaultStore());
	}
}
