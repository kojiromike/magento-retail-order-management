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

class EbayEnterprise_Eb2cOrder_Test_Helper_DataTest extends EbayEnterprise_Eb2cOrder_Test_Abstract
{
	protected $_helper;
	// @var Mage_Core_Model_App original Mage::app instance
	protected $_origApp;
	/**
	 * setUp method
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_origApp = EcomDev_Utils_Reflection::getRestrictedPropertyValue('Mage', '_app');
		$this->_helper = Mage::helper('eb2corder');
	}
	/**
	 * Restore original Mage::app instance
	 */
	public function tearDown()
	{
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $this->_origApp);
		parent::tearDown();
	}
	/**
	 * Make sure we get back a EbayEnterprise_Eb2cCore_Model_Config_Registry and that
	 * we can see some sensible values in it.
	 * @loadFixture basicTestConfig.yaml
	 */
	public function testGetConfig()
	{
		$this->replaceCoreConfigRegistry(
			array(
				'apiRegion' => 'api_rgn',
				'clientId'  => 'client_id',
			)
		);
		$config = $this->_helper->getConfigModel();
		$this->assertStringStartsWith(
			'api_rgn',
			$config->apiRegion
		);
		$this->assertStringStartsWith(
			'client_id',
			$config->clientId
		);
	}

	/**
	 * Testing getOperationUri method with both create and cancel operations
	 *
	 * @loadFixture basicTestConfig.yaml
	 */
	public function testGetOperationUri()
	{
		$this->replaceCoreConfigRegistry(
			array(
				'apiRegion' => 'api_rgn',
				'clientId'  => 'client_id',
			)
		);
		$this->assertStringEndsWith(
			'create.xml',
			$this->_helper->getOperationUri('create')
		);

		$this->assertStringEndsWith(
			'cancel.xml',
			$this->_helper->getOperationUri('cancel')
		);
	}
	/**
	 * Test getting an order history URL for a given store
	 */
	public function testOrderHistoryUrl()
	{
		$order = Mage::getModel('sales/order', array('store_id' => 1, 'entity_id' => 5));

		$urlMock = $this->getModelMock('core/url', array('getUrl'));
		$urlMock->expects($this->once())
			->method('getUrl')
			->with($this->identicalTo('sales/order/view'), $this->identicalTo(array('_store' => 1, 'order_id' => 5)))
			->will($this->returnValue('http://test.example.com/mocked/order/create'));
		$this->replaceByMock('model', 'core/url', $urlMock);
		$this->assertSame(
			'http://test.example.com/mocked/order/create',
			Mage::helper('eb2corder')->getOrderHistoryUrl($order)
		);
	}
	/**
	 * Test removing the order increment id prefix.
	 */
	public function testRemoveOrderIncrementPrefix()
	{
		$admin = Mage::getModel('core/store', array('store_id' => 0));
		$default = Mage::getModel('core/store', array('store_id' => 1));

		$app = $this->getModelMock('core/app', array('getStores'));
		$app->expects($this->any())
			->method('getStores')
			->will($this->returnValueMap(array(
				array(true, false, array(0 => $admin, 1 => $default)),
				array(false, false, array(1 => $default))
			)));

		$adminConfig = $this->buildCoreConfigRegistry(array('clientOrderIdPrefix' => '555'));
		$storeConfig = $this->buildCoreConfigRegistry(array('clientOrderIdPrefix' => '7777'));
		$coreHelper = $this->getHelperMock('eb2ccore/data', array('getConfigModel'));
		$coreHelper->expects($this->any())
			->method('getConfigModel')
			->will($this->returnValueMap(array(
				array(0, $adminConfig),
				array(1, $storeConfig),
			)));
		$this->replaceByMock('helper', 'eb2ccore', $coreHelper);
		EcomDev_Utils_Reflection::setRestrictedPropertyValue('Mage', '_app', $app);
		// should be able to replace the order id prefix from any config scope
		$this->assertSame('8888888', $this->_helper->removeOrderIncrementPrefix('77778888888'));
		$this->assertSame('8888888', $this->_helper->removeOrderIncrementPrefix('5558888888'));
		// when no matching prefix on the original increment id, should return unmodified value
		$this->assertSame('1238888888', $this->_helper->removeOrderIncrementPrefix('1238888888'));
		// must work with null as when the first increment id for a store is
		// created, the "last id" will be given as null
		$this->assertSame('', $this->_helper->removeOrderIncrementPrefix(null));
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cOrder_Helper_Data::getOrderCollectionByIncrementIds
	 * return a Mage_Sales_Model_Resource_Order_Collection object.
	 */
	public function testGetOrderCollectionByIncrementIds()
	{
		$incrementIds = array('0004600007', '0004600009', '0004600011');
		$orderCollection = $this->getResourceModelMock('sales/order_collection', array(
			'addFieldToFilter'
		));
		$orderCollection->expects($this->once())
			->method('addFieldToFilter')
			->with($this->identicalTo('increment_id'), $this->identicalTo( array('in' => $incrementIds)))
			->will($this->returnSelf());
		$this->replaceByMock('resource_model', 'sales/order_collection', $orderCollection);

		$this->assertSame($orderCollection, Mage::helper('eb2corder')->getOrderCollectionByIncrementIds($incrementIds));
	}
	/**
	 * Test that the method EbayEnterprise_Eb2cOrder_Helper_Data::extractOrderEventIncrementIds
	 * will return an empty array when an empty xml string is passed to it.
	 */
	public function testextractOrderEventIncrementIds()
	{
		$this->assertSame(array(), Mage::helper('eb2corder')->extractOrderEventIncrementIds('', '//some/x/path'));
	}
}
